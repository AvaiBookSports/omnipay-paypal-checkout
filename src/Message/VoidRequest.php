<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\PaymentAuthorization;

/**
 * @see \Omnipay\PayPalCheckout\Tests\Message\VoidRequestTest
 */
class VoidRequest extends AbstractRequest
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('transactionReference');

        return [
            'authorizationId' => $this->getTransactionReference(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendData($data): Response|ErrorResponse
    {
        try {
            $apiResponse = $this
                ->getSdkClient()
                ->getPaymentsController()
                ->voidPayment([
                    'authorizationId' => $data['authorizationId'],
                    'prefer' => 'return=representation',
                ]);

            $result = $apiResponse->getResult();

            if ($result instanceof PaymentAuthorization) {
                return new Response(
                    $this,
                    $this->serializeToArray($result),
                    $result->getStatus() ?? 'VOIDED',
                    $result->getId(),
                );
            }

            $authorizationId = \is_string($data['authorizationId']) ? $data['authorizationId'] : null;
            return new Response(
                $this,
                [],
                'VOIDED',
                $authorizationId,
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
