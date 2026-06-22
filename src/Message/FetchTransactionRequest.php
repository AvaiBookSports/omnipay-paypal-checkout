<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;

/**
 * @see \Omnipay\PayPalCheckout\Tests\Message\FetchTransactionRequestTest
 */
class FetchTransactionRequest extends AbstractRequest
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('transactionReference');

        return [
            'orderId' => $this->getTransactionReference(),
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
                ->getOrdersController()
                ->getOrder(['id' => $data['orderId']]);

            $order = $apiResponse->getResult();

            return new Response(
                $this,
                \json_decode(\json_encode($order->jsonSerialize()), true),
                $order->getStatus() ?? 'UNKNOWN',
                $order->getId(),
                $order->getPurchaseUnits()[0]?->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
