<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\AuthorizeRequest;
use Omnipay\PayPalCheckout\Message\RedirectResponse;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Models\CheckoutPaymentIntent;
use PaypalServerSdkLib\Models\LinkDescription;
use PaypalServerSdkLib\Models\Order;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

class AuthorizeRequestTest extends TestCase
{
    private AuthorizeRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new AuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->setClientId('test-id');
        $this->request->setClientSecret('test-secret');
        $this->request->setAmount('25.00');
        $this->request->setCurrency('USD');
        $this->request->setReturnUrl('https://example.com/return');
        $this->request->setCancelUrl('https://example.com/cancel');
    }

    public function testIntentIsAuthorize(): void
    {
        $data = $this->request->getData();

        $this->assertSame(CheckoutPaymentIntent::AUTHORIZE, $data['intent']);
    }

    public function testSendDataSuccess(): void
    {
        $order = new Order();
        $order->setId('ORDER-AUTH-456');
        $order->setStatus('CREATED');
        $order->setLinks([
            new LinkDescription('https://www.sandbox.paypal.com/checkoutnow?token=ORDER-AUTH-456', 'approve'),
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
        $this->assertSame('ORDER-AUTH-456', $response->getTransactionReference());
    }
}
