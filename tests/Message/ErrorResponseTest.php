<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use PHPUnit\Framework\TestCase;

final class ErrorResponseTest extends TestCase
{
    private ErrorResponse $errorResponse;

    protected function setUp(): void
    {
        $this->errorResponse = new ErrorResponse(
            $this->createStub(RequestInterface::class),
            'Something went wrong',
            'INVALID_REQUEST',
        );
    }

    public function testIsNeverSuccessful(): void
    {
        self::assertFalse($this->errorResponse->isSuccessful());
    }

    public function testGetMessage(): void
    {
        self::assertSame('Something went wrong', $this->errorResponse->getMessage());
    }

    public function testGetCode(): void
    {
        self::assertSame('INVALID_REQUEST', $this->errorResponse->getCode());
    }

    public function testGetTransactionReferenceIsNull(): void
    {
        self::assertNull($this->errorResponse->getTransactionReference());
    }

    public function testGetDataIsEmptyArray(): void
    {
        self::assertSame([], $this->errorResponse->getData());
    }
}
