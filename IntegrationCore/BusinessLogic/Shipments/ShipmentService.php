<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Shipments;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Shipment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Tracking;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Shipments\Exceptions\ShipmentNotAllowedException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class ShipmentService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Shipments
 */
class ShipmentService extends BaseService
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
     * Creates shipment for complete order on mollie API based on provided shop reference
     *
     * @param string $shopReference Unique identifier of a shop order
     *
     * @param Tracking|null $tracking
     * @param OrderLine[] $lines
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function shipOrder($shopReference, $tracking = null, $lines = array())
    {
        $orderReference = $this->getValidOrderReference($shopReference);

        $shipment = Shipment::fromArray(array('orderId' => $orderReference->getMollieReference()));
        $order = Order::fromArray($orderReference->getPayload());
        $shipment->setLines($lines);
        if ($this->allItemsRefunding($order->getLines(), $shipment->getShipmentLinesMap())) {
            $shipment->setLines(array());
        }

        if ($tracking) {
            $shipment->setTracking($tracking);
        }

        $this->getProxy()->createShipment($shipment);
    }

    /**
     * @param string|int $shopReference
     *
     * @return Shipment[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function getShipments($shopReference)
    {
        $orderReference = $this->getValidOrderReference($shopReference);

        return $this->getProxy()->getShipments($orderReference->getMollieReference());
    }

    /**
     * Check if shipping all remaining items
     *
     * @param OrderLine[] $existingLines
     * @param array $shipmentLinesMap
     *
     * @return bool
     * @throws ShipmentNotAllowedException
     */
    private function allItemsRefunding($existingLines, array $shipmentLinesMap)
    {
        $isFullShipment = true;
        foreach ($existingLines as $existingLine) {
            if ($existingLine->getType() === 'discount') {
                continue;
            }

            if (array_key_exists($existingLine->getId(), $shipmentLinesMap)) {
                $this->recalculateShippableQuantity($shipmentLinesMap[$existingLine->getId()], $existingLine);
            }

            if ($existingLine->getShippableQuantity() > 0) {
                return false;
            }
        }

        return $isFullShipment;
    }

    /**
     * @param OrderLine $shipmentLine
     * @param OrderLine $existingLine
     *
     * @throws ShipmentNotAllowedException
     */
    private function recalculateShippableQuantity(OrderLine $shipmentLine, OrderLine $existingLine)
    {
        $recalculatedQty = $existingLine->getShippableQuantity() - $shipmentLine->getQuantity();
        if ($recalculatedQty < 0) {
            throw new ShipmentNotAllowedException('Ship item quantity is bigger than ordered');
        }

        $existingLine->setShippableQuantity($recalculatedQty);
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

        return $orderReferenceService->getValidOrderReference($shopReference, PaymentMethodConfig::API_METHOD_ORDERS);
    }
}
