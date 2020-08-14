<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Interfaces\RepositoryInterface;

/**
 * Class MemoryFooRepository.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM
 */
class MemoryFooRepository extends MemoryRepository implements RepositoryInterface
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;
}
