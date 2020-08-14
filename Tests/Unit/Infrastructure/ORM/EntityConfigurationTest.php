<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Configuration\EntityConfiguration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Configuration\IndexMap;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityConfigurationTest
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM
 */
class EntityConfigurationTest extends TestCase
{
    public function testEntityConfiguration()
    {
        $map = new IndexMap();
        $type = 'test';
        $config = new EntityConfiguration($map, $type);

        $this->assertEquals($map, $config->getIndexMap());
        $this->assertEquals($type, $config->getType());
    }
}
