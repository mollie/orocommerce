<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CustomerReference\Model;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Configuration\EntityConfiguration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Configuration\IndexMap;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Entity;

/**
 * Class CustomerReference
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CustomerReference\Model
 */
class CustomerReference extends Entity
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
        'mollieReference',
        'payload',
    );

    /**
     * @var string
     */
    protected $shopReference;
    /**
     * @var string
     */
    protected $mollieReference;
    /**
     * @var array
     */
    protected $payload = array();

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $map = new IndexMap();

        $map->addStringIndex('shopReference');
        $map->addStringIndex('mollieReference');

        return new EntityConfiguration($map, 'CustomerReference');
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
        $this->shopReference = $shopReference;
    }

    /**
     * @return string
     */
    public function getMollieReference()
    {
        return $this->mollieReference;
    }

    /**
     * @param string $mollieReference
     */
    public function setMollieReference($mollieReference)
    {
        $this->mollieReference = $mollieReference;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }
}
