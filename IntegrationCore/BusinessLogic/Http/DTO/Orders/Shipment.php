<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\BaseDto;

class Shipment extends BaseDto
{
    /**
     * @var string
     */
    protected $resource;
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $orderId;
    /**
     * @var Tracking|null
     */
    protected $tracking;
    /**
     * @var OrderLine[]
     */
    protected $lines;

    /**
     * @param array $raw
     *
     * @return Shipment
     */
    public static function fromArray(array $raw)
    {
        $result = new static();

        $result->resource = static::getValue($raw, 'resource');
        $result->id = static::getValue($raw, 'id');
        $result->orderId = static::getValue($raw, 'orderId');
        $result->setLines(OrderLine::fromArrayBatch(static::getValue($raw, 'lines', array())));

        $trackingData = static::getValue($raw, 'tracking', array());
        if (!empty($trackingData)) {
            $result->tracking = Tracking::fromArray($trackingData);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $lines = array();
        foreach ($this->lines as $orderLine) {
            $lines[] = $orderLine->toArray();
        }

        $result = array(
            'resource' => $this->resource,
            'id' => $this->id,
            'orderId' => $this->orderId,
            'lines' => $lines,
        );

        if ($this->tracking) {
            $result['tracking'] = $this->tracking;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return Tracking|null
     */
    public function getTracking()
    {
        return $this->tracking;
    }

    /**
     * @param Tracking|null $tracking
     */
    public function setTracking($tracking)
    {
        $this->tracking = $tracking;
    }

    /**
     * @return OrderLine[]
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @param OrderLine[] $lines
     */
    public function setLines($lines)
    {
        $this->lines = $lines;
    }
}