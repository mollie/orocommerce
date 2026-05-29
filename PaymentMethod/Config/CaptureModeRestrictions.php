<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config;

final class CaptureModeRestrictions
{
    public const MANUAL_ONLY = [];

    public const AUTOMATIC_ONLY = [
        'ideal',
        'bancomatpay',
        'bancontact',
        'belfius',
        'blik',
        'eps',
        'giftcard',
        'kbc',
        'mybank',
        'przelewy24',
        'paysafecard',
        'banktransfer',
        'twint',
        'paypal',
        'directdebit',
        'sofort',
        'voucher',
        'payconiq',
        'alma',
    ];
}
