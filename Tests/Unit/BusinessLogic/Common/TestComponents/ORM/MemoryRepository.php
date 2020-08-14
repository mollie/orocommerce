<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Entity;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\MemoryRepository as InfrastructureMemoryRepository;

class MemoryRepository extends InfrastructureMemoryRepository implements RepositoryInterface
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Executes delete where query.
     *
     * @param QueryFilter $filter Filter for query.
     */
    public function deleteBy(QueryFilter $filter = null)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $entities = $this->select($filter);
        foreach ($entities as $entity) {
            $this->delete($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveOrUpdate(Entity $entity)
    {
        if (!$entity->getId()) {
            $this->save($entity);
        } else {
            $this->update($entity);
        }

        return $entity->getId();
    }
}
