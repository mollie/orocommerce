<?php

namespace Mollie\Bundle\PaymentBundle\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Class OrderMapperDecorator
 *
 * @package Mollie\Bundle\PaymentBundle\Mapper
 */
class OrderMapperDecorator implements MapperInterface
{
    /**
     * @var MapperInterface
     */
    private $orderMapper;

    /**
     * @param MapperInterface $orderMapper
     */
    public function __construct(MapperInterface $orderMapper)
    {
        $this->orderMapper = $orderMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function map(Checkout $checkout, array $data = [], array $skipped = [])
    {
        $skipped['mollieSurchargeAmount'] = true;
        $order = $this->orderMapper->map($checkout, $data, $skipped);

        if ($order instanceof Order) {
            $order->setMollieSurchargeAmount($checkout->getMollieSurchargeAmount());
        }

        return $order;
    }
}
