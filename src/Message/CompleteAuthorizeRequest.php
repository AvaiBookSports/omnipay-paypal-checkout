<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\OrderAuthorizeResponse;

class CompleteAuthorizeRequest extends AbstractRequest
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
                ->authorizeOrder(['id' => $data['orderId'], 'prefer' => 'return=representation']);

            /** @var OrderAuthorizeResponse $order */
            $order = $apiResponse->getResult();

            $authorizationId = $this->extractAuthorizationId($order);

            return new Response(
                $this,
                \json_decode(\json_encode($order->jsonSerialize()), true),
                $order->getStatus() ?? 'UNKNOWN',
                $authorizationId ?? $order->getId(),
                $order->getPurchaseUnits()[0]?->getInvoiceId(),
            );
        } catch (ErrorException $e) {
            return new ErrorResponse($this, $e->getMessage(), (string) $e->getCode());
        }
    }

    private function extractAuthorizationId(OrderAuthorizeResponse $order): ?string
    {
        $purchaseUnits = $order->getPurchaseUnits() ?? [];
        foreach ($purchaseUnits as $unit) {
            $payments = $unit->getPayments();
            if ($payments === null) {
                continue;
            }
            $authorizations = $payments->getAuthorizations() ?? [];
            foreach ($authorizations as $authorization) {
                $id = $authorization->getId();
                if ($id !== null) {
                    return $id;
                }
            }
        }

        return null;
    }
}
