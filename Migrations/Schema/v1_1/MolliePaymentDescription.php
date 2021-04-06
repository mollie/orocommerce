<?php

namespace Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MolliePaymentDescription implements Migration
{
    const PAYMENT_DESCRIPTION_TABLE = 'mollie_payment_settings_p_des';
    const TRANSACTION_DESCRIPTION_TABLE = 'mollie_payment_trans_desc';

    /**
     * {@inheritdoc}
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable(self::PAYMENT_DESCRIPTION_TABLE)) {
            $this->createMolliePaymentSettingsPaymentDescriptionTable($schema);
            $this->addMolliePaymentSettingsPaymentDescriptionForeignKeys($schema);
        }

        if (!$schema->hasTable(self::TRANSACTION_DESCRIPTION_TABLE)) {
            $this->createMollieTransactionDescriptionTable($schema);
            $this->addMolliePaymentTransactionForeignKeys($schema);
        }
    }
    

    /**
     * Creates mollie_payment_settings_p_des table
     *
     * @param Schema $schema
     */
    protected function createMolliePaymentSettingsPaymentDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::PAYMENT_DESCRIPTION_TABLE);
        $table->addColumn('payment_setting_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['payment_setting_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_Payment_Desc');
        $table->addIndex(['payment_setting_id'], 'IDX_Payment_Desc', []);
    }

    /**
     * Add mollie_payment_settings_p_des foreign keys.
     *
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    protected function addMolliePaymentSettingsPaymentDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PAYMENT_DESCRIPTION_TABLE);
        $table->addForeignKeyConstraint(
            $schema->getTable('mollie_payment_settings'),
            ['payment_setting_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create mollie_payment_settings_name table
     *
     * @param Schema $schema
     */
    protected function createMollieTransactionDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::TRANSACTION_DESCRIPTION_TABLE);
        $table->addColumn('payment_setting_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['payment_setting_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_Payment_Trans');
        $table->addIndex(['payment_setting_id'], 'IDX_Payment_Trans', []);
    }

    /**
     * Add mollie_payment_settings_p_des foreign keys.
     *
     * @param Schema $schema
     *
     * @throws SchemaException
     */
    protected function addMolliePaymentTransactionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::TRANSACTION_DESCRIPTION_TABLE);
        $table->addForeignKeyConstraint(
            $schema->getTable('mollie_payment_settings'),
            ['payment_setting_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
