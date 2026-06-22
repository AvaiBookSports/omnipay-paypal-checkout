<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\AmountWithBreakdown;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\Order;
use PaypalServerSdkLib\Models\OrderRequest;
use PaypalServerSdkLib\Models\PaymentSource;
use PaypalServerSdkLib\Models\PaypalExperienceUserAction;
use PaypalServerSdkLib\Models\PaypalWallet;
use PaypalServerSdkLib\Models\PaypalWalletContextShippingPreference;
use PaypalServerSdkLib\Models\PaypalWalletExperienceContext;
use PaypalServerSdkLib\Models\PurchaseUnitRequest;

/**
 * @see \Omnipay\PayPalCheckout\Tests\Message\PurchaseRequestTest
 * @mago-expect lint:cyclomatic-complexity
 */
class PurchaseRequest extends AbstractRequest
{
    protected function getIntent(): string
    {
        return CheckoutPaymentIntent::CAPTURE;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('amount', 'currency', 'returnUrl', 'cancelUrl');

        return [
            'intent' => $this->getIntent(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'description' => $this->getDescription(),
            'transactionId' => $this->getTransactionId(),
            'returnUrl' => $this->getReturnUrl(),
            'cancelUrl' => $this->getCancelUrl(),
            'brandName' => $this->getBrandName(),
            'notifyUrl' => $this->getNotifyUrl(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendData($data): RedirectResponse|ErrorResponse
    {
        $intent = \is_string($data['intent']) ? $data['intent'] : '';
        $returnUrl = \is_string($data['returnUrl']) ? $data['returnUrl'] : null;
        $cancelUrl = \is_string($data['cancelUrl']) ? $data['cancelUrl'] : null;
        $brandName = \is_string($data['brandName']) ? $data['brandName'] : null;

        $amountWithBreakdown = new AmountWithBreakdown(
            \is_string($data['currency']) ? $data['currency'] : '',
            \is_string($data['amount']) ? $data['amount'] : '',
        );

        $purchaseUnitRequest = new PurchaseUnitRequest($amountWithBreakdown);
        if (\is_string($data['description'])) {
            $purchaseUnitRequest->setDescription($data['description']);
        }

        if (\is_string($data['transactionId'])) {
            $purchaseUnitRequest->setInvoiceId($data['transactionId']);
            $purchaseUnitRequest->setReferenceId($data['transactionId']);
        }

        $orderRequest = new OrderRequest($intent, [$purchaseUnitRequest]);

        $paypalWalletExperienceContext = new PaypalWalletExperienceContext();
        $paypalWalletExperienceContext->setReturnUrl($returnUrl);
        $paypalWalletExperienceContext->setCancelUrl($cancelUrl);
        $paypalWalletExperienceContext->setShippingPreference(PaypalWalletContextShippingPreference::NO_SHIPPING);
        $paypalWalletExperienceContext->setUserAction(PaypalExperienceUserAction::PAY_NOW);
        if ($brandName !== null && $brandName !== '') {
            $paypalWalletExperienceContext->setBrandName($brandName);
        }

        $paypalWallet = new PaypalWallet();
        $paypalWallet->setExperienceContext($paypalWalletExperienceContext);

        $paymentSource = new PaymentSource();
        $paymentSource->setPaypal($paypalWallet);

        $orderRequest->setPaymentSource($paymentSource);

        try {
            $apiResponse = $this
                ->getSdkClient()
                ->getOrdersController()
                ->createOrder(['body' => $orderRequest, 'prefer' => 'return=representation']);

            $order = $apiResponse->getResult();
            if (!$order instanceof Order) {
                return new ErrorResponse($this, 'Unexpected API response type', '500');
            }

            $orderId = $order->getId();
            $links = $order->getLinks() ?? [];
            $approvalUrl = $this->findApprovalUrl($links);

            if ($orderId === null || $approvalUrl === null) {
                throw new InvalidResponseException('PayPal order creation did not return an approval URL.');
            }

            return new RedirectResponse(
                $this,
                $this->serializeToArray($order),
                $orderId,
                $approvalUrl,
                $order->getStatus() ?? 'CREATED',
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
