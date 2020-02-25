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
}
