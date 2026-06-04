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

final class AuthorizeRequestTest extends TestCase
{
    private AuthorizeRequest $authorizeRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorizeRequest = new AuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->authorizeRequest->setClientId('test-id');
        $this->authorizeRequest->setClientSecret('test-secret');
        $this->authorizeRequest->setAmount('25.00');
        $this->authorizeRequest->setCurrency('USD');
        $this->authorizeRequest->setReturnUrl('https://example.com/return');
        $this->authorizeRequest->setCancelUrl('https://example.com/cancel');
    }

    public function testIntentIsAuthorize(): void
    {
        $data = $this->authorizeRequest->getData();

        self::assertSame(CheckoutPaymentIntent::AUTHORIZE, $data['intent']);
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

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflectionProperty->setValue($this->authorizeRequest, $sdkClient);

        $response = $this->authorizeRequest->sendData($this->authorizeRequest->getData());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('ORDER-AUTH-456', $response->getTransactionReference());
    }
}
