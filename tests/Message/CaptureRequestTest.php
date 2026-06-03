<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\CaptureRequest;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use Omnipay\PayPalCheckout\Message\Response;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\PaymentsController;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Http\HttpRequest;
use PaypalServerSdkLib\Http\HttpResponse;
use PaypalServerSdkLib\Models\CapturedPayment;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

class CaptureRequestTest extends TestCase
{
    private CaptureRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new CaptureRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->setClientId('test-id');
        $this->request->setClientSecret('test-secret');
        $this->request->setTransactionReference('AUTH-123');
    }

    public function testGetData(): void
    {
        $data = $this->request->getData();

        $this->assertSame('AUTH-123', $data['authorizationId']);
        $this->assertNull($data['amount']);
        $this->assertNull($data['currency']);
    }

    public function testGetDataWithAmount(): void
    {
        $this->request->setAmount('50.00');
        $this->request->setCurrency('EUR');

        $data = $this->request->getData();

        $this->assertSame('50.00', $data['amount']);
        $this->assertSame('EUR', $data['currency']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $request = new CaptureRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $request->getData();
    }

    public function testSendDataSuccess(): void
    {
        $capture = new CapturedPayment();
        $capture->setId('CAP-456');
        $capture->setStatus('COMPLETED');
        $capture->setInvoiceId('INV-003');

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($capture);

        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController->method('captureAuthorizedPayment')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getPaymentsController')->willReturn($paymentsController);

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('COMPLETED', $response->getStatus());
        $this->assertSame('CAP-456', $response->getTransactionReference());
        $this->assertSame('INV-003', $response->getTransactionId());
    }

    public function testSendDataApiError(): void
    {
        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController->method('captureAuthorizedPayment')->willThrowException(
            new ErrorException(
                'AUTHORIZATION_VOIDED',
                new HttpRequest('POST'),
                new HttpResponse(422, [], ''),
                'AUTHORIZATION_VOIDED',
                'AUTHORIZATION_VOIDED',
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
