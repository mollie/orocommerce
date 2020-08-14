<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;

/**
 * Class MolliePaymentConfig
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config
 */
class MolliePaymentConfig extends AbstractParameterBagPaymentConfig implements MolliePaymentConfigInterface
{
    const API_TOKEN = 'api_token';
    const TEST_MODE = 'test_mode';
    const ICON = 'icon';
    const MOLLIE_ID = 'mollie_id';
    const API_METHOD = 'api_method';
    const IS_API_METHOD_RESTRICTED = 'is_api_method_restricted';
    const PROFILE_ID = 'profile_id';
    const CHANNEL_ID = 'channel_id';
    const SURCHARGE_AMOUNT = 'surcharge_amount';

    /**
     * {@inheritdoc}
     */
    public function getApiToken()
    {
        return (string)$this->get(self::API_TOKEN);
    }

    /**
     * {@inheritdoc}
     */
    public function isTestModeEnabled()
    {
        return (bool)$this->get(self::TEST_MODE);
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return (string)$this->get(self::ICON);
    }

    /**
     * {@inheritdoc}
     */
    public function getMollieId()
    {
        return (string)$this->get(self::MOLLIE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiMethod()
    {
        return (string)$this->get(self::API_METHOD);
    }

    /**
     * {@inheritdoc}
     */
    public function isApiMethodRestricted()
    {
        return (bool)$this->get(self::IS_API_METHOD_RESTRICTED);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileId()
    {
        return (string)$this->get(self::PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getChannelId()
    {
        return (string)$this->get(self::CHANNEL_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getSurchargeAmount()
    {
        return (float)$this->get(self::SURCHARGE_AMOUNT);
    }
}
