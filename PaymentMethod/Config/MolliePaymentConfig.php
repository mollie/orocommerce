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
    const ISSUER_LIST_STYLE = 'issuer_list_style';
    const USE_MOLLIE_COMPONENTS = 'use_mollie_components';
    const ISSUERS = 'issuers';
    const PAYMENT_DESCRIPTION = 'payment_description';

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

    /**
     * {@inheritdoc}
     */
    public function useMollieComponents()
    {
        return (bool)$this->get(self::USE_MOLLIE_COMPONENTS);
    }

    /**
     * {@inheritdoc }
     */
    public function getIssuerListStyle()
    {
        return (string)$this->get(self::ISSUER_LIST_STYLE);
    }

    public function getIssuers()
    {
        return (array)$this->get(self::ISSUERS);
    }

    public function getPaymentDescription()
    {
        return (string)$this->get(self::PAYMENT_DESCRIPTION);
    }
}
