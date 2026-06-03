<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;

class RedirectResponse extends AbstractResponse implements RedirectResponseInterface
{
    private string $orderId;
    private string $approvalUrl;
    private string $status;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        RequestInterface $request,
        array $data,
        string $orderId,
        string $approvalUrl,
        string $status,
    ) {
        parent::__construct($request, $data);
        $this->orderId = $orderId;
        $this->approvalUrl = $approvalUrl;
        $this->status = $status;
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
