<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod;

/**
 * Class PaymentMethods
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod
 */
class PaymentMethods
{
    const PayPal = 'paypal';
    const KlarnaPayLater = 'klarnapaylater';
    const KlarnaSliceIt = 'klarnasliceit';
    const KlarnaPayNow = 'klarnapaynow';
    const CreditCard = 'creditcard';
    const iDEAL = 'ideal';
    const KBC = 'kbc';
    const GiftCard = 'giftcard';
    const Vouchers = 'voucher';
    const Banktransfer = 'banktransfer';
    const Alma = 'alma';
    const Payconiq = 'payconiq';
}
