<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\Money;
use PaypalServerSdkLib\Models\RefundRequest as SdkRefundRequest;

class RefundRequest extends AbstractRequest
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('transactionReference');

        return [
            'captureId' => $this->getTransactionReference(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendData($data): Response|ErrorResponse
    {
        try {
            $options = [
                'captureId' => $data['captureId'],
                'prefer' => 'return=representation',
            ];

            if ($data['amount'] !== null && $data['currency'] !== null) {
                $refundRequest = new SdkRefundRequest();
                $refundRequest->setAmount(new Money($data['currency'], $data['amount']));
                $options['body'] = $refundRequest;
            }

            $apiResponse = $this
                ->getSdkClient()
                ->getPaymentsController()
                ->refundCapturedPayment($options);

            $refund = $apiResponse->getResult();

            return new Response(
                $this,
                \json_decode(\json_encode($refund->jsonSerialize()), true),
                $refund->getStatus() ?? 'UNKNOWN',
                $refund->getId(),
                $refund->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
