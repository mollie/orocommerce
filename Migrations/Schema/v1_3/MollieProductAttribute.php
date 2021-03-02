<?php


namespace Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_3;


use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MollieProductAttribute implements Migration, ExtendExtensionAwareInterface
{

    const PRODUCT_TABLE_NAME = 'oro_product';

    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addMollieProductAttribute($schema);
    }

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    protected function addMollieProductAttribute(Schema $schema)
    {
        if ($schema->hasTable(self::PRODUCT_TABLE_NAME)) {
            $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
            $this->extendExtension->addEnumField(
                $schema,
                $table,
                'voucher_category',
                'voucher_category',
                false,
                false,
                [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'importexport' => ['excluded' => true]
                ]
            );
        }
    }
}