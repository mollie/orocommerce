<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\ConfigEntity;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\IntermediateObject;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Utility\EntityTranslator;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\Entity\FooEntity;

/**
 * Class EntityTranslatorTest.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM
 */
class EntityTranslatorTest extends BaseInfrastructureTestWithServices
{
    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\EntityClassException
     * @throws \Exception
     */
    public function testTranslate()
    {
        $entity = new ConfigEntity();
        $entity->setName('test');
        $entity->setId(123);
        $entity->setSystemId('Test system');
        $entity->setValue(time());

        $intermediate = new IntermediateObject();
        $intermediate->setData(serialize($entity));

        $translator = new EntityTranslator();
        $translator->init(ConfigEntity::getClassName());
        $entities = $translator->translate(array($intermediate));

        $this->assertEquals($entity, $entities[0]);
    }

    /**
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testTranslateWithoutInit()
    {
        $intermediate = new IntermediateObject();
        $translator = new EntityTranslator();
        $translator->translate(array($intermediate));
    }

    /**
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testInitOnNonEntity()
    {
        $translator = new EntityTranslator();
        $translator->init('\Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\IntermediateObject');
    }

    /**
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\EntityClassException
     */
    public function testTranslateWrongEntity()
    {
        $entity = new FooEntity();

        $intermediate = new IntermediateObject();
        $intermediate->setData(serialize($entity));

        $translator = new EntityTranslator();
        $translator->init(ConfigEntity::getClassName());
        $translator->translate(array($intermediate));
    }
}
