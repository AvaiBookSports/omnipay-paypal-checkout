<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use Omnipay\PayPalCheckout\Message\PurchaseRequest;
use Omnipay\PayPalCheckout\Message\RedirectResponse;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Http\HttpRequest;
use PaypalServerSdkLib\Http\HttpResponse;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\LinkDescription;
use PaypalServerSdkLib\Models\Order;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

final class PurchaseRequestTest extends TestCase
{
    private PurchaseRequest $purchaseRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseRequest = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->purchaseRequest->setClientId('test-id');
        $this->purchaseRequest->setClientSecret('test-secret');
        $this->purchaseRequest->setAmount('10.00');
        $this->purchaseRequest->setCurrency('EUR');
        $this->purchaseRequest->setReturnUrl('https://example.com/return');
        $this->purchaseRequest->setCancelUrl('https://example.com/cancel');
    }

    public function testGetData(): void
    {
        $data = $this->purchaseRequest->getData();

        self::assertSame(CheckoutPaymentIntent::CAPTURE, $data['intent']);
        self::assertSame('10.00', $data['amount']);
        self::assertSame('EUR', $data['currency']);
        self::assertSame('https://example.com/return', $data['returnUrl']);
        self::assertSame('https://example.com/cancel', $data['cancelUrl']);
    }

    public function testGetDataWithOptionalFields(): void
    {
        $this->purchaseRequest->setDescription('Test payment');
        $this->purchaseRequest->setTransactionId('INV-001');
        $this->purchaseRequest->setBrandName('Test Brand');
        $this->purchaseRequest->setNotifyUrl('https://example.com/notify');

        $data = $this->purchaseRequest->getData();

        self::assertSame('Test payment', $data['description']);
        self::assertSame('INV-001', $data['transactionId']);
        self::assertSame('Test Brand', $data['brandName']);
        self::assertSame('https://example.com/notify', $data['notifyUrl']);
    }

    public function testGetDataValidatesAmount(): void
    {
        $this->purchaseRequest->setAmount(null);

        $this->expectException(InvalidRequestException::class);
        $this->purchaseRequest->getData();
    }

    public function testGetDataValidatesCurrency(): void
    {
        $purchaseRequest = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $purchaseRequest->setAmount('10.00');
        $purchaseRequest->setReturnUrl('https://example.com/return');
        $purchaseRequest->setCancelUrl('https://example.com/cancel');

        $this->expectException(InvalidRequestException::class);
        $purchaseRequest->getData();
    }

    public function testGetDataValidatesReturnUrl(): void
    {
        $this->purchaseRequest->setReturnUrl(null);

        $this->expectException(InvalidRequestException::class);
        $this->purchaseRequest->getData();
    }

    public function testGetDataValidatesCancelUrl(): void
    {
        $this->purchaseRequest->setCancelUrl(null);

        $this->expectException(InvalidRequestException::class);
        $this->purchaseRequest->getData();
    }

    public function testSendDataSuccess(): void
    {
        $order = new Order();
        $order->setId('ORDER-123');
        $order->setStatus('CREATED');
        $order->setLinks([
            new LinkDescription('https://api.sandbox.paypal.com/v2/checkout/orders/ORDER-123', 'self'),
            new LinkDescription('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123', 'approve'),
        ]);

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($order);

        $ordersController = $this->createMock(OrdersController::class);
        $ordersController->method('createOrder')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->purchaseRequest, $sdkClient);

        $response = $this->purchaseRequest->sendData($this->purchaseRequest->getData());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertTrue($response->isRedirect());
        self::assertFalse($response->isSuccessful());
        self::assertSame('ORDER-123', $response->getTransactionReference());
        self::assertSame('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123', $response->getRedirectUrl());
        self::assertSame('CREATED', $response->getCode());
    }

    public function testSendDataApiError(): void
    {
        $ordersController = $this->createMock(OrdersController::class);
        $ordersController
            ->method('createOrder')
            ->willThrowException(
                new ErrorException(
                    'INVALID_REQUEST',
                    new HttpRequest('POST'),
                    new HttpResponse(400, [], ''),
                    'INVALID_REQUEST',
                    'INVALID_REQUEST',
                    'debug-id',
                ),
            );

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->purchaseRequest, $sdkClient);

        $response = $this->purchaseRequest->sendData($this->purchaseRequest->getData());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertFalse($response->isSuccessful());
        self::assertSame('INVALID_REQUEST', $response->getMessage());
    }
}
