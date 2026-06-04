<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;

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

            if ($result !== null) {
                return new Response(
                    $this,
                    \json_decode(\json_encode($result->jsonSerialize()), true),
                    $result->getStatus() ?? 'VOIDED',
                    $result->getId(),
                );
            }

            return new Response(
                $this,
                [],
                'VOIDED',
                $data['authorizationId'],
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
