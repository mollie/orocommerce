<?php

namespace Mollie\Bundle\PaymentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class MollieProductAttribute
 */
class MollieProductAttribute extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use MakeProductAttributesTrait;

    /**
     * @inheritdoc
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->makeProductAttributes(
            [
                \Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1\MollieProductAttribute::VOUCHER_CATEGORY_FIELD_NAME => []
            ],
            ExtendScope::OWNER_CUSTOM
        );
    }

    public function getDependencies(): array
    {
        return [
            LoadProductDefaultAttributeFamilyData::class,
            LoadMollieVoucherCategoryData::class,
        ];
    }
}
