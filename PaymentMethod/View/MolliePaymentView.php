<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class MolliePaymentView implements PaymentMethodViewInterface
{
    /**
     * @var MolliePaymentConfigInterface
     */
    protected $config;

    /**
     * @param MolliePaymentConfigInterface $config
     */
    public function __construct(MolliePaymentConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(PaymentContextInterface $context)
    {
        return [
            'isApplePay' => false !== strpos($this->config->getPaymentMethodIdentifier(), 'applepay'),
            'icon' => $this->config->getIcon(),
            'isSurchargeSupported' => $this->config->isSurchargeSupported(),
            'surchargeAmount' => $this->config->getSurchargeAmount(),
            'currency' => $context->getCurrency(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlock()
    {
        return '_payment_methods_mollie_payment_widget';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }
}