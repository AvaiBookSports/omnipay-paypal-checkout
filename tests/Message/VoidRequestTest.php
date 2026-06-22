<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use Omnipay\PayPalCheckout\Message\Response;
use Omnipay\PayPalCheckout\Message\VoidRequest;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\PaymentsController;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Http\HttpRequest;
use PaypalServerSdkLib\Http\HttpResponse;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

final class VoidRequestTest extends TestCase
{
    private VoidRequest $voidRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voidRequest = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->voidRequest->setClientId('test-id');
        $this->voidRequest->setClientSecret('test-secret');
        $this->voidRequest->setTransactionReference('AUTH-999');
    }

    public function testGetData(): void
    {
        $data = $this->voidRequest->getData();

        self::assertSame('AUTH-999', $data['authorizationId']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $voidRequest = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $voidRequest->getData();
    }

    public function testSendDataWithNullResult(): void
    {
        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn(null);

        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController->method('voidPayment')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getPaymentsController')->willReturn($paymentsController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->voidRequest, $sdkClient);

        $response = $this->voidRequest->sendData($this->voidRequest->getData());

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isSuccessful());
        self::assertSame('VOIDED', $response->getStatus());
        self::assertSame('AUTH-999', $response->getTransactionReference());
        self::assertSame([], $response->getData());
    }

    public function testSendDataApiError(): void
    {
        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController
            ->method('voidPayment')
            ->willThrowException(
                new ErrorException(
                    'AUTHORIZATION_ALREADY_CAPTURED',
                    new HttpRequest('POST'),
                    new HttpResponse(422, [], ''),
                    'AUTHORIZATION_ALREADY_CAPTURED',
                    'AUTHORIZATION_ALREADY_CAPTURED',
                    'debug-id',
                ),
            );

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getPaymentsController')->willReturn($paymentsController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->voidRequest, $sdkClient);

        $response = $this->voidRequest->sendData($this->voidRequest->getData());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertFalse($response->isSuccessful());
    }
}
