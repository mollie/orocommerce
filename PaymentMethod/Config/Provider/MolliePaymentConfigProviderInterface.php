<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;

/**
 * Interface MolliePaymentConfigProviderInterface
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider
 */
interface MolliePaymentConfigProviderInterface
{
    /**
     * @return MolliePaymentConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return MolliePaymentConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
