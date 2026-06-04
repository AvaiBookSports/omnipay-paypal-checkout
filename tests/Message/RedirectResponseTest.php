<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayPalCheckout\Message\RedirectResponse;
use PHPUnit\Framework\TestCase;

final class RedirectResponseTest extends TestCase
{
    private RedirectResponse $redirectResponse;

    protected function setUp(): void
    {
        $this->redirectResponse = new RedirectResponse(
            $this->createStub(RequestInterface::class),
            ['id' => 'ORDER-123'],
            'ORDER-123',
            'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123',
            'CREATED',
        );
    }

    public function testIsNotSuccessful(): void
    {
        self::assertFalse($this->redirectResponse->isSuccessful());
    }

    public function testIsRedirect(): void
    {
        self::assertTrue($this->redirectResponse->isRedirect());
    }

    public function testGetRedirectUrl(): void
    {
        self::assertSame(
            'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123',
            $this->redirectResponse->getRedirectUrl(),
        );
    }

    public function testGetRedirectMethod(): void
    {
        self::assertSame('GET', $this->redirectResponse->getRedirectMethod());
    }

    public function testGetRedirectData(): void
    {
        self::assertSame([], $this->redirectResponse->getRedirectData());
    }

    public function testGetTransactionReference(): void
    {
        self::assertSame('ORDER-123', $this->redirectResponse->getTransactionReference());
    }

    public function testGetCode(): void
    {
        self::assertSame('CREATED', $this->redirectResponse->getCode());
    }

    public function testGetMessage(): void
    {
        self::assertNull($this->redirectResponse->getMessage());
    }

    public function testGetData(): void
    {
        self::assertSame(['id' => 'ORDER-123'], $this->redirectResponse->getData());
    }
}
