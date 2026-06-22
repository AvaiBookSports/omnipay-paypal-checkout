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

final class CaptureRequestTest extends TestCase
{
    private CaptureRequest $captureRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->captureRequest = new CaptureRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->captureRequest->setClientId('test-id');
        $this->captureRequest->setClientSecret('test-secret');
        $this->captureRequest->setTransactionReference('AUTH-123');
    }

    public function testGetData(): void
    {
        $data = $this->captureRequest->getData();

        self::assertSame('AUTH-123', $data['authorizationId']);
        self::assertNull($data['amount']);
        self::assertNull($data['currency']);
    }

    public function testGetDataWithAmount(): void
    {
        $this->captureRequest->setAmount('50.00');
        $this->captureRequest->setCurrency('EUR');

        $data = $this->captureRequest->getData();

        self::assertSame('50.00', $data['amount']);
        self::assertSame('EUR', $data['currency']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $captureRequest = new CaptureRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $captureRequest->getData();
    }

    public function testSendDataSuccess(): void
    {
        $capturedPayment = new CapturedPayment();
        $capturedPayment->setId('CAP-456');
        $capturedPayment->setStatus('COMPLETED');
        $capturedPayment->setInvoiceId('INV-003');

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($capturedPayment);

        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController->method('captureAuthorizedPayment')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getPaymentsController')->willReturn($paymentsController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->captureRequest, $sdkClient);

        $response = $this->captureRequest->sendData($this->captureRequest->getData());

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isSuccessful());
        self::assertSame('COMPLETED', $response->getStatus());
        self::assertSame('CAP-456', $response->getTransactionReference());
        self::assertSame('INV-003', $response->getTransactionId());
    }

    public function testSendDataApiError(): void
    {
        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController
            ->method('captureAuthorizedPayment')
            ->willThrowException(
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

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'paypalServerSdkClient');
        $reflectionProperty->setValue($this->captureRequest, $sdkClient);

        $response = $this->captureRequest->sendData($this->captureRequest->getData());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertFalse($response->isSuccessful());
    }
}
