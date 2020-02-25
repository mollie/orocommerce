<?php

namespace Mollie\Bundle\PaymentBundle\Mapper;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Address;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Interface MollieDtoMapperInterface
 *
 * @package Mollie\Bundle\PaymentBundle\Mapper
 */
interface MollieDtoMapperInterface
{
    /**
     * Creates Order DTO form PaymentTransaction object
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return Order|null
     */
    public function getOrderData(PaymentTransaction $paymentTransaction);

    /**
     * Creates OrderLine DTO from ORO source line item
     *
     * @param OrderLineItem $orderLineItem
     *
     * @return OrderLine
     */
    public function getOrderLine(OrderLineItem $orderLineItem);

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\BaseDto|Payment
     */
    public function getPaymentData(PaymentTransaction $paymentTransaction);

    /**
     * @param OrderAddress $address
     * @param string $email
     *
     * @return Address
     */
    public function getAddressData(OrderAddress $address, $email);
}