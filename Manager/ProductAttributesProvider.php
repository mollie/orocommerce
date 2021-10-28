<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1\MollieProductAttribute;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ProductAttributesProvider
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class ProductAttributesProvider
{
    const ALIAS = 'product';

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var EntityAliasResolver
     */
    private $aliasResolver;
    /**
     * @var ConfigModelManager
     */
    private $configModelManager;

    /**
     * ProductAttributesProvider constructor.
     *
     * @param TranslatorInterface $translator
     * @param EntityAliasResolver $aliasResolver
     * @param ConfigModelManager $configModelManager
     */
    public function __construct(
        TranslatorInterface $translator,
        EntityAliasResolver $aliasResolver,
        ConfigModelManager $configModelManager
    ) {
        $this->translator = $translator;
        $this->aliasResolver = $aliasResolver;
        $this->configModelManager = $configModelManager;
    }


    /**
     * Returns product attribute options
     *
     * @return array
     */
    public function getProductAttributes()
    {
        $fields = $this->filterFields();

        $result = [];
        foreach ($fields as $field) {
            $label = $field->toArray('entity')['label'];
            $key = $field->getFieldName();
            $result[$key] = $this->translator->trans($label);
        }

        $this->setMollieVoucherAsDefault($result);
        $this->appendKeyToDuplicatedLabels($result);

        return array_flip($result);
    }

    /**
     * Sets mollie voucher category as default choice
     *
     * @param array $options
     */
    private function setMollieVoucherAsDefault(array &$options)
    {
        $key = MollieProductAttribute::VOUCHER_CATEGORY_FIELD_NAME;
        if (array_key_exists($key, $options)) {
            $tmp = [$key => $options[$key]];
            unset($options[$key]);
            $options = array_merge($tmp, $options);
        }
    }

    /**
     * Returns fields of string and enum type
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    private function filterFields()
    {
        $entityConfigModel = $this->getEntityProduct();
        $labelArray = [];

        return $entityConfigModel->getFields(
            function (FieldConfigModel $configModel) use ($labelArray) {
                if ($this->isVoucherAttribute($configModel)) {
                    $labelArray[] = $configModel->toArray('entity')['label'];
                }

                return $labelArray;
            }
        );
    }

    /**
     * @param FieldConfigModel $configModel
     *
     * @return bool
     */
    private function isVoucherAttribute(FieldConfigModel $configModel)
    {
        $extend = $configModel->toArray('extend');
        $isDeleted = $extend['is_deleted'] || $extend['state'] === 'Deleted';

        return !$isDeleted &&
            $configModel->toArray('attribute')['is_attribute'] &&
            in_array($configModel->getType(), ['string', 'enum'], true);
    }

    /**
     * Appends attribute key if there are labels with duplicated values
     *
     * @param array $result
     */
    private function appendKeyToDuplicatedLabels(&$result)
    {
        $duplicatedLabels = $this->getDuplicatedLabels($result);
        foreach ($result as $key => $value) {
            if (in_array($value, $duplicatedLabels, true)) {
                $result[$key] = $value . " ($key)";
            }
        }
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    private function getDuplicatedLabels($attributes)
    {
        // create count map (label as key, and count as value)
        $countMap = [];
        foreach ($attributes as $attributeLabel) {
            if (!array_key_exists($attributeLabel, $countMap)) {
                $countMap[$attributeLabel] = 0;
            }

            $countMap[$attributeLabel]++;
        }

        // return all labels which occurs more than one time
        $duplicated = [];
        foreach ($countMap as $label => $count) {
            if ($count > 1) {
                $duplicated[] = $label;
            }
        }

        return $duplicated;
    }

    /**
     * @return false|EntityConfigModel|null
     */
    private function getEntityProduct()
    {
        $entityClass = $this->aliasResolver->getClassByAlias(self::ALIAS);

        return $this->configModelManager->findEntityModel($entityClass);
    }
}
