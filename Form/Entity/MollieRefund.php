<?php

namespace Mollie\Bundle\PaymentBundle\Form\Entity;

/**
 * Class MollieRefund
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Entity
 */
class MollieRefund
{
    /**
     * @var array
     */
    private $refundItems = [];
    /**
     * @var bool
     */
    private $isOrderApiUsed;
    /**
     * @var MollieRefundPayment
     */
    private $refundPayment;
    /**
     * @var string
     */
    private $selectedTab;
    /**
     * @var float
     */
    private $totalRefunded;
    /**
     * @var float
     */
    private $totalValue;
    /**
     * @var string
     */
    private $currency;
    /**
     * @var string
     */
    private $currencySymbol;
    /**
     * @var bool
     */
    private $isOrderRefundable;
    /**
     * @var bool
     */
    private $isVoucher;

    /**
     * @return mixed
     */
    public function getSelectedTab()
    {
        return $this->selectedTab;
    }

    /**
     * @param mixed $selectedTab
     */
    public function setSelectedTab($selectedTab)
    {
        $this->selectedTab = $selectedTab;
    }

    /**
     * @return array
     */
    public function getRefundItems()
    {
        return $this->refundItems;
    }

    /**
     * @param array $refundItems
     */
    public function setRefundItems($refundItems)
    {
        $this->refundItems = $refundItems;
    }

    /**
     * @return bool
     */
    public function isOrderApiUsed()
    {
        return $this->isOrderApiUsed;
    }

    /**
     * @param bool $isOrderApiUsed
     */
    public function setIsOrderApiUsed($isOrderApiUsed)
    {
        $this->isOrderApiUsed = $isOrderApiUsed;
    }

    /**
     * @return MollieRefundPayment
     */
    public function getRefundPayment()
    {
        return $this->refundPayment;
    }

    /**
     * @param MollieRefundPayment $refundPayment
     */
    public function setRefundPayment($refundPayment)
    {
        $this->refundPayment = $refundPayment;
    }

    /**
     * @return float
     */
    public function getTotalRefunded()
    {
        return $this->totalRefunded;
    }

    /**
     * @param float $totalRefunded
     */
    public function setTotalRefunded($totalRefunded)
    {
        $this->totalRefunded = $totalRefunded;
    }

    /**
     * @return float
     */
    public function getTotalValue()
    {
        return $this->totalValue;
    }

    /**
     * @param float $totalValue
     */
    public function setTotalValue($totalValue)
    {
        $this->totalValue = $totalValue;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->currencySymbol;
    }

    /**
     * @param string $currencySymbol
     */
    public function setCurrencySymbol($currencySymbol)
    {
        $this->currencySymbol = $currencySymbol;
    }

    /**
     * @return bool
     */
    public function isOrderRefundable()
    {
        return $this->isOrderRefundable;
    }

    /**
     * @param bool $isOrderRefundable
     */
    public function setIsOrderRefundable($isOrderRefundable)
    {
        $this->isOrderRefundable = $isOrderRefundable;
    }

    /**
     * @return bool
     */
    public function isVoucher()
    {
        return $this->isVoucher;
    }

    /**
     * @param bool $isVoucher
     */
    public function setIsVoucher($isVoucher)
    {
        $this->isVoucher = $isVoucher;
    }
}
