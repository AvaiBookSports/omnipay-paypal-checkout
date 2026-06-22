<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\CompleteAuthorizeRequest;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use Omnipay\PayPalCheckout\Message\Response;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\OrdersController;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Http\HttpRequest;
use PaypalServerSdkLib\Http\HttpResponse;
use PaypalServerSdkLib\Models\AuthorizationWithAdditionalData;
use PaypalServerSdkLib\Models\OrderAuthorizeResponse;
use PaypalServerSdkLib\Models\PaymentCollection;
use PaypalServerSdkLib\Models\PurchaseUnit;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

final class CompleteAuthorizeRequestTest extends TestCase
{
    private CompleteAuthorizeRequest $completeAuthorizeRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->completeAuthorizeRequest = new CompleteAuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->completeAuthorizeRequest->setClientId('test-id');
        $this->completeAuthorizeRequest->setClientSecret('test-secret');
        $this->completeAuthorizeRequest->setTransactionReference('ORDER-456');
    }

    public function testGetData(): void
    {
        $data = $this->completeAuthorizeRequest->getData();

        self::assertSame('ORDER-456', $data['orderId']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $completeAuthorizeRequest = new CompleteAuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $completeAuthorizeRequest->getData();
    }

    public function testSendDataSuccess(): void
    {
        $authorizationWithAdditionalData = new AuthorizationWithAdditionalData();
        $authorizationWithAdditionalData->setId('AUTH-789');

        $paymentCollection = new PaymentCollection();
        $paymentCollection->setAuthorizations([$authorizationWithAdditionalData]);

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setPayments($paymentCollection);
        $purchaseUnit->setInvoiceId('INV-002');

        $orderAuthorizeResponse = new OrderAuthorizeResponse();
        $orderAuthorizeResponse->setId('ORDER-456');
        $orderAuthorizeResponse->setStatus('COMPLETED');
        $orderAuthorizeResponse->setPurchaseUnits([$purchaseUnit]);

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($orderAuthorizeResponse);

        $ordersController = $this->createMock(OrdersController::class);
        $ordersController->method('authorizeOrder')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->completeAuthorizeRequest, $sdkClient);

        $response = $this->completeAuthorizeRequest->sendData($this->completeAuthorizeRequest->getData());

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isSuccessful());
        self::assertSame('AUTH-789', $response->getTransactionReference());
        self::assertSame('INV-002', $response->getTransactionId());
    }

    public function testSendDataFallsBackToOrderIdWhenNoAuthorizationId(): void
    {
        $orderAuthorizeResponse = new OrderAuthorizeResponse();
        $orderAuthorizeResponse->setId('ORDER-456');
        $orderAuthorizeResponse->setStatus('COMPLETED');
        $orderAuthorizeResponse->setPurchaseUnits([new PurchaseUnit()]);

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($orderAuthorizeResponse);

        $ordersController = $this->createMock(OrdersController::class);
        $ordersController->method('authorizeOrder')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getOrdersController')->willReturn($ordersController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->completeAuthorizeRequest, $sdkClient);

        $response = $this->completeAuthorizeRequest->sendData($this->completeAuthorizeRequest->getData());

        self::assertSame('ORDER-456', $response->getTransactionReference());
    }

    public function testSendDataApiError(): void
    {
        $ordersController = $this->createMock(OrdersController::class);
        $ordersController
            ->method('authorizeOrder')
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
        $reflectionProperty->setValue($this->completeAuthorizeRequest, $sdkClient);

        $response = $this->completeAuthorizeRequest->sendData($this->completeAuthorizeRequest->getData());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertFalse($response->isSuccessful());
    }
}
