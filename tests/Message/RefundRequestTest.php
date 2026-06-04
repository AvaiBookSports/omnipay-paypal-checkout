<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\PayPalCheckout\Message\AbstractRequest;
use Omnipay\PayPalCheckout\Message\ErrorResponse;
use Omnipay\PayPalCheckout\Message\RefundRequest;
use Omnipay\PayPalCheckout\Message\Response;
use Omnipay\Tests\TestCase;
use PaypalServerSdkLib\Controllers\PaymentsController;
use PaypalServerSdkLib\Exceptions\ErrorException;
use PaypalServerSdkLib\Http\ApiResponse;
use PaypalServerSdkLib\Http\HttpRequest;
use PaypalServerSdkLib\Http\HttpResponse;
use PaypalServerSdkLib\Models\Refund;
use PaypalServerSdkLib\PaypalServerSdkClient;
use ReflectionProperty;

final class RefundRequestTest extends TestCase
{
    private RefundRequest $refundRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refundRequest = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->refundRequest->setClientId('test-id');
        $this->refundRequest->setClientSecret('test-secret');
        $this->refundRequest->setTransactionReference('CAP-123');
    }

    public function testGetData(): void
    {
        $data = $this->refundRequest->getData();

        self::assertSame('CAP-123', $data['captureId']);
        self::assertNull($data['amount']);
        self::assertNull($data['currency']);
    }

    public function testGetDataWithPartialAmount(): void
    {
        $this->refundRequest->setAmount('5.00');
        $this->refundRequest->setCurrency('EUR');

        $data = $this->refundRequest->getData();

        self::assertSame('5.00', $data['amount']);
        self::assertSame('EUR', $data['currency']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $refundRequest = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $refundRequest->getData();
    }

    public function testSendDataSuccess(): void
    {
        $refund = new Refund();
        $refund->setId('REF-789');
        $refund->setStatus('COMPLETED');
        $refund->setInvoiceId('INV-004');

        $apiResponse = $this->createMock(ApiResponse::class);
        $apiResponse->method('getResult')->willReturn($refund);

        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController->method('refundCapturedPayment')->willReturn($apiResponse);

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getPaymentsController')->willReturn($paymentsController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflectionProperty->setValue($this->refundRequest, $sdkClient);

        $response = $this->refundRequest->sendData($this->refundRequest->getData());

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isSuccessful());
        self::assertSame('COMPLETED', $response->getStatus());
        self::assertSame('REF-789', $response->getTransactionReference());
        self::assertSame('INV-004', $response->getTransactionId());
    }

    public function testSendDataApiError(): void
    {
        $paymentsController = $this->createMock(PaymentsController::class);
        $paymentsController
            ->method('refundCapturedPayment')
            ->willThrowException(
                new ErrorException(
                    'CAPTURE_FULLY_REFUNDED',
                    new HttpRequest('POST'),
                    new HttpResponse(422, [], ''),
                    'CAPTURE_FULLY_REFUNDED',
                    'CAPTURE_FULLY_REFUNDED',
                    'debug-id',
                ),
            );

        $sdkClient = $this->createMock(PaypalServerSdkClient::class);
        $sdkClient->method('getPaymentsController')->willReturn($paymentsController);

        $reflectionProperty = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflectionProperty->setValue($this->refundRequest, $sdkClient);

        $response = $this->refundRequest->sendData($this->refundRequest->getData());

        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertFalse($response->isSuccessful());
    }
}
