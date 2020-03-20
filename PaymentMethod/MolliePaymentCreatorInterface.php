<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface MolliePaymentCreatorInterface
{
    /**
     * Creates payment instance (payment or order) on mollie api
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return MolliePaymentResultInterface|null
     */
    public function createMolliePayment(PaymentTransaction $paymentTransaction);
}