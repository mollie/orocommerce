<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

interface MolliePaymentConfigInterface extends PaymentConfigInterface
{
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
     * @return string
     */
    public function getProfileId();

    /**
     * @return string
     */
    public function getChannelId();

    /**
     * @return bool
     */
    public function isSurchargeSupported();

    /**
     * @return float
     */
    public function getSurchargeAmount();
}