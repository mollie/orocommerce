<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;

class MollieOrdersApiPaymentResult implements MolliePaymentResultInterface
{
    /**
     * @var Order
     */
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->order->getId();
    }

    /**
     * @inheritDoc
     */
    public function getRedirectLink()
    {
        return $this->order->getLink('checkout');
    }
}