<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\MemoryFooRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use PHPUnit\Framework\TestCase;

/***
 * Class RepositoryRegistryTest
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM
 */
class RepositoryRegistryTest extends TestCase
{
    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepository()
    {
        RepositoryRegistry::registerRepository(
            'test',
            MemoryRepository::getClassName()
        );

        $repository = RepositoryRegistry::getRepository('test');
        $this->assertInstanceOf(
            MemoryRepository::getClassName(),
            $repository
        );
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepositoryWrongRepo()
    {
        RepositoryRegistry::registerRepository('test', MemoryFooRepository::getClassName());

        $repository = RepositoryRegistry::getRepository('test');
        $this->assertNotEquals(
            MemoryRepository::getClassName(),
            $repository
        );
    }

    /**
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    public function testRegisterRepositoryWrongRepoClass()
    {
        RepositoryRegistry::registerRepository('test', '\PHPUnit\Framework\TestCase');
    }

    /**
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepositoryNotRegistered()
    {
        RepositoryRegistry::getRepository('test2');
    }
}
