<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Address;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Shipment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Tracking;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class OrderService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders
 */
class OrderService extends BaseService
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
     * Creates new order on Mollie for a provided order data
     *
     * @param string $shopReference Unique identifier of a shop order
     * @param Order $order Order data to pass to create method
     *
     * @return Order Created order instance
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function createOrder($shopReference, Order $order)
    {
        $createdOrder = $this->getProxy()->createOrder($order);
        $this->updateOrderReference($shopReference, $createdOrder);

        return $createdOrder;
    }

    /**
     * @param $shopReference
     *
     * @return Order
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     */
    public function getOrder($shopReference)
    {
        return $this->getProxy()->getOrder(
            $this->getValidOrderReference($shopReference)->getMollieReference()
        );
    }

    /**
     * Creates shipment for complete order on mollie API based on provided shop reference
     *
     * @param string $shopReference Unique identifier of a shop order
     *
     * @param Tracking|null $tracking
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function shipOrder($shopReference, $tracking = null)
    {
        $orderReference = $this->getValidOrderReference($shopReference);

        $shipment = Shipment::fromArray(array('orderId' => $orderReference->getMollieReference()));
        if ($tracking) {
            $shipment->setTracking($tracking);
        }

        $this->getProxy()->createShipment($shipment);
    }

    /**
     * @param string $shopReference order id from shop
     * @param OrderLine $orderLine DTO for update
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function updateOrderLine($shopReference, OrderLine $orderLine)
    {
        $orderReference = $this->getValidOrderReference($shopReference);
        $this->getProxy()->updateOrderLine($orderReference->getMollieReference(), $orderLine->getId(), $orderLine);
    }

    /**
     * Updates billing address
     *
     * @param string $shopReference
     * @param Address $billingAddress
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function updateBillingAddress($shopReference, Address $billingAddress)
    {
        $orderDto = new Order();
        $orderDto->setBillingAddress($billingAddress);

        $this->updateOrderAddress($shopReference, $orderDto);
    }

    /**
     * Updates shipping address
     *
     * @param string $shopReference
     * @param Address $shippingAddress
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function updateShippingAddress($shopReference, Address $shippingAddress)
    {
        $orderDto = new Order();
        $orderDto->setShippingAddress($shippingAddress);

        $this->updateOrderAddress($shopReference, $orderDto);
    }

    /**
     * @param string $shopReference
     * @param Order $orderDto
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    protected function updateOrderAddress($shopReference, Order $orderDto)
    {
        $orderReference = $this->getValidOrderReference($shopReference);
        $this->getProxy()->updateOrder($orderReference->getMollieReference(), $orderDto);
    }

    /**
     * Gets order reference with existing Mollie reference
     *
     * @param string $shopReference
     *
     * @return OrderReference
     *
     * @throws ReferenceNotFoundException
     */
    protected function getValidOrderReference($shopReference)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        $orderReference = $orderReferenceService->getByShopReference($shopReference);
        if (
            $orderReference &&
            $orderReference->getMollieReference() &&
            $orderReference->getApiMethod() === PaymentMethodConfig::API_METHOD_ORDERS
        ) {
            return $orderReference;
        }

        throw new ReferenceNotFoundException("Valid order reference not found for shop reference: {$shopReference}");
    }

    /**
     * @param $shopReference
     * @param Order $order
     */
    protected function updateOrderReference($shopReference, Order $order)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        $orderReferenceService->updateOrderReference(
            $order,
            $shopReference,
            PaymentMethodConfig::API_METHOD_ORDERS
        );
    }
}
