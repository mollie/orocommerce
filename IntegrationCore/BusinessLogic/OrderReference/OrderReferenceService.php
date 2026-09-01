<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\BaseDto;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
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
     * Creates or updates the order reference for the given shop order.
     *
     * IMPORTANT: $shopReference is ALWAYS the shop order entity id (in OroCommerce
     * Order::getId()), never the order number (Order::getIdentifier()). Every write path
     * stores it that way - PaymentService::createPayment() and OrderService::createOrder()
     * are both called with PaymentTransaction::getEntityIdentifier(), and
     * WebHookTransformer reuses the value already stored on the reference. Any reader must
     * therefore look the reference up by the entity id as well; the two values only happen
     * to be equal when the default SimpleEntityAwareGenerator is in use.
     *
     * @param BaseDto $createdResource
     * @param int|string $shopReference Shop order entity id
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
     * $shopReference must be the shop order entity id (see updateOrderReference()), not the
     * order number. The lookup is a strict EQUALS comparison on the string value with no
     * fallback, so passing the wrong identifier silently returns null instead of failing.
     * The shopReference index is not unique either, so a wrong identifier can even match a
     * different order's reference.
     *
     * @param int|string $shopReference Shop order entity id
     *
     * @return OrderReference|null
     */
    public function getByShopReference($shopReference)
    {
        /** @var OrderReference|null $orderReference */
        $orderReference = $this->getRepository(OrderReference::CLASS_NAME)->selectOne(
            $this->setFilterCondition(
                new QueryFilter(),
                'shopReference',
                Operators::EQUALS,
                (string)$shopReference
            )
        );

        return $orderReference;
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
                new QueryFilter(),
                'mollieReference',
                Operators::EQUALS,
                (string)$mollieReference
            )
        );

        return $orderReference;
    }

    /**
     * Gets order reference with restrictions:
     *  - Order reference should be stored
     *  - Mollie reference should be present on order reference
     *  - Api method must be equal to given api method
     *
     * @param string $shopReference
     * @param string $allowedApiMethod
     *
     * @return OrderReference
     *
     * @throws ReferenceNotFoundException
     */
    public function getValidOrderReference($shopReference, $allowedApiMethod)
    {
        $orderReference = $this->getByShopReference($shopReference);
        if (
            $orderReference &&
            $orderReference->getMollieReference() &&
            $orderReference->getApiMethod() === $allowedApiMethod
        ) {
            return $orderReference;
        }

        throw new ReferenceNotFoundException("Valid order reference not found for shop reference: {$shopReference}");
    }
}
