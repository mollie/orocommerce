<?php

namespace Mollie\Bundle\PaymentBundle\Form\Entity;

/**
 * Class MollieRefundPayment
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Entity
 */
class MollieRefundPayment
{
    /**
     * @var int
     */
    private $amount;
    /**
     * @var string
     */
    private $description;

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
