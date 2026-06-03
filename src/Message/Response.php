<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

class Response extends AbstractResponse
{
    private string $status;
    private ?string $transactionReference;
    private ?string $transactionId;
    private ?string $message;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        RequestInterface $request,
        array $data,
        string $status,
        ?string $transactionReference = null,
        ?string $transactionId = null,
        ?string $message = null,
    ) {
        parent::__construct($request, $data);
        $this->status = $status;
        $this->transactionReference = $transactionReference;
        $this->transactionId = $transactionId;
        $this->message = $message;
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
