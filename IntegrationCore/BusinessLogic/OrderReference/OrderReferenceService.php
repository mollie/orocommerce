<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\BaseDto;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Class OrderReferenceService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference
 */
class OrderReferenceService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Returns api method which is used for provided shop order identifier
     *
     * @param int|string $shopReference Unique identifier of a shop order
     *
     * @return string|null
     */
    public function getApiMethod($shopReference)
    {
        $orderReference = $this->getByShopReference($shopReference);

        return $orderReference ? $orderReference->getApiMethod() : null;
    }

    /**
     * @param BaseDto $createdResource
     * @param $shopReference
     * @param $method
     */
    public function updateOrderReference(BaseDto $createdResource, $shopReference, $method)
    {
        $orderReference = $this->getByShopReference($shopReference);
        if (!$orderReference) {
            $orderReference = new OrderReference();
        }

        $orderReference->setShopReference($shopReference);
        $orderReference->setMollieReference($createdResource->getId());
        $orderReference->setApiMethod($method);
        $orderReference->setPayload($createdResource->toArray());

        $this->getRepository(OrderReference::CLASS_NAME)->saveOrUpdate($orderReference);
    }

    /**
     * Returns order reference for provided shop order identifier
     *
     * @param int|string $shopReference Unique identifier of a shop order
     *
     * @return OrderReference|null
     */
    public function getByShopReference($shopReference)
    {
        /** @var OrderReference|null $orderReference */
        $orderReference = $this->getRepository(OrderReference::CLASS_NAME)->selectOne(
            $this->setFilterCondition(
                new QueryFilter(), 'shopReference', Operators::EQUALS, (string)$shopReference
            )
        );

        return  $orderReference;
    }

    /**
     * Returns order reference for provided mollie order/payment identifier
     *
     * @param string $mollieReference Unique identifier of a mollie order or payment
     *
     * @return OrderReference|null
     */
    public function getByMollieReference($mollieReference)
    {
        /** @var OrderReference|null $orderReference */
        $orderReference = $this->getRepository(OrderReference::CLASS_NAME)->selectOne(
            $this->setFilterCondition(
                new QueryFilter(), 'mollieReference', Operators::EQUALS, (string)$mollieReference
            )
        );

        return $orderReference;
    }
}
