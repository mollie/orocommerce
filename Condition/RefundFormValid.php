<?php

namespace Mollie\Bundle\PaymentBundle\Condition;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefundLineItem;
use Mollie\Bundle\PaymentBundle\Manager\MollieRefundProvider;
use Oro\Component\Action\Condition\AbstractCondition;

/**
 * Class RefundFormValidRules
 *
 * @package Mollie\Bundle\PaymentBundle\Condition
 */
class RefundFormValid extends AbstractCondition
{
    const NAME = 'mollie_refund_form_valid';

    public function getName()
    {
        return static::NAME;
    }

    public function initialize(array $options)
    {
        reset($options);

        return $this;
    }

    /**
     * Validates input
     *
     * @param mixed $context
     *
     * @return bool
     */
    protected function isConditionAllowed($context)
    {
        /** @var MollieRefund $mollieRefund */
        $mollieRefund = $context->get('mollieRefund');
        if (!$mollieRefund) {
            return false;
        }

        if ($mollieRefund->getSelectedTab() === MollieRefundProvider::PAYMENT_REFUND) {
            if (!is_numeric($mollieRefund->getRefundPayment()->getAmount())) {
                return false;
            }
        } else {
            /** @var MollieRefundLineItem $item */
            foreach ($mollieRefund->getRefundItems() as $item) {
                $quantityToRefund = $item->getQuantityToRefund() !== null ? $item->getQuantityToRefund() : 0;
                if (!preg_match("/^[\d]+$/", $quantityToRefund)) {
                    return false;
                }
            }
        }

        return true;
    }
}
