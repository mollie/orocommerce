<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM\Entity;

use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\Entity\StudentEntity;

/**
 * Class StudentTest.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\ORM\Entity
 */
class StudentTest extends GenericEntityTest
{
    /**
     * Returns entity full class name
     *
     * @return string
     */
    public function getEntityClass()
    {
        return StudentEntity::getClassName();
    }
}
