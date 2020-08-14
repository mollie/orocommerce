<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * Interface MolliePaymentContextAwareConfigProviderInterface
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider
 */
interface MolliePaymentContextAwareConfigProviderInterface extends MolliePaymentConfigProviderInterface
{
    /**
     * @param PaymentContextInterface|null $context
     *
     * @return mixed
     */
    public function setPaymentContext(PaymentContextInterface $context = null);
    /**
     * @param string $apiMethod PaymentMethodConfig::API_METHOD_ORDERS|PaymentMethodConfig::API_METHOD_PAYMENT
     */
    public function setApiMethod(string $apiMethod = PaymentMethodConfig::API_METHOD_ORDERS);
}
