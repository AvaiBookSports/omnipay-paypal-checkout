<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout;

use Omnipay\Common\AbstractGateway;
use Omnipay\PayPalCheckout\Message\AuthorizeRequest;
use Omnipay\PayPalCheckout\Message\CaptureRequest;
use Omnipay\PayPalCheckout\Message\CompleteAuthorizeRequest;
use Omnipay\PayPalCheckout\Message\CompletePurchaseRequest;
use Omnipay\PayPalCheckout\Message\FetchTransactionRequest;
use Omnipay\PayPalCheckout\Message\PurchaseRequest;
use Omnipay\PayPalCheckout\Message\RefundRequest;
use Omnipay\PayPalCheckout\Message\VoidRequest;

class Gateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'PayPal Checkout';
    }

    public function getShortName(): string
    {
        return 'PayPalCheckout';
    }

    public function getDefaultParameters(): array
    {
        return [
            'clientId' => '',
            'clientSecret' => '',
            'testMode' => false,
            'brandName' => '',
        ];
    }

    public function getClientId(): string
    {
        return $this->getParameter('clientId') ?? '';
    }

    public function setClientId(string $value): self
    {
        return $this->setParameter('clientId', $value);
    }

    public function getClientSecret(): string
    {
        return $this->getParameter('clientSecret') ?? '';
    }

    public function setClientSecret(string $value): self
    {
        return $this->setParameter('clientSecret', $value);
    }

    public function getBrandName(): ?string
    {
        return $this->getParameter('brandName');
    }

    public function setBrandName(?string $value): self
    {
        return $this->setParameter('brandName', $value);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function authorize(array $parameters = []): AuthorizeRequest
    {
        /** @var AuthorizeRequest */
        return $this->createRequest(AuthorizeRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function completeAuthorize(array $parameters = []): CompleteAuthorizeRequest
    {
        /** @var CompleteAuthorizeRequest */
        return $this->createRequest(CompleteAuthorizeRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function capture(array $parameters = []): CaptureRequest
    {
        /** @var CaptureRequest */
        return $this->createRequest(CaptureRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function purchase(array $parameters = []): PurchaseRequest
    {
        /** @var PurchaseRequest */
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function completePurchase(array $parameters = []): CompletePurchaseRequest
    {
        /** @var CompletePurchaseRequest */
        return $this->createRequest(CompletePurchaseRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function refund(array $parameters = []): RefundRequest
    {
        /** @var RefundRequest */
        return $this->createRequest(RefundRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function void(array $parameters = []): VoidRequest
    {
        /** @var VoidRequest */
        return $this->createRequest(VoidRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function fetchTransaction(array $parameters = []): FetchTransactionRequest
    {
        /** @var FetchTransactionRequest */
        return $this->createRequest(FetchTransactionRequest::class, $parameters);
    }
}
