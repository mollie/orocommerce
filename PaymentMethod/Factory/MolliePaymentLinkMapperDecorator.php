<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Factory;

use Mollie\Bundle\PaymentBundle\Manager\PaymentLinkConfigProviderInterface;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Class MolliePaymentLinkMapperDecorator
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
class MolliePaymentLinkMapperDecorator extends MollieConfigMapperDecorator
{
    /**
     * @var PaymentLinkConfigProviderInterface
     */
    private $paymentLinkConfigProvider;

    /**
     * MolliePaymentLinkMapperDecorator constructor.
     *
     * @param MollieDtoMapperInterface $dtoMapper
     * @param MolliePaymentConfigInterface $config
     */
    public function __construct(
        MollieDtoMapperInterface $dtoMapper,
        MolliePaymentConfigInterface $config,
        PaymentLinkConfigProviderInterface $paymentLinkConfigProvider,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($dtoMapper, $config, $doctrineHelper);

        $this->paymentLinkConfigProvider = $paymentLinkConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderData(PaymentTransaction $paymentTransaction)
    {
        $orderData = parent::getOrderData($paymentTransaction);
        if ($orderData) {
            $orderData->setMethods($this->getPaymentLinkMethod($paymentTransaction));
        }

        return $orderData;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentData(PaymentTransaction $paymentTransaction)
    {
        $paymentData = parent::getPaymentData($paymentTransaction);

        $paymentData->setMethods($this->getPaymentLinkMethod($paymentTransaction));

        return $paymentData;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return string[]|null
     */
    private function getPaymentLinkMethod(PaymentTransaction $paymentTransaction)
    {
        $paymentLinkConfig = $this->paymentLinkConfigProvider
            ->getPaymentLinkConfig($paymentTransaction->getEntityIdentifier());

        if ($paymentLinkConfig && !empty($paymentLinkConfig->getPaymentMethods())) {
            return $paymentLinkConfig->getPaymentMethods();
        }

        return null;
    }
}
