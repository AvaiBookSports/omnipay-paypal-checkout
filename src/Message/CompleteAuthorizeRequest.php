<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Models\OrderAuthorizeResponse;

/**
 * @see \Omnipay\PayPalCheckout\Tests\Message\CompleteAuthorizeRequestTest
 */
class CompleteAuthorizeRequest extends AbstractRequest
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
                ->authorizeOrder(['id' => $data['orderId'], 'prefer' => 'return=representation']);

            $order = $apiResponse->getResult();
            if (!$order instanceof OrderAuthorizeResponse) {
                return new ErrorResponse($this, 'Unexpected API response type', '500');
            }

            $authorizationId = $this->extractAuthorizationId($order);

            $purchaseUnits = $order->getPurchaseUnits() ?? [];
            $firstPurchaseUnit = $purchaseUnits[0] ?? null;

            return new Response(
                $this,
                $this->serializeToArray($order),
                $order->getStatus() ?? 'UNKNOWN',
                $authorizationId ?? $order->getId(),
                $firstPurchaseUnit?->getInvoiceId(),
            );
        } catch (ErrorException $errorException) {
            return new ErrorResponse($this, $errorException->getMessage(), (string) $errorException->getCode());
        }
    }

    private function extractAuthorizationId(OrderAuthorizeResponse $orderAuthorizeResponse): ?string
    {
        $purchaseUnits = $orderAuthorizeResponse->getPurchaseUnits() ?? [];
        foreach ($purchaseUnits as $purchaseUnit) {
            $payments = $purchaseUnit->getPayments();
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
