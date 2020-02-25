<?php

namespace Mollie\Bundle\PaymentBundle\Form\Entity;

/**
 * Class MollieRefundLineItem
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Entity
 */
class MollieRefundLineItem
{
    /**
     * @var string
     */
    private $sku;
    /**
     * @var string
     */
    private $mollieId;
    /**
     * @var string
     */
    private $product;
    /**
     * @var int
     */
    private $orderedQuantity;
    /**
     * @var int
     */
    private $refundedQuantity;
    /**
     * @var float
     */
    private $price;
    /**
     * @var int
     */
    private $quantityToRefund;
    /**
     * @var bool
     */
    private $isRefundable;

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return string
     */
    public function getMollieId()
    {
        return $this->mollieId;
    }

    /**
     * @param string $mollieId
     */
    public function setMollieId($mollieId)
    {
        $this->mollieId = $mollieId;
    }

    /**
     * @param string $sku
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }

    /**
     * @return string
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param string $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return int
     */
    public function getOrderedQuantity()
    {
        return $this->orderedQuantity;
    }

    /**
     * @param int $orderedQuantity
     */
    public function setOrderedQuantity($orderedQuantity)
    {
        $this->orderedQuantity = $orderedQuantity;
    }

    /**
     * @return int
     */
    public function getRefundedQuantity()
    {
        return $this->refundedQuantity;
    }

    /**
     * @param int $refundedQuantity
     */
    public function setRefundedQuantity($refundedQuantity)
    {
        $this->refundedQuantity = $refundedQuantity;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getQuantityToRefund()
    {
        return $this->quantityToRefund;
    }

    /**
     * @param int $quantityToRefund
     */
    public function setQuantityToRefund($quantityToRefund)
    {
        $this->quantityToRefund = $quantityToRefund;
    }

    /**
     * @return bool
     */
    public function isRefundable()
    {
        return $this->isRefundable;
    }

    /**
     * @param bool $isRefundable
     */
    public function setIsRefundable($isRefundable)
    {
        $this->isRefundable = $isRefundable;
    }
}
