<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Tests\Message;

use Omnipay\Common\Message\RequestInterface;
use Omnipay\PayPalCheckout\Message\Response;
use PHPUnit\Framework\TestCase;

/**
 * @mago-expect lint:too-many-methods
 */
final class ResponseTest extends TestCase
{
    protected function setUp(): void {}

    public function testSuccessfulStatuses(): void
    {
        foreach (['COMPLETED', 'APPROVED', 'VOIDED', 'CREATED'] as $status) {
            $response = new Response($this->createStub(\Omnipay\Common\Message\RequestInterface::class), [], $status);
            self::assertTrue($response->isSuccessful(), sprintf('Status %s should be successful', $status));
        }
    }

    public function testUnsuccessfulStatuses(): void
    {
        foreach (['PENDING', 'FAILED', 'UNKNOWN', 'DENIED'] as $status) {
            $response = new Response($this->createStub(\Omnipay\Common\Message\RequestInterface::class), [], $status);
            self::assertFalse($response->isSuccessful(), sprintf('Status %s should not be successful', $status));
        }
    }

    public function testGetStatus(): void
    {
        $response = new Response($this->createStub(\Omnipay\Common\Message\RequestInterface::class), [], 'COMPLETED');
        self::assertSame('COMPLETED', $response->getStatus());
    }

    public function testGetCode(): void
    {
        $response = new Response($this->createStub(\Omnipay\Common\Message\RequestInterface::class), [], 'APPROVED');
        self::assertSame('APPROVED', $response->getCode());
    }

    public function testGetTransactionReference(): void
    {
        $response = new Response(
            $this->createStub(\Omnipay\Common\Message\RequestInterface::class),
            [],
            'COMPLETED',
            'ref-123',
        );
        self::assertSame('ref-123', $response->getTransactionReference());
    }

    public function testGetTransactionReferenceDefaultsToNull(): void
    {
        $response = new Response($this->createStub(\Omnipay\Common\Message\RequestInterface::class), [], 'COMPLETED');
        self::assertNull($response->getTransactionReference());
    }

    public function testGetTransactionId(): void
    {
        $response = new Response(
            $this->createStub(\Omnipay\Common\Message\RequestInterface::class),
            [],
            'COMPLETED',
            'ref',
            'tx-456',
        );
        self::assertSame('tx-456', $response->getTransactionId());
    }

    public function testGetTransactionIdDefaultsToNull(): void
    {
        $response = new Response($this->createStub(\Omnipay\Common\Message\RequestInterface::class), [], 'COMPLETED');
        self::assertNull($response->getTransactionId());
    }

    public function testGetMessage(): void
    {
        $response = new Response(
            $this->createStub(\Omnipay\Common\Message\RequestInterface::class),
            [],
            'COMPLETED',
            null,
            null,
            'Some message',
        );
        self::assertSame('Some message', $response->getMessage());
    }

    public function testGetMessageDefaultsToNull(): void
    {
        $response = new Response($this->createStub(\Omnipay\Common\Message\RequestInterface::class), [], 'COMPLETED');
        self::assertNull($response->getMessage());
    }

    public function testGetData(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $response = new Response(
            $this->createStub(\Omnipay\Common\Message\RequestInterface::class),
            $data,
            'COMPLETED',
        );
        self::assertSame($data, $response->getData());
    }
}
