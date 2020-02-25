<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;

/**
 * Class IntegrationOrderTotalChangedEvent
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event
 */
class IntegrationOrderTotalChangedEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    protected $shopReference;

    /**
     * IntegrationOrderTotalChangedEvent constructor.
     *
     * @param string $shopReference
     */
    public function __construct($shopReference)
    {
        $this->shopReference = $shopReference;
    }

    /**
     * @return string
     */
    public function getShopReference()
    {
        return $this->shopReference;
    }
}
