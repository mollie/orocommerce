<?php

namespace Mollie\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Repository for PaymentSettings entity
 */
class ChannelSettingsRepository extends EntityRepository
{
    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @return \Mollie\Bundle\PaymentBundle\Entity\ChannelSettings[]
     */
    public function getEnabledSettings()
    {
        $qb = $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true');

        return $this->aclHelper->apply($qb)->getResult();
    }
}