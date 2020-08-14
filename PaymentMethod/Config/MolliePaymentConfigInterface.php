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
}
