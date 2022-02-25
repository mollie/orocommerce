<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

/**
 * Interface MolliePaymentConfigInterface
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config
 */
interface MolliePaymentConfigInterface extends PaymentConfigInterface
{
    const ADMIN_PAYMENT_LINK_ID = 'mollie_admin_link';
    /**
     * @return string
     */
    public function getApiToken();

    /**
     * @return bool
     */
    public function isTestModeEnabled();

    /**
     * @return string
     */
    public function getIcon();

    /**
     * @return string
     */
    public function getMollieId();

    /**
     * @return string
     */
    public function getApiMethod();

    /**
     * @return bool
     */
    public function isApiMethodRestricted();

    /**
     * @return string
     */
    public function getProfileId();

    /**
     * @return string
     */
    public function getChannelId();

    /**
     * @return float
     */
    public function getSurchargeAmount();

    /**
     * @return bool
     */
    public function useMollieComponents();

    /**
     * @return bool
     */
    public function useSingleClickPayment();

    /**
     * @return string
     */
    public function getSingleClickPaymentApprovalText();

    /**
     * @return string
     */
    public function getSingleClickPaymentDescription();

    /**
     * @return string
     */
    public function getIssuerListStyle();

    /**
     * @return array
     */
    public function getIssuers();

    /**
     * @return string
     */
    public function getPaymentDescription();

    /**
     * @return string
     */
    public function getTransactionDescription();

    /**
     * @return int
     */
    public function getOrderExpiryDays();

    /**
     * @return int
     */
    public function getPaymentExpiryDays();

    /**
     * @return string
     */
    public function getVoucherCategory();

    /**
     * @return string
     */
    public function getProductAttribute();
}
