<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory;

use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

/**
 * Class PaymentConfigIdentifierGenerator
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory
 */
class PaymentConfigIdentifierGenerator
{
    /**
     * PaymentConfigIdentifierGenerator constructor.
     *
     * @param IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator
     */
    public function __construct(IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator)
    {
        $this->integrationIdentifierGenerator = $integrationIdentifierGenerator;
    }

    /**
     * @param Channel $channel
     * @param PaymentMethodSettings $paymentMethodSetting
     *
     * @return string
     */
    public function generateIdentifier(Channel $channel, PaymentMethodSettings $paymentMethodSetting)
    {
        return sprintf(
            '%s_%s',
            $this->integrationIdentifierGenerator->generateIdentifier($channel),
            $paymentMethodSetting->getMollieMethodId()
        );
    }
}
