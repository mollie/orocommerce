<?php


namespace Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MollieProductAttribute implements Migration, ExtendExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'oro_product';
    const VOUCHER_CATEGORY_FIELD_NAME = 'mollie_voucher_category';
    const VOUCHER_CATEGORY_FIELD_CODE = 'mollie_voucher';

    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /**
     * @inheritdoc
     * @param Schema $schema
     * @param QueryBag $queries
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addMollieProductAttribute($schema);
    }

    /**
     * @inheritdoc
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Adds Mollie Voucher column
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addMollieProductAttribute(Schema $schema)
    {
        if ($schema->hasTable(self::PRODUCT_TABLE_NAME)) {
            $table = $schema->getTable('oro_product');

            $this->extendExtension->addEnumField(
                $schema,
                $table,
                self::VOUCHER_CATEGORY_FIELD_NAME,
                self::VOUCHER_CATEGORY_FIELD_CODE,
                false,
                false,
                [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'entity' => ['label' => 'mollie.payment.extend.entity.voucher_category.label'],
                    'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                    'importexport' => ['excluded' => true]
                ]
            );
        }
    }
}
