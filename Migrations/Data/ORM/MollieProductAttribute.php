<?php

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class MollieProductAttribute
 */
class MollieProductAttribute extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /**
     * @var array[]
     */
    private static $selectOptions = [
        PaymentMethodConfig::VOUCHER_CATEGORY_NONE => [
            'name' => 'None',
            'is_default' => true,
        ],
        PaymentMethodConfig::VOUCHER_CATEGORY_GIFT => [
            'name' => 'Gift',
            'is_default' => false,
        ],
        PaymentMethodConfig::VOUCHER_CATEGORY_ECO => [
            'name' => 'Eco',
            'is_default' => false,
        ],
        PaymentMethodConfig::VOUCHER_CATEGORY_MEAL => [
            'name' => 'Meal',
            'is_default' => false,
        ],
    ];

    /**
     * @inheritdoc
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->addEnumOptions($manager);

        $this->makeProductAttributes(
            [
                \Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1\MollieProductAttribute::VOUCHER_CATEGORY_FIELD_NAME => []
            ],
            ExtendScope::OWNER_CUSTOM
        );

        $this->setDefaultFamily($manager);
    }

    /**
     * Add mollie voucher category options
     *
     * @param ObjectManager $manager
     */
    private function addEnumOptions(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName(\Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1\MollieProductAttribute::VOUCHER_CATEGORY_FIELD_CODE);

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        $priority = 1;
        foreach (static::$selectOptions as $id => $option) {
            $enumOption = $enumRepo->createEnumValue($option['name'], $priority++, $option['is_default']);
            $manager->persist($enumOption);
        }
    }

    /**
     * Set default family for mollie voucher category
     *
     * @param ObjectManager $manager
     */
    private function setDefaultFamily(ObjectManager $manager)
    {
        $defaultFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        $this->setReference(LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE, $defaultFamily);

        $attributeGroup = $defaultFamily->getAttributeGroup(LoadProductDefaultAttributeFamilyData::GENERAL_GROUP_CODE);

        $configManager = $this->getConfigManager();
        $variantField = $configManager->getConfigFieldModel(
            Product::class,
            \Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1\MollieProductAttribute::VOUCHER_CATEGORY_FIELD_NAME
        );

        $attributeGroupRelation = new AttributeGroupRelation();
        if ($variantField) {
            $attributeGroupRelation->setEntityConfigFieldId($variantField->getId());
            $attributeGroup->addAttributeRelation($attributeGroupRelation);
            $manager->persist($defaultFamily);
            $manager->flush();
        }
    }
}
