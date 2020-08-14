<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Factory;

use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Class MollieConfigMapperDecorator
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
class MollieConfigMapperDecorator implements MollieDtoMapperInterface
{
    /**
     * @var MollieDtoMapperInterface
     */
    protected $dtoMapper;
    /**
     * @var MolliePaymentConfigInterface $config
     */
    protected $config;

    /**
     * MollieConfigMapperDecorator constructor.
     *
     * @param MollieDtoMapperInterface $dtoMapper
     * @param MolliePaymentConfigInterface $config
     */
    public function __construct(MollieDtoMapperInterface $dtoMapper, MolliePaymentConfigInterface $config)
    {
        $this->dtoMapper = $dtoMapper;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderData(PaymentTransaction $paymentTransaction)
    {
        $orderData = $this->dtoMapper->getOrderData($paymentTransaction);
        if ($orderData) {
            $orderData->setProfileId($this->config->getProfileId());
            $orderData->setMethod([$this->config->getMollieId()]);
        }

        return $orderData;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderLine(OrderLineItem $orderLineItem)
    {
        return $this->dtoMapper->getOrderLine($orderLineItem);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentData(PaymentTransaction $paymentTransaction)
    {
        $paymentData = $this->dtoMapper->getPaymentData($paymentTransaction);
        $paymentData->setProfileId($this->config->getProfileId());
        $paymentData->setMethod([$this->config->getMollieId()]);

        return $paymentData;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddressData(OrderAddress $address, $email)
    {
        return $this->dtoMapper->getAddressData($address, $email);
    }
}
