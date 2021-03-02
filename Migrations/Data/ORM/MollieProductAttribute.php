<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class MollieProductAttribute extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;

    public function load(\Doctrine\Common\Persistence\ObjectManager $objectManager)
    {
        $this->makeProductAttributes(
            [
                'voucher_category' => []
            ],
            ExtendScope::OWNER_SYSTEM
        );
    }

    public function getDependencies()
    {
        return [
            LoadLocalizationData::class
        ];
    }
}