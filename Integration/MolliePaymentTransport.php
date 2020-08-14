<?php

namespace Mollie\Bundle\PaymentBundle\Integration;

use Mollie\Bundle\PaymentBundle\Entity\ChannelSettings;
use Mollie\Bundle\PaymentBundle\Form\Type\ChannelSettingsType;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

/**
 * Class MolliePaymentTransport
 *
 * @package Mollie\Bundle\PaymentBundle\Integration
 */
class MolliePaymentTransport implements TransportInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'mollie.payment.transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return ChannelSettingsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return ChannelSettings::class;
    }
}
