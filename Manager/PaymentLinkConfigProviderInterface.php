<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Entity\PaymentLinkMethod;

/**
 * Interface PaymentLinkConfigProviderInterface
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
interface PaymentLinkConfigProviderInterface
{
    /**
     * @param string $orderId
     * @return PaymentLinkMethod|null
     */
    public function getPaymentLinkConfig($orderId);
}
