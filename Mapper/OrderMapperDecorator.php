<?php

namespace Mollie\Bundle\PaymentBundle\Mapper;

use Mollie\Bundle\PaymentBundle\Entity\MollieSurchargeAwareInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;

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
        /** @var MollieSurchargeAwareInterface $order */
        $order = $this->orderMapper->map($checkout, $data, $skipped);

        if ($checkout instanceof MollieSurchargeAwareInterface) {
            $order->setMollieSurchargeAmount($checkout->getMollieSurchargeAmount());
        }

        return $order;
    }
}
