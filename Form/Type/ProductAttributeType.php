<?php


namespace Mollie\Bundle\PaymentBundle\Form\Type;


use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductAttributeType
{
    const ALIAS = 'product';

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
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
     * ProductAttributeService constructor.
     */
    public function __construct
    (
        TranslatorInterface $translator,
        EntityAliasResolver $aliasResolver,
        ConfigModelManager $configModelManager
    )
    {
        $this->translator = $translator;
        $this->configModelManager = $configModelManager;
        $this->aliasResolver = $aliasResolver;
    }


    public function getProductAttributes()
    {
        $entityConfigModel = $this->getEntityProduct();
        $labelArray = [];
        /** @var FieldConfigModel $fieldConfigModel */
        $fields = $entityConfigModel->getFields(
            function (FieldConfigModel $configModel) use ($labelArray) {
                $isAttribute = $configModel->toArray('attribute')['is_attribute'];
                $type = $configModel->getType();
                if ($isAttribute && ($type === 'string' || $type === 'enum')) {
                    $labelArray[] = $configModel->toArray('entity')['label'];
                }

                return $labelArray;
            });

        $labelArray = [];
        $labelArrayReturn = [];
        foreach ($fields as $field) {
            $label = $field->toArray('entity')['label'];
            $name = $field->getFieldName();
            if ($label === 'oro.product.voucher_category.label') {
                $labelArrayReturn['Voucher Category'] = $name;
            } else {
                $labelArray[$this->translator->trans($label)] = $name;
            }
        }

        return $this->createProductAttributeArray($labelArray,$labelArrayReturn);
    }

    /**
     * @param string $alias
     *
     * @return EntityConfigModel
     */
    public function getEntityProduct()
    {
        $entityClass = $this->aliasResolver->getClassByAlias(self::ALIAS);

        return $this->configModelManager->findEntityModel($entityClass);
    }

    private function createProductAttributeArray(array $labelArray, array $returnArray)
    {
        foreach ($labelArray as $label){
            $index = array_search($label, $labelArray);
            $returnArray[$index] = $label;
        }

        return $returnArray;
    }
}