<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory;

use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

class PaymentConfigIdentifierGenerator
{
    public function __construct(IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator)
    {
        $this->integrationIdentifierGenerator = $integrationIdentifierGenerator;
    }

    public function generateIdentifier(Channel $channel, PaymentMethodSettings $paymentMethodSetting)
    {
        return sprintf(
            '%s_%s',
            $this->integrationIdentifierGenerator->generateIdentifier($channel),
            $paymentMethodSetting->getMollieMethodId()
        );
    }
}