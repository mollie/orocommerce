<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;

/**
 * Class MollieOrdersApiPaymentResult
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
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
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->order->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectLink()
    {
        return $this->order->getLink('checkout');
    }
}
