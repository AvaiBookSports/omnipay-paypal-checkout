<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

class RedirectResponse extends AbstractResponse implements RedirectResponseInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        RequestInterface $request,
        array $data,
        private readonly string $orderId,
        private readonly string $approvalUrl,
        private readonly string $status,
    ) {
        parent::__construct($request, $data);
    }

    public function isSuccessful(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        return true;
    }

    public function getRedirectUrl(): string
    {
        return $this->approvalUrl;
    }

    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getTransactionReference(): string
    {
        return $this->orderId;
    }

    public function getCode(): string
    {
        return $this->status;
    }

    public function getMessage(): ?string
    {
        return null;
    }
}
