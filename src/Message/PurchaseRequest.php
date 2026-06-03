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
        $amount = new AmountWithBreakdown(
            (string) $data['currency'],
            (string) $data['amount'],
        );

        $purchaseUnit = new PurchaseUnitRequest($amount);
        if ($data['description'] !== null) {
            $purchaseUnit->setDescription($data['description']);
        }
        if ($data['transactionId'] !== null) {
            $purchaseUnit->setInvoiceId($data['transactionId']);
            $purchaseUnit->setReferenceId($data['transactionId']);
        }

        $orderRequest = new OrderRequest($data['intent'], [$purchaseUnit]);

        $experienceContext = new PaypalWalletExperienceContext();
        $experienceContext->setReturnUrl($data['returnUrl']);
        $experienceContext->setCancelUrl($data['cancelUrl']);
        $experienceContext->setShippingPreference(PaypalWalletContextShippingPreference::NO_SHIPPING);
        $experienceContext->setUserAction(PaypalExperienceUserAction::PAY_NOW);
        if ($data['brandName'] !== null && $data['brandName'] !== '') {
            $experienceContext->setBrandName($data['brandName']);
        }

        $paypalWallet = new PaypalWallet();
        $paypalWallet->setExperienceContext($experienceContext);

        $paymentSource = new PaymentSource();
        $paymentSource->setPaypal($paypalWallet);

        $orderRequest->setPaymentSource($paymentSource);

        try {
            $apiResponse = $this->getSdkClient()
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
        } catch (ErrorException $e) {
            return new ErrorResponse($this, $e->getMessage(), (string) $e->getCode());
        }
    }
}
