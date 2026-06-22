<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\CaptureRequest as SdkCaptureRequest;
use PaypalServerSdkLib\Models\Money;

/**
 * @see \Omnipay\PayPalCheckout\Tests\Message\CaptureRequestTest
 */
class CaptureRequest extends AbstractRequest
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('transactionReference');

        return [
            'authorizationId' => $this->getTransactionReference(),
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
                'authorizationId' => $data['authorizationId'],
                'prefer' => 'return=representation',
            ];

            if ($data['amount'] !== null && $data['currency'] !== null) {
                $captureRequest = new SdkCaptureRequest();
                $captureRequest->setAmount(new Money($data['currency'], $data['amount']));
                $options['body'] = $captureRequest;
            }

            $apiResponse = $this
                ->getSdkClient()
                ->getPaymentsController()
                ->captureAuthorizedPayment($options);

            $capture = $apiResponse->getResult();

            return new Response(
                $this,
                \json_decode(\json_encode($capture->jsonSerialize()), true),
                $capture->getStatus() ?? 'UNKNOWN',
                $capture->getId(),
                $capture->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
