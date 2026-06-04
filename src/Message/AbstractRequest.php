<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    private ?PaypalServerSdkClient $paypalServerSdkClient = null;

    public function getClientId(): string
    {
        return $this->getParameter('clientId') ?? '';
    }

    public function setClientId(string $value): self
    {
        return $this->setParameter('clientId', $value);
    }

    public function getClientSecret(): string
    {
        return $this->getParameter('clientSecret') ?? '';
    }

    public function setClientSecret(string $value): self
    {
        return $this->setParameter('clientSecret', $value);
    }

    public function getBrandName(): ?string
    {
        return $this->getParameter('brandName');
    }

    public function setBrandName(?string $value): self
    {
        return $this->setParameter('brandName', $value);
    }

    protected function getSdkClient(): PaypalServerSdkClient
    {
        if ($this->paypalServerSdkClient instanceof \PaypalServerSdkLib\PaypalServerSdkClient) {
            return $this->paypalServerSdkClient;
        }

        $this->paypalServerSdkClient = PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init(
                    $this->getClientId(),
                    $this->getClientSecret(),
                ),
            )
            ->environment($this->getTestMode() ? Environment::SANDBOX : Environment::PRODUCTION)
            ->build();

        return $this->paypalServerSdkClient;
    }

    /**
     * @param list<\PaypalServerSdkLib\Models\LinkDescription> $links
     */
    protected function findApprovalUrl(array $links): ?string
    {
        foreach ($links as $link) {
            if ($link->getRel() === 'approve' || $link->getRel() === 'payer-action') {
                return $link->getHref();
            }
        }

        return null;
    }
}
