<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;

/**
 * Class IntegrationOrderCanceledEvent
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event
 */
class IntegrationOrderCanceledEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    private $shopOrderReference;

    /**
     * IntegrationOrderCanceledEvent constructor.
     *
     * @param string $shopOrderReference
     */
    public function __construct($shopOrderReference)
    {
        $this->shopOrderReference = $shopOrderReference;
    }

    /**
     * @return string
     */
    public function getShopOrderReference()
    {
        return $this->shopOrderReference;
    }
}
