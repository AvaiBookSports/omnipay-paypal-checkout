<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests;

use Omnipay\PayPalCheckout\Gateway;
use Omnipay\PayPalCheckout\Message\AuthorizeRequest;
use Omnipay\PayPalCheckout\Message\CaptureRequest;
use Omnipay\PayPalCheckout\Message\CompleteAuthorizeRequest;
use Omnipay\PayPalCheckout\Message\CompletePurchaseRequest;
use Omnipay\PayPalCheckout\Message\FetchTransactionRequest;
use Omnipay\PayPalCheckout\Message\PurchaseRequest;
use Omnipay\PayPalCheckout\Message\RefundRequest;
use Omnipay\PayPalCheckout\Message\VoidRequest;
use Omnipay\Tests\GatewayTestCase;

final class GatewayTest extends GatewayTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setClientId('test-client-id');
        $this->gateway->setClientSecret('test-client-secret');
    }

    public function testGatewayName(): void
    {
        self::assertSame('PayPal Checkout', $this->gateway->getName());
    }

    public function testGatewayShortName(): void
    {
        self::assertSame('PayPalCheckout', $this->gateway->getShortName());
    }

    public function testDefaultParameters(): void
    {
        $defaults = $this->gateway->getDefaultParameters();

        self::assertArrayHasKey('clientId', $defaults);
        self::assertArrayHasKey('clientSecret', $defaults);
        self::assertArrayHasKey('testMode', $defaults);
        self::assertArrayHasKey('brandName', $defaults);
    }

    public function testClientId(): void
    {
        $this->gateway->setClientId('my-id');
        self::assertSame('my-id', $this->gateway->getClientId());
    }

    public function testClientSecret(): void
    {
        $this->gateway->setClientSecret('my-secret');
        self::assertSame('my-secret', $this->gateway->getClientSecret());
    }

    public function testBrandName(): void
    {
        $this->gateway->setBrandName('My Brand');
        self::assertSame('My Brand', $this->gateway->getBrandName());
    }

    public function testBrandNameNullable(): void
    {
        $this->gateway->setBrandName(null);
        self::assertNull($this->gateway->getBrandName());
    }

    public function testPurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->purchase();
        self::assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function testAuthorizeReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->authorize();
        self::assertInstanceOf(AuthorizeRequest::class, $request);
    }

    public function testCaptureReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->capture();
        self::assertInstanceOf(CaptureRequest::class, $request);
    }

    public function testCompletePurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->completePurchase();
        self::assertInstanceOf(CompletePurchaseRequest::class, $request);
    }

    public function testCompleteAuthorizeReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->completeAuthorize();
        self::assertInstanceOf(CompleteAuthorizeRequest::class, $request);
    }

    public function testRefundReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->refund();
        self::assertInstanceOf(RefundRequest::class, $request);
    }

    public function testVoidReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->void();
        self::assertInstanceOf(VoidRequest::class, $request);
    }

    public function testFetchTransactionReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->fetchTransaction();
        self::assertInstanceOf(FetchTransactionRequest::class, $request);
    }

    public function testParametersPropagateToRequest(): void
    {
        $this->gateway->setClientId('propagated-id');
        $this->gateway->setClientSecret('propagated-secret');
        $this->gateway->setBrandName('Propagated Brand');

        $request = $this->gateway->purchase();

        self::assertSame('propagated-id', $request->getClientId());
        self::assertSame('propagated-secret', $request->getClientSecret());
        self::assertSame('Propagated Brand', $request->getBrandName());
    }
}
