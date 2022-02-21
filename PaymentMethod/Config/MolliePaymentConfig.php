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
    const USE_SINGLE_CLICK_PAYMENT = 'use_single_click_payment';
    const SINGLE_CLICK_PAYMENT_APPROVAL_TEXT = 'single_click_payment_approval_text';
    const SINGLE_CLICK_PAYMENT_DESCRIPTION_TEXT = 'single_click_payment_description';
    const ISSUERS = 'issuers';
    const PAYMENT_DESCRIPTION = 'payment_description';
    const TRANSACTION_DESCRIPTION = 'transaction_description';
    const ORDER_EXPIRY_DAYS = 'order_expiry_days';
    const PAYMENT_EXPIRY_DAYS = 'payment_expiry_days';
    const VOUCHER_CATEGORY = 'voucher_category';
    const PRODUCT_ATTRIBUTE = 'product_attribute';

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
    public function useSingleClickPayment()
    {
        return (bool)$this->get(self::USE_SINGLE_CLICK_PAYMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleClickPaymentApprovalText()
    {
        return (string)$this->get(self::SINGLE_CLICK_PAYMENT_APPROVAL_TEXT);
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleClickPaymentDescription()
    {
        return (string)$this->get(self::SINGLE_CLICK_PAYMENT_DESCRIPTION_TEXT);
    }

    /**
     * {@inheritdoc }
     */
    public function getIssuerListStyle()
    {
        return (string)$this->get(self::ISSUER_LIST_STYLE);
    }

    /**
     * {@inheritdoc }
     */
    public function getIssuers()
    {
        return (array)$this->get(self::ISSUERS);
    }

    /**
     * {@inheritdoc }
     */
    public function getPaymentDescription()
    {
        return (string)$this->get(self::PAYMENT_DESCRIPTION);
    }

    /**
     * {@inheritdoc }
     */
    public function getTransactionDescription()
    {
        return (string)$this->get(self::TRANSACTION_DESCRIPTION);
    }

    /**
     * {@inheritdoc }
     */
    public function getOrderExpiryDays()
    {
        return (int)$this->get(self::ORDER_EXPIRY_DAYS);
    }

    /**
     * {@inheritdoc }
     */
    public function getPaymentExpiryDays()
    {
        return (int)$this->get(self::PAYMENT_EXPIRY_DAYS);
    }

    /**
     * {@inheritdoc }
     */
    public function getVoucherCategory()
    {
        return (string)$this->get(self::VOUCHER_CATEGORY);
    }

    /**
     * {@inheritdoc }
     */
    public function getProductAttribute()
    {
        return (string)$this->get(self::PRODUCT_ATTRIBUTE);
    }
}
