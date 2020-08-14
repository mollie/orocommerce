<?php

namespace Mollie\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Mollie\Bundle\PaymentBundle\Entity\ChannelSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Repository for PaymentSettings entity
 *
 * Class ChannelSettingsRepository
 *
 * @package Mollie\Bundle\PaymentBundle\Entity\Repository
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
     * @return ChannelSettings[]
     */
    public function getEnabledSettings()
    {
        $qb = $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true');

        return $this->aclHelper->apply($qb)->getResult();
    }
}
