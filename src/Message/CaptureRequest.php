<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\CapturedPayment;
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

            if (\is_string($data['amount']) && \is_string($data['currency'])) {
                $captureRequest = new SdkCaptureRequest();
                $captureRequest->setAmount(new Money($data['currency'], $data['amount']));
                $options['body'] = $captureRequest;
            }

            $apiResponse = $this
                ->getSdkClient()
                ->getPaymentsController()
                ->captureAuthorizedPayment($options);

            $capture = $apiResponse->getResult();
            if (!$capture instanceof CapturedPayment) {
                return new ErrorResponse($this, 'Unexpected API response type', '500');
            }

            return new Response(
                $this,
                $this->serializeToArray($capture),
                $capture->getStatus() ?? 'UNKNOWN',
                $capture->getId(),
                $capture->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
