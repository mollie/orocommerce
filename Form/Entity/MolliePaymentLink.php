<?php

namespace Mollie\Bundle\PaymentBundle\Form\Entity;

/**
 * Class MolliePaymentLink
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Entity
 */
class MolliePaymentLink
{
    /**
     * @var string
     */
    private $paymentLink;
    /**
     * @var bool
     */
    private $isMolliePaymentOnOrder = false;
    /**
     * @var bool
     */
    private $isPaymentsApiOnly = false;
    /**
     * @var array
     */
    private $paymentMethods = [];
    /**
     * @var string[]
     */
    private $selectedPaymentMethods = [];

    /**
     * @return string
     */
    public function getPaymentLink()
    {
        return $this->paymentLink;
    }

    /**
     * @param string $paymentLink
     */
    public function setPaymentLink($paymentLink)
    {
        $this->paymentLink = $paymentLink;
    }

    /**
     * @return bool
     */
    public function isMolliePaymentOnOrder(): bool
    {
        return $this->isMolliePaymentOnOrder;
    }

    /**
     * @param bool $isMolliePaymentOnOrder
     */
    public function setIsMolliePaymentOnOrder($isMolliePaymentOnOrder)
    {
        $this->isMolliePaymentOnOrder = (bool)$isMolliePaymentOnOrder;
    }

    /**
     * @return bool
     */
    public function isPaymentsApiOnly(): bool
    {
        return $this->isPaymentsApiOnly;
    }

    /**
     * @param bool $isPaymentsApiOnly
     */
    public function setIsPaymentsApiOnly($isPaymentsApiOnly)
    {
        $this->isPaymentsApiOnly = (bool)$isPaymentsApiOnly;
    }

    /**
     * @return array
     */
    public function getPaymentMethods(): array
    {
        return $this->paymentMethods;
    }

    /**
     * @param array $paymentMethods
     */
    public function setPaymentMethods(array $paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * @return string[]
     */
    public function getSelectedPaymentMethods(): array
    {
        return $this->selectedPaymentMethods;
    }

    /**
     * @param string[] $selectedPaymentMethods
     */
    public function setSelectedPaymentMethods(array $selectedPaymentMethods)
    {
        $this->selectedPaymentMethods = $selectedPaymentMethods;
    }
}
