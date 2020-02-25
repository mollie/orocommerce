<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config;

use Oro\Bundle\PaymentBundle\Method\Config\ParameterBag\AbstractParameterBagPaymentConfig;

class MolliePaymentConfig extends AbstractParameterBagPaymentConfig implements MolliePaymentConfigInterface
{
    const API_TOKEN = 'api_token';
    const TEST_MODE = 'test_mode';
    const ICON = 'icon';
    const MOLLIE_ID = 'mollie_id';
    const API_METHOD = 'api_method';
    const PROFILE_ID = 'profile_id';
    const CHANNEL_ID = 'channel_id';
    const IS_SURCHARGE_SUPPORTED = 'is_surcharge_supported';
    const SURCHARGE_AMOUNT = 'surcharge_amount';

    /**
     * @inheritDoc
     */
    public function getApiToken()
    {
        return (string)$this->get(self::API_TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function isTestModeEnabled()
    {
        return (bool)$this->get(self::TEST_MODE);
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return (string)$this->get(self::ICON);
    }

    /**
     * @inheritDoc
     */
    public function getMollieId()
    {
        return (string)$this->get(self::MOLLIE_ID);
    }

    /**
     * @inheritDoc
     */
    public function getApiMethod()
    {
        return (string)$this->get(self::API_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function getProfileId()
    {
        return (string)$this->get(self::PROFILE_ID);
    }

    /**
     * @inheritDoc
     */
    public function getChannelId()
    {
        return (string)$this->get(self::CHANNEL_ID);
    }

    /**
     * @inheritDoc
     */
    public function isSurchargeSupported()
    {
        return (bool)$this->get(self::IS_SURCHARGE_SUPPORTED);
    }

    /**
     * @inheritDoc
     */
    public function getSurchargeAmount()
    {
        return (float)$this->get(self::SURCHARGE_AMOUNT);
    }
}