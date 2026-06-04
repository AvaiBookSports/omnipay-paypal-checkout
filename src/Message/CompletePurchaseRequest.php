<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;

class CompletePurchaseRequest extends AbstractRequest
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        if (!$this->getTransactionReference()) {
            $this->setTransactionReference($this->httpRequest->query->get('token'));
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

            $captureId = $this->extractCaptureId($order);

            return new Response(
                $this,
                \json_decode(\json_encode($order->jsonSerialize()), true),
                $order->getStatus() ?? 'UNKNOWN',
                $captureId ?? $order->getId(),
                $order->getPurchaseUnits()[0]?->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }

    private function extractCaptureId(\PaypalServerSdkLib\Models\Order $order): ?string
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
