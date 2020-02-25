<?php

namespace Mollie\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for PaymentSettings entity
 */
class ChannelSettingsRepository extends EntityRepository
{
    /**
     * @return \Mollie\Bundle\PaymentBundle\Entity\ChannelSettings[]
     */
    public function getEnabledSettings()
    {
        return $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true')
            ->getQuery()
            ->getResult();
    }
}