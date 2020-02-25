<?php

namespace Mollie\Bundle\PaymentBundle\Entity\Repository;

use Mollie\Bundle\PaymentBundle\Entity\MollieBaseEntity;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Entity;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\IntegrationServices\ConfigurationService;

class MollieContextAwareEntityRepository extends MollieBaseEntityRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationServices\ConfigurationService
     */
    private $configurationService;

    public function __construct()
    {
        parent::__construct();

        $this->configurationService = ServiceRegister::getService(ConfigurationService::CLASS_NAME);
    }

    protected function getBaseDoctrineQuery(QueryFilter $filter = null, $isCount = false)
    {
        $query = parent::getBaseDoctrineQuery($filter, $isCount);

        $context = $this->configurationService->getContext();
        if (!empty($context)) {
            $alias = 'p';
            $query->andWhere("$alias.context = '{$context}'");
        }

        return $query;
    }

    protected function persistEntity(Entity $entity, MollieBaseEntity $persistedEntity)
    {
        $context = $this->configurationService->getContext();
        if (!empty($context)) {
            $persistedEntity->setContext($context);
        }

        return parent::persistEntity($entity, $persistedEntity);
    }
}