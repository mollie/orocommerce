<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\EventListener\AbstractSurchargeListener;
use Oro\Bundle\PaymentBundle\Model\Surcharge;

/**
 * Class MollieSurchargeListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class MollieSurchargeListener extends AbstractSurchargeListener
{
    /**
     * {@inheritdoc}
     */
    protected function setAmount(Surcharge $model, $amount)
    {
        $model->setHandlingAmount($model->getHandlingAmount() + $amount);
    }
}
