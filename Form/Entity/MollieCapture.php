<?php

namespace Mollie\Bundle\PaymentBundle\Form\Entity;

class MollieCapture
{
    /** @var float */
    private $amount;

    /** @var string */
    private $currency;

    /** @var string */
    private $currencySymbol;

    /** @var float */
    private $totalAuthorized = 0.0;

    /** @var float */
    private $totalCaptured = 0.0;

    /** @var float */
    private $totalValue = 0.0;

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function getCurrencySymbol()
    {
        return $this->currencySymbol;
    }

    public function setCurrencySymbol($currencySymbol)
    {
        $this->currencySymbol = $currencySymbol;
    }

    public function getTotalAuthorized()
    {
        return $this->totalAuthorized;
    }

    public function setTotalAuthorized($totalAuthorized)
    {
        $this->totalAuthorized = $totalAuthorized;
    }

    public function getTotalCaptured()
    {
        return $this->totalCaptured;
    }

    public function setTotalCaptured($totalCaptured)
    {
        $this->totalCaptured = $totalCaptured;
    }

    public function getTotalValue()
    {
        return $this->totalValue;
    }

    public function setTotalValue($totalValue)
    {
        $this->totalValue = $totalValue;
    }
}
