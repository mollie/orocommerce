<?php

namespace Mollie\Bundle\PaymentBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class MolliePaymentChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'mollie_payment_channel';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'mollie.payment.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/molliepayment/img/mollie-logo.png';
    }
}