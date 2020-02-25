<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Address;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;

/**
 * Class IntegrationOrderShippingAddressChangedEvent
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event
 */
class IntegrationOrderShippingAddressChangedEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    private $shopReference;
    /**
     * @var Address
     */
    private $shippingAddress;

    /**
     * IntegrationOrderShippingAddressChangedEvent constructor.
     *
     * @param string $shopReference
     * @param Address $shippingAddress
     */
    public function __construct($shopReference, Address $shippingAddress)
    {
        $this->shopReference = $shopReference;
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @return string
     */
    public function getShopReference()
    {
        return $this->shopReference;
    }

    /**
     * @return Address
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }
}
