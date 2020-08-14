<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;

class TestRepositoryRegistry extends RepositoryRegistry
{
    public static function cleanUp()
    {
        static::$repositories = array();
        static::$instantiated = array();
    }
}
