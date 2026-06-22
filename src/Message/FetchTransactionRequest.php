<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\Order;

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
            if (!$order instanceof Order) {
                return new ErrorResponse($this, 'Unexpected API response type', '500');
            }

            $purchaseUnits = $order->getPurchaseUnits() ?? [];
            $firstPurchaseUnit = $purchaseUnits[0] ?? null;

            return new Response(
                $this,
                $this->serializeToArray($order),
                $order->getStatus() ?? 'UNKNOWN',
                $order->getId(),
                $firstPurchaseUnit?->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }
}
