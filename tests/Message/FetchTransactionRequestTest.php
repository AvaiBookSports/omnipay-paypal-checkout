<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use Omnipay\PayPalCheckout\Message\FetchTransactionRequest;
use Omnipay\PayPalCheckout\Message\Response;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Http\HttpRequest;
use PaypalServerSdkLib\Http\HttpResponse;
use PaypalServerSdkLib\Models\Order;
use PaypalServerSdkLib\Models\PurchaseUnit;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

final class FetchTransactionRequestTest extends TestCase
{
    private FetchTransactionRequest $fetchTransactionRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fetchTransactionRequest = new FetchTransactionRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->fetchTransactionRequest->setClientId('test-id');
        $this->fetchTransactionRequest->setClientSecret('test-secret');
        $this->fetchTransactionRequest->setTransactionReference('ORDER-789');
    }

    public function testGetData(): void
    {
        $data = $this->fetchTransactionRequest->getData();

        self::assertSame('ORDER-789', $data['orderId']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $fetchTransactionRequest = new FetchTransactionRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $fetchTransactionRequest->getData();
    }

    public function testSendDataSuccess(): void
    {
        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setInvoiceId('INV-005');

        $order = new Order();
        $order->setId('ORDER-789');
        $order->setStatus('COMPLETED');
        $order->setPurchaseUnits([$purchaseUnit]);

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($order);

        $ordersController = $this->createMock(OrdersController::class);
        $ordersController->method('getOrder')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->fetchTransactionRequest, $sdkClient);

        $response = $this->fetchTransactionRequest->sendData($this->fetchTransactionRequest->getData());

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isSuccessful());
        self::assertSame('COMPLETED', $response->getStatus());
        self::assertSame('ORDER-789', $response->getTransactionReference());
        self::assertSame('INV-005', $response->getTransactionId());
    }

    public function testSendDataApiError(): void
    {
        $ordersController = $this->createMock(OrdersController::class);
        $ordersController
            ->method('getOrder')
            ->willThrowException(
                new ErrorException(
                    'RESOURCE_NOT_FOUND',
                    new HttpRequest('GET'),
                    new HttpResponse(404, [], ''),
                    'RESOURCE_NOT_FOUND',
                    'RESOURCE_NOT_FOUND',
                    'debug-id',
                ),
            );

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->fetchTransactionRequest, $sdkClient);

        $response = $this->fetchTransactionRequest->sendData($this->fetchTransactionRequest->getData());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertFalse($response->isSuccessful());
    }
}
