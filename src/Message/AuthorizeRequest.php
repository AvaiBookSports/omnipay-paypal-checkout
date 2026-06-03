<?php

declare(strict_types=1);

namespace Omnipay\PayPalCheckout\Message;

use PaypalServerSdkLib\Models\CheckoutPaymentIntent;

class AuthorizeRequest extends PurchaseRequest
{
    protected function getIntent(): string
    {
        return CheckoutPaymentIntent::AUTHORIZE;
    }
}
