<?php

namespace Mollie\Bundle\PaymentBundle\Entity;

/**
 * Interface MollieSurchargeAwareInterface
 *
 * @package Mollie\Bundle\PaymentBundle\Entity
 */
interface MollieSurchargeAwareInterface
{

    /**
     * @return float|null
     */
    public function getMollieSurchargeAmount();

    /**
     * @param float $surchargeAmount
     */
    public function setMollieSurchargeAmount($surchargeAmount);
}
