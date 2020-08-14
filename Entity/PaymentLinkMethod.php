<?php

namespace Mollie\Bundle\PaymentBundle\Entity;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Configuration\EntityConfiguration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Configuration\IndexMap;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Entity;

/**
 * Class PaymentLinkMethod
 *
 * @package Mollie\Bundle\PaymentBundle\Entity
 */
class PaymentLinkMethod extends Entity
{

    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * {@inheritdoc}
     */
    protected $fields = array(
        'id',
        'shopReference',
        'apiMethod',
        'paymentMethods',
    );

    /**
     * @var string
     */
    protected $shopReference;
    /**
     * @var string
     */
    protected $apiMethod;
    /**
     * @var string[]
     */
    protected $paymentMethods = [];

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $map = new IndexMap();

        $map->addStringIndex('shopReference');

        return new EntityConfiguration($map, 'PaymentLinkMethod');
    }

    /**
     * @return string
     */
    public function getShopReference()
    {
        return $this->shopReference;
    }

    /**
     * @param string $shopReference
     */
    public function setShopReference($shopReference)
    {
        $this->shopReference = (string)$shopReference;
    }

    /**
     * @return string
     */
    public function getApiMethod()
    {
        return $this->apiMethod;
    }

    /**
     * @param string $apiMethod
     */
    public function setApiMethod($apiMethod)
    {
        $this->apiMethod = $apiMethod;
    }

    /**
     * @return string[]
     */
    public function getPaymentMethods()
    {
        return $this->paymentMethods;
    }

    /**
     * @param string[] $paymentMethods
     */
    public function setPaymentMethods(array $paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
    }
}
