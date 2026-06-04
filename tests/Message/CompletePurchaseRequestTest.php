<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\CompletePurchaseRequest;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use Omnipay\PayPalCheckout\Message\Response;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Http\HttpRequest;
use PaypalServerSdkLib\Http\HttpResponse;
use PaypalServerSdkLib\Models\CapturedPayment;
use PaypalServerSdkLib\Models\Order;
use PaypalServerSdkLib\Models\PaymentCollection;
use PaypalServerSdkLib\Models\PurchaseUnit;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

class CompletePurchaseRequestTest extends TestCase
{
    private CompletePurchaseRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->setClientId('test-id');
        $this->request->setClientSecret('test-secret');
        $this->request->setTransactionReference('ORDER-123');
    }

    public function testGetData(): void
    {
        $data = $this->request->getData();

        $this->assertSame('ORDER-123', $data['orderId']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testSendDataSuccess(): void
    {
        $capture = new CapturedPayment();
        $capture->setId('CAP-789');

        $payments = new PaymentCollection();
        $payments->setCaptures([$capture]);

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setPayments($payments);
        $purchaseUnit->setInvoiceId('INV-001');

        $order = new Order();
        $order->setId('ORDER-123');
        $order->setStatus('COMPLETED');
        $order->setPurchaseUnits([$purchaseUnit]);

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($order);

        $ordersController = $this->createMock(OrdersController::class);
        $ordersController->method('captureOrder')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('COMPLETED', $response->getStatus());
        $this->assertSame('CAP-789', $response->getTransactionReference());
        $this->assertSame('INV-001', $response->getTransactionId());
    }

    public function testSendDataFallsBackToOrderIdWhenNoCaptureId(): void
    {
        $order = new Order();
        $order->setId('ORDER-123');
        $order->setStatus('COMPLETED');
        $order->setPurchaseUnits([new PurchaseUnit()]);

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($order);

        $ordersController = $this->createMock(OrdersController::class);
        $ordersController->method('captureOrder')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertSame('ORDER-123', $response->getTransactionReference());
    }

    public function testSendDataApiError(): void
    {
        $ordersController = $this->createMock(OrdersController::class);
        $ordersController
            ->method('captureOrder')
            ->willThrowException(
                new ErrorException(
                    'ORDER_NOT_APPROVED',
                    new HttpRequest('POST'),
                    new HttpResponse(422, [], ''),
                    'ORDER_NOT_APPROVED',
                    'ORDER_NOT_APPROVED',
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
    }
}
