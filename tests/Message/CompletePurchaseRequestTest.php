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

final class CompletePurchaseRequestTest extends TestCase
{
    private CompletePurchaseRequest $completePurchaseRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->completePurchaseRequest = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->completePurchaseRequest->setClientId('test-id');
        $this->completePurchaseRequest->setClientSecret('test-secret');
        $this->completePurchaseRequest->setTransactionReference('ORDER-123');
    }

    public function testGetData(): void
    {
        $data = $this->completePurchaseRequest->getData();

        self::assertSame('ORDER-123', $data['orderId']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $completePurchaseRequest = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $completePurchaseRequest->getData();
    }

    public function testSendDataSuccess(): void
    {
        $capturedPayment = new CapturedPayment();
        $capturedPayment->setId('CAP-789');

        $paymentCollection = new PaymentCollection();
        $paymentCollection->setCaptures([$capturedPayment]);

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setPayments($paymentCollection);
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

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->completePurchaseRequest, $sdkClient);

        $response = $this->completePurchaseRequest->sendData($this->completePurchaseRequest->getData());

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isSuccessful());
        self::assertSame('COMPLETED', $response->getStatus());
        self::assertSame('CAP-789', $response->getTransactionReference());
        self::assertSame('INV-001', $response->getTransactionId());
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

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->completePurchaseRequest, $sdkClient);

        $response = $this->completePurchaseRequest->sendData($this->completePurchaseRequest->getData());

        self::assertSame('ORDER-123', $response->getTransactionReference());
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

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->completePurchaseRequest, $sdkClient);

        $response = $this->completePurchaseRequest->sendData($this->completePurchaseRequest->getData());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertFalse($response->isSuccessful());
    }
}
