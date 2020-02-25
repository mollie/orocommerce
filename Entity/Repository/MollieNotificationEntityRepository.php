<?php

namespace Mollie\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;

class MollieNotificationEntityRepository extends MollieContextAwareEntityRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * For notifications entity we need to have fresh connection to be able to isolate notification storage operations from
     * the rest of business logic transaction. This is crucial for cases when we detect failures and push new notifications
     * but transaction is opened on higher level and eventually rollback is called. If notifications are not using new
     * separate connection new notifications would be rejected and lost during transaction rollback.
     *
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $em = parent::getEntityManager();
            $this->entityManager = EntityManager::create($em->getConnection()->getParams(), $em->getConfiguration());
        }

        return $this->entityManager;
    }
}