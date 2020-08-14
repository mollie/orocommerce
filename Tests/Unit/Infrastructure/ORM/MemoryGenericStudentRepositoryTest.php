<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM;

use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\MemoryStorage;

/**
 * Class MemoryGenericStudentRepositoryTest.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM
 */
class MemoryGenericStudentRepositoryTest extends AbstractGenericStudentRepositoryTest
{
    /**
     * @return string
     */
    public function getStudentEntityRepositoryClass()
    {
        return MemoryRepository::getClassName();
    }

    /**
     * Cleans up all storage services used by repositories
     */
    public function cleanUpStorage()
    {
        MemoryStorage::reset();
    }
}
