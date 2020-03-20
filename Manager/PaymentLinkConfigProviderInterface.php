<?php


namespace Mollie\Bundle\PaymentBundle\Manager;


use Mollie\Bundle\PaymentBundle\Entity\PaymentLinkMethod;

interface PaymentLinkConfigProviderInterface
{
    /**
     * @param string $orderId
     * @return PaymentLinkMethod|null
     */
    public function getPaymentLinkConfig($orderId);
}