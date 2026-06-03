<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayPalCheckout\Message\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private RequestInterface $mockRequest;

    protected function setUp(): void
    {
        $this->mockRequest = $this->createMock(RequestInterface::class);
    }

    public function testSuccessfulStatuses(): void
    {
        foreach (['COMPLETED', 'APPROVED', 'VOIDED', 'CREATED'] as $status) {
            $response = new Response($this->mockRequest, [], $status);
            $this->assertTrue($response->isSuccessful(), "Status $status should be successful");
        }
    }

    public function testUnsuccessfulStatuses(): void
    {
        foreach (['PENDING', 'FAILED', 'UNKNOWN', 'DENIED'] as $status) {
            $response = new Response($this->mockRequest, [], $status);
            $this->assertFalse($response->isSuccessful(), "Status $status should not be successful");
        }
    }

    public function testGetStatus(): void
    {
        $response = new Response($this->mockRequest, [], 'COMPLETED');
        $this->assertSame('COMPLETED', $response->getStatus());
    }

    public function testGetCode(): void
    {
        $response = new Response($this->mockRequest, [], 'APPROVED');
        $this->assertSame('APPROVED', $response->getCode());
    }

    public function testGetTransactionReference(): void
    {
        $response = new Response($this->mockRequest, [], 'COMPLETED', 'ref-123');
        $this->assertSame('ref-123', $response->getTransactionReference());
    }

    public function testGetTransactionReferenceDefaultsToNull(): void
    {
        $response = new Response($this->mockRequest, [], 'COMPLETED');
        $this->assertNull($response->getTransactionReference());
    }

    public function testGetTransactionId(): void
    {
        $response = new Response($this->mockRequest, [], 'COMPLETED', 'ref', 'tx-456');
        $this->assertSame('tx-456', $response->getTransactionId());
    }

    public function testGetTransactionIdDefaultsToNull(): void
    {
        $response = new Response($this->mockRequest, [], 'COMPLETED');
        $this->assertNull($response->getTransactionId());
    }

    public function testGetMessage(): void
    {
        $response = new Response($this->mockRequest, [], 'COMPLETED', null, null, 'Some message');
        $this->assertSame('Some message', $response->getMessage());
    }

    public function testGetMessageDefaultsToNull(): void
    {
        $response = new Response($this->mockRequest, [], 'COMPLETED');
        $this->assertNull($response->getMessage());
    }

    public function testGetData(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $response = new Response($this->mockRequest, $data, 'COMPLETED');
        $this->assertSame($data, $response->getData());
    }
}
