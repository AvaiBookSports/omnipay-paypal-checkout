<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\LinkDescription;
use PaypalServerSdkLib\PaypalServerSdkClient;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    private ?PaypalServerSdkClient $paypalServerSdkClient = null;

    public function getClientId(): string
    {
        $value = $this->getParameter('clientId');
        return \is_string($value) ? $value : '';
    }

    public function setClientId(string $value): self
    {
        return $this->setParameter('clientId', $value);
    }

    public function getClientSecret(): string
    {
        $value = $this->getParameter('clientSecret');
        return \is_string($value) ? $value : '';
    }

    public function setClientSecret(string $value): self
    {
        return $this->setParameter('clientSecret', $value);
    }

    public function getBrandName(): ?string
    {
        $value = $this->getParameter('brandName');
        return \is_string($value) ? $value : null;
    }

    public function setBrandName(?string $value): self
    {
        return $this->setParameter('brandName', $value);
    }

    /**
     * @mago-expect lint:halstead
     */
    protected function getSdkClient(): PaypalServerSdkClient
    {
        if ($this->paypalServerSdkClient instanceof PaypalServerSdkClient) {
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
     * @return array<string, mixed>
     */
    protected function serializeToArray(\JsonSerializable $jsonSerializable): array
    {
        $encoded = \json_encode($jsonSerializable->jsonSerialize());
        $decoded = \json_decode(false === $encoded ? '{}' : $encoded, true);
        $result = [];
        if (\is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                // @mago-expect lint:prefer-early-continue
                if (\is_string($key)) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param LinkDescription[] $links
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
