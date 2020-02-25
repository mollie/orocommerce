<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Tracking;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;

class IntegrationOrderShippedEvent extends Event
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
     * @var Tracking|null
     */
    private $tracking;

    /**
     * IntegrationOrderShippedEvent constructor.
     *
     * @param string $shopOrderReference Unique identifier of a shop order
     * @param Tracking|null $tracking
     */
    public function __construct($shopOrderReference, $tracking = null)
    {
        $this->shopOrderReference = $shopOrderReference;
        $this->tracking = $tracking;
    }

    /**
     * @return string
     */
    public function getShopOrderReference()
    {
        return $this->shopOrderReference;
    }

    /**
     * @return Tracking|null
     */
    public function getTracking()
    {
        return $this->tracking;
    }
}