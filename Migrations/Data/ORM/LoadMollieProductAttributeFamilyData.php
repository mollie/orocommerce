<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1\MollieProductAttribute;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class MollieProductAttribute
 */
class LoadMollieProductAttributeFamilyData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use MakeProductAttributesTrait;

    /**
     * @inheritdoc
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $defaultFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        $this->setReference(LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE, $defaultFamily);

        $attributeGroup = $defaultFamily->getAttributeGroup(LoadProductDefaultAttributeFamilyData::GENERAL_GROUP_CODE);

        $configManager = $this->getConfigManager();
        $variantField = $configManager->getConfigFieldModel(
            Product::class,
            MollieProductAttribute::VOUCHER_CATEGORY_FIELD_NAME
        );

        $attributeGroupRelation = new AttributeGroupRelation();
        if ($variantField) {
            $attributeGroupRelation->setEntityConfigFieldId($variantField->getId());
            $attributeGroup->addAttributeRelation($attributeGroupRelation);
            $manager->persist($defaultFamily);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [
            LoadProductDefaultAttributeFamilyData::class,
        ];
    }
}
