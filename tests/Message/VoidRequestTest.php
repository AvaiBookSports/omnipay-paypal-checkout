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

class VoidRequestTest extends TestCase
{
    private VoidRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->setClientId('test-id');
        $this->request->setClientSecret('test-secret');
        $this->request->setTransactionReference('AUTH-999');
    }

    public function testGetData(): void
    {
        $data = $this->request->getData();

        $this->assertSame('AUTH-999', $data['authorizationId']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $request = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testSendDataWithNullResult(): void
    {
        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn(null);

        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController->method('voidPayment')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getPaymentsController')->willReturn($paymentsController);

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('VOIDED', $response->getStatus());
        $this->assertSame('AUTH-999', $response->getTransactionReference());
        $this->assertSame([], $response->getData());
    }

    public function testSendDataApiError(): void
    {
        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController->method('voidPayment')->willThrowException(
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

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
    }
}
