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
     * @var MollieRefundPayment
     */
    private $refundPayment;
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
    private $isVoucher;

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
