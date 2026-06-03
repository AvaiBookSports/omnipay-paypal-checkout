<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use PHPUnit\Framework\TestCase;

class ErrorResponseTest extends TestCase
{
    private ErrorResponse $response;

    protected function setUp(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $this->response = new ErrorResponse($mockRequest, 'Something went wrong', 'INVALID_REQUEST');
    }

    public function testIsNeverSuccessful(): void
    {
        $this->assertFalse($this->response->isSuccessful());
    }

    public function testGetMessage(): void
    {
        $this->assertSame('Something went wrong', $this->response->getMessage());
    }

    public function testGetCode(): void
    {
        $this->assertSame('INVALID_REQUEST', $this->response->getCode());
    }

    public function testGetTransactionReferenceIsNull(): void
    {
        $this->assertNull($this->response->getTransactionReference());
    }

    public function testGetDataIsEmptyArray(): void
    {
        $this->assertSame([], $this->response->getData());
    }
}
