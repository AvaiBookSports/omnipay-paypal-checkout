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

class PurchaseRequestTest extends TestCase
{
    private PurchaseRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->setClientId('test-id');
        $this->request->setClientSecret('test-secret');
        $this->request->setAmount('10.00');
        $this->request->setCurrency('EUR');
        $this->request->setReturnUrl('https://example.com/return');
        $this->request->setCancelUrl('https://example.com/cancel');
    }

    public function testGetData(): void
    {
        $data = $this->request->getData();

        $this->assertSame(CheckoutPaymentIntent::CAPTURE, $data['intent']);
        $this->assertSame('10.00', $data['amount']);
        $this->assertSame('EUR', $data['currency']);
        $this->assertSame('https://example.com/return', $data['returnUrl']);
        $this->assertSame('https://example.com/cancel', $data['cancelUrl']);
    }

    public function testGetDataWithOptionalFields(): void
    {
        $this->request->setDescription('Test payment');
        $this->request->setTransactionId('INV-001');
        $this->request->setBrandName('Test Brand');
        $this->request->setNotifyUrl('https://example.com/notify');

        $data = $this->request->getData();

        $this->assertSame('Test payment', $data['description']);
        $this->assertSame('INV-001', $data['transactionId']);
        $this->assertSame('Test Brand', $data['brandName']);
        $this->assertSame('https://example.com/notify', $data['notifyUrl']);
    }

    public function testGetDataValidatesAmount(): void
    {
        $this->request->setAmount(null);

        $this->expectException(InvalidRequestException::class);
        $this->request->getData();
    }

    public function testGetDataValidatesCurrency(): void
    {
        $request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $request->setAmount('10.00');
        $request->setReturnUrl('https://example.com/return');
        $request->setCancelUrl('https://example.com/cancel');

        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testGetDataValidatesReturnUrl(): void
    {
        $this->request->setReturnUrl(null);

        $this->expectException(InvalidRequestException::class);
        $this->request->getData();
    }

    public function testGetDataValidatesCancelUrl(): void
    {
        $this->request->setCancelUrl(null);

        $this->expectException(InvalidRequestException::class);
        $this->request->getData();
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

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirect());
        $this->assertFalse($response->isSuccessful());
        $this->assertSame('ORDER-123', $response->getTransactionReference());
        $this->assertSame('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-123', $response->getRedirectUrl());
        $this->assertSame('CREATED', $response->getCode());
    }

    public function testSendDataApiError(): void
    {
        $ordersController = $this->createMock(OrdersController::class);
        $ordersController->method('createOrder')->willThrowException(
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

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
        $this->assertSame('INVALID_REQUEST', $response->getMessage());
    }
}
