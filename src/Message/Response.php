<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * @see \Omnipay\PayPalCheckout\Tests\Message\ResponseTest
 */
class Response extends AbstractResponse
{
    /**
     * @param array<string, mixed> $data
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        RequestInterface $request,
        array $data,
        private readonly string $status,
        private readonly ?string $transactionReference = null,
        private readonly ?string $transactionId = null,
        private readonly ?string $message = null,
    ) {
        parent::__construct($request, $data);
    }

    public function isSuccessful(): bool
    {
        return \in_array($this->status, ['COMPLETED', 'APPROVED', 'VOIDED', 'CREATED'], true);
    }

    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getCode(): ?string
    {
        return $this->status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
