<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayPalCheckout\Message\RedirectResponse;
use PHPUnit\Framework\TestCase;

class RedirectResponseTest extends TestCase
{
    private RedirectResponse $response;

    protected function setUp(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $this->response = new RedirectResponse(
            $mockRequest,
            ['id' => 'ORDER-123'],
            'ORDER-123',
            'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123',
            'CREATED',
        );
    }

    public function testIsNotSuccessful(): void
    {
        $this->assertFalse($this->response->isSuccessful());
    }

    public function testIsRedirect(): void
    {
        $this->assertTrue($this->response->isRedirect());
    }

    public function testGetRedirectUrl(): void
    {
        $this->assertSame(
            'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123',
            $this->response->getRedirectUrl(),
        );
    }

    public function testGetRedirectMethod(): void
    {
        $this->assertSame('GET', $this->response->getRedirectMethod());
    }

    public function testGetRedirectData(): void
    {
        $this->assertSame([], $this->response->getRedirectData());
    }

    public function testGetTransactionReference(): void
    {
        $this->assertSame('ORDER-123', $this->response->getTransactionReference());
    }

    public function testGetCode(): void
    {
        $this->assertSame('CREATED', $this->response->getCode());
    }

    public function testGetMessage(): void
    {
        $this->assertNull($this->response->getMessage());
    }

    public function testGetData(): void
    {
        $this->assertSame(['id' => 'ORDER-123'], $this->response->getData());
    }
}
