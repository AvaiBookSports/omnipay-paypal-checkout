<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

class ErrorResponse extends AbstractResponse
{
    public function __construct(
        RequestInterface $request,
        private readonly string $errorMessage,
        private readonly string $errorCode,
    ) {
        parent::__construct($request, []);
    }

    public function isSuccessful(): bool
    {
        return false;
    }

    public function getMessage(): string
    {
        return $this->errorMessage;
    }

    public function getCode(): string
    {
        return $this->errorCode;
    }

    public function getTransactionReference(): ?string
    {
        return null;
    }
}
