<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Link;

/**
 * Interface MolliePaymentResultInterface
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
interface MolliePaymentResultInterface
{
    /**
     * Gets id of created mollie payment
     *
     * @return string
     */
    public function getId();

    /**
     * Gets redirect link from mollie payment
     *
     * @return Link|null
     */
    public function getRedirectLink();
}
