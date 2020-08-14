<?php

namespace Mollie\Bundle\PaymentBundle;

use Mollie\Bundle\PaymentBundle\DependencyInjection\PaymentExtension;
use Mollie\Bundle\PaymentBundle\IntegrationServices\BootstrapComponent;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MolliePaymentBundle
 *
 * @package Mollie\Bundle\PaymentBundle
 */
class MolliePaymentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        parent::boot();
        BootstrapComponent::boot($this->container);
    }
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new PaymentExtension();
        }

        return $this->extension;
    }
}
