<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

class ErrorResponse extends AbstractResponse
{
    private string $errorMessage;
    private string $errorCode;

    public function __construct(RequestInterface $request, string $message, string $code)
    {
        parent::__construct($request, []);
        $this->errorMessage = $message;
        $this->errorCode = $code;
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
