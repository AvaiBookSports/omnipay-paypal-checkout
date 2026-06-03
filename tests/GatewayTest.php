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

class GatewayTest extends GatewayTestCase
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
        $this->assertSame('PayPal Checkout', $this->gateway->getName());
    }

    public function testGatewayShortName(): void
    {
        $this->assertSame('PayPalCheckout', $this->gateway->getShortName());
    }

    public function testDefaultParameters(): void
    {
        $defaults = $this->gateway->getDefaultParameters();

        $this->assertArrayHasKey('clientId', $defaults);
        $this->assertArrayHasKey('clientSecret', $defaults);
        $this->assertArrayHasKey('testMode', $defaults);
        $this->assertArrayHasKey('brandName', $defaults);
    }

    public function testClientId(): void
    {
        $this->gateway->setClientId('my-id');
        $this->assertSame('my-id', $this->gateway->getClientId());
    }

    public function testClientSecret(): void
    {
        $this->gateway->setClientSecret('my-secret');
        $this->assertSame('my-secret', $this->gateway->getClientSecret());
    }

    public function testBrandName(): void
    {
        $this->gateway->setBrandName('My Brand');
        $this->assertSame('My Brand', $this->gateway->getBrandName());
    }

    public function testBrandNameNullable(): void
    {
        $this->gateway->setBrandName(null);
        $this->assertNull($this->gateway->getBrandName());
    }

    public function testPurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->purchase();
        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function testAuthorizeReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->authorize();
        $this->assertInstanceOf(AuthorizeRequest::class, $request);
    }

    public function testCaptureReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->capture();
        $this->assertInstanceOf(CaptureRequest::class, $request);
    }

    public function testCompletePurchaseReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->completePurchase();
        $this->assertInstanceOf(CompletePurchaseRequest::class, $request);
    }

    public function testCompleteAuthorizeReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->completeAuthorize();
        $this->assertInstanceOf(CompleteAuthorizeRequest::class, $request);
    }

    public function testRefundReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->refund();
        $this->assertInstanceOf(RefundRequest::class, $request);
    }

    public function testVoidReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->void();
        $this->assertInstanceOf(VoidRequest::class, $request);
    }

    public function testFetchTransactionReturnsCorrectRequestClass(): void
    {
        $request = $this->gateway->fetchTransaction();
        $this->assertInstanceOf(FetchTransactionRequest::class, $request);
    }

    public function testParametersPropagateToRequest(): void
    {
        $this->gateway->setClientId('propagated-id');
        $this->gateway->setClientSecret('propagated-secret');
        $this->gateway->setBrandName('Propagated Brand');

        $request = $this->gateway->purchase();

        $this->assertSame('propagated-id', $request->getClientId());
        $this->assertSame('propagated-secret', $request->getClientSecret());
        $this->assertSame('Propagated Brand', $request->getBrandName());
    }
}
