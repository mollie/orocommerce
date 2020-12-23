<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class TestServiceRegister.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common
 */
class TestServiceRegister extends ServiceRegister
{
    /**
     * TestServiceRegister constructor.
     *
     * @inheritdoc
     */
    public function __construct(array $services = array())
    {
        // changing visibility so that services could be reset in tests.
        parent::__construct($services);
    }
}
