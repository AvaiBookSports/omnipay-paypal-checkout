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

class RefundRequestTest extends TestCase
{
    private RefundRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->setClientId('test-id');
        $this->request->setClientSecret('test-secret');
        $this->request->setTransactionReference('CAP-123');
    }

    public function testGetData(): void
    {
        $data = $this->request->getData();

        $this->assertSame('CAP-123', $data['captureId']);
        $this->assertNull($data['amount']);
        $this->assertNull($data['currency']);
    }

    public function testGetDataWithPartialAmount(): void
    {
        $this->request->setAmount('5.00');
        $this->request->setCurrency('EUR');

        $data = $this->request->getData();

        $this->assertSame('5.00', $data['amount']);
        $this->assertSame('EUR', $data['currency']);
    }

    public function testGetDataValidatesTransactionReference(): void
    {
        $request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->expectException(InvalidRequestException::class);
        $request->getData();
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

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('COMPLETED', $response->getStatus());
        $this->assertSame('REF-789', $response->getTransactionReference());
        $this->assertSame('INV-004', $response->getTransactionId());
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

        $reflection = new ReflectionProperty(AbstractRequest::class, 'sdkClient');
        $reflection->setValue($this->request, $sdkClient);

        $response = $this->request->sendData($this->request->getData());

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertFalse($response->isSuccessful());
    }
}
