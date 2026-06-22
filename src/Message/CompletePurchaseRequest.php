<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\Order;

/**
 * @see \Omnipay\PayPalCheckout\Tests\Message\CompletePurchaseRequestTest
 */
class CompletePurchaseRequest extends AbstractRequest
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        if (!$this->getTransactionReference()) {
            $token = $this->httpRequest->query->get('token');
            if ($token !== null) {
                $this->setTransactionReference($token);
            }
        }

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
                ->captureOrder(['id' => $data['orderId'], 'prefer' => 'return=representation']);

            $order = $apiResponse->getResult();
            if (!$order instanceof Order) {
                return new ErrorResponse($this, 'Unexpected API response type', '500');
            }

            $captureId = $this->extractCaptureId($order);

            $purchaseUnits = $order->getPurchaseUnits() ?? [];
            $firstPurchaseUnit = $purchaseUnits[0] ?? null;

            return new Response(
                $this,
                $this->serializeToArray($order),
                $order->getStatus() ?? 'UNKNOWN',
                $captureId ?? $order->getId(),
                $firstPurchaseUnit?->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }

    private function extractCaptureId(Order $order): ?string
    {
        $purchaseUnits = $order->getPurchaseUnits() ?? [];
        foreach ($purchaseUnits as $purchaseUnit) {
            $payments = $purchaseUnit->getPayments();
            if ($payments === null) {
                continue;
            }

            $captures = $payments->getCaptures() ?? [];
            foreach ($captures as $capture) {
                $id = $capture->getId();
                if ($id !== null) {
                    return $id;
                }
            }
        }

        return null;
    }
}
