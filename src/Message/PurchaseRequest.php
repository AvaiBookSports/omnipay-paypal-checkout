<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\AmountWithBreakdown;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\OrderRequest;
use PaypalServerSdkLib\Models\PaymentSource;
use PaypalServerSdkLib\Models\PaypalExperienceUserAction;
use PaypalServerSdkLib\Models\PaypalWallet;
use PaypalServerSdkLib\Models\PaypalWalletContextShippingPreference;
use PaypalServerSdkLib\Models\PaypalWalletExperienceContext;
use PaypalServerSdkLib\Models\PurchaseUnitRequest;

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
        $amountWithBreakdown = new AmountWithBreakdown(
            (string) $data['currency'],
            (string) $data['amount'],
        );

        $purchaseUnitRequest = new PurchaseUnitRequest($amountWithBreakdown);
        if ($data['description'] !== null) {
            $purchaseUnitRequest->setDescription($data['description']);
        }

        if ($data['transactionId'] !== null) {
            $purchaseUnitRequest->setInvoiceId($data['transactionId']);
            $purchaseUnitRequest->setReferenceId($data['transactionId']);
        }

        $orderRequest = new OrderRequest($data['intent'], [$purchaseUnitRequest]);

        $paypalWalletExperienceContext = new PaypalWalletExperienceContext();
        $paypalWalletExperienceContext->setReturnUrl($data['returnUrl']);
        $paypalWalletExperienceContext->setCancelUrl($data['cancelUrl']);
        $paypalWalletExperienceContext->setShippingPreference(PaypalWalletContextShippingPreference::NO_SHIPPING);
        $paypalWalletExperienceContext->setUserAction(PaypalExperienceUserAction::PAY_NOW);
        if ($data['brandName'] !== null && $data['brandName'] !== '') {
            $paypalWalletExperienceContext->setBrandName($data['brandName']);
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
            $orderId = $order->getId();
            $links = $order->getLinks() ?? [];
            $approvalUrl = $this->findApprovalUrl($links);

            if ($orderId === null || $approvalUrl === null) {
                throw new InvalidResponseException('PayPal order creation did not return an approval URL.');
            }

            return new RedirectResponse(
                $this,
                \json_decode(\json_encode($order->jsonSerialize()), true),
                $orderId,
                $approvalUrl,
                $order->getStatus() ?? 'CREATED',
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
