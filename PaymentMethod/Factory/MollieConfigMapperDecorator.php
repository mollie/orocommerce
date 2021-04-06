<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Factory;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\Manager\ProductAttributeResolver;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
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
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * MollieConfigMapperDecorator constructor.
     *
     * @param MollieDtoMapperInterface $dtoMapper
     * @param MolliePaymentConfigInterface $config
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        MollieDtoMapperInterface $dtoMapper,
        MolliePaymentConfigInterface $config,
        DoctrineHelper $doctrineHelper
    ) {
        $this->dtoMapper = $dtoMapper;
        $this->config = $config;
        $this->doctrineHelper = $doctrineHelper;
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
            $expiryDays = $this->config->getOrderExpiryDays();
            if ($expiryDays > 0) {
                $orderData->calculateExpiresAt($expiryDays);
            }
        }

        $mollieLines = [];
        $order = $this->getOrderEntity($paymentTransaction);
        if ($order) {
            $lines = $order->getLineItems();
            foreach ($lines as $line) {
                $mollieLines[] = $this->getOrderLine($line);
            }

            $orderData->setLines(array_merge($mollieLines, $this->getSurcharges($order)));
        }

        return $orderData;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderLine(OrderLineItem $orderLineItem)
    {
        $mollieLine = $this->dtoMapper->getOrderLine($orderLineItem);
        $product = $orderLineItem->getProduct();
        $attributeProvider = new ProductAttributeResolver($product, $this->config->getVoucherCategory(), $this->config->getProductAttribute());
        $category = $attributeProvider->getPropertyValue();
        if (in_array($category, ['meal', 'eco', 'gift'])) {
            $mollieLine->setCategory($category);
        }

        return $mollieLine;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentData(PaymentTransaction $paymentTransaction)
    {
        $paymentData = $this->dtoMapper->getPaymentData($paymentTransaction);
        $paymentData->setProfileId($this->config->getProfileId());
        $paymentData->setMethod([$this->config->getMollieId()]);
        $this->setDueDate($paymentData);

        return $paymentData;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddressData(OrderAddress $address, $email)
    {
        return $this->dtoMapper->getAddressData($address, $email);
    }

    /**
     * @param Order $order
     *
     * @return OrderLine[]
     */
    public function getSurcharges(Order $order)
    {
        return $this->dtoMapper->getSurcharges($order);
    }


    /**
     * @param Payment $paymentData
     */
    protected function setDueDate(Payment $paymentData)
    {
        $paymentExpiryDays = $this->config->getPaymentExpiryDays();
        if ($paymentExpiryDays > 0 && $this->config->getMollieId() === 'banktransfer') {
            $paymentData->calculateDueDate($paymentExpiryDays);
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return Order|null
     */
    protected function getOrderEntity(PaymentTransaction $paymentTransaction)
    {
        if ($paymentTransaction->getEntityClass() !== Order::class) {
            return null;
        }

        /** @var Order $order */
        $order = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        return $order;
    }
}
