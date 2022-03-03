<?php

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MollieSingleClickPayment implements Migration
{
    const SINGLE_CLICK_PAYMENT_APPROVAL_TEXT = 'mollie_single_click_appr_text';
    const SINGLE_CLICK_PAYMENT_DESCRIPTION = 'mollie_single_click_desc';

    /**
     * {@inheritdoc}
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable(self::SINGLE_CLICK_PAYMENT_APPROVAL_TEXT)) {
            $this->createMolliePaymentSettingsSingleClickApprovalTextTable($schema);
            $this->addMolliePaymentSettingsSingleSlickApprovalTextForeignKeys($schema);
        }

        if (!$schema->hasTable(self::SINGLE_CLICK_PAYMENT_DESCRIPTION)) {
            $this->createMolliePaymentSettingsSingleClickDescriptionTable($schema);
            $this->addMolliePaymentSettingsSingleSlickDescriptionForeignKeys($schema);
        }
    }

    /**
     * Create mollie_single_click_appr_text table
     *
     * @param Schema $schema
     */
    protected function createMolliePaymentSettingsSingleClickApprovalTextTable(Schema $schema)
    {
        $table = $schema->createTable(self::SINGLE_CLICK_PAYMENT_APPROVAL_TEXT);
        $table->addColumn('payment_setting_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['payment_setting_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_Single_Click_Approval_Text');
        $table->addIndex(['payment_setting_id'], 'IDX_Single_Click_Approval_Text', []);
    }

    /**
     * Create mollie_single_click_desc table
     *
     * @param Schema $schema
     */
    protected function createMolliePaymentSettingsSingleClickDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::SINGLE_CLICK_PAYMENT_DESCRIPTION);
        $table->addColumn('payment_setting_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['payment_setting_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_Single_Click_Description');
        $table->addIndex(['payment_setting_id'], 'IDX_Single_Click_Description', []);
    }

    /**
     * Add mollie_single_click_appr_text foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addMolliePaymentSettingsSingleSlickApprovalTextForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::SINGLE_CLICK_PAYMENT_APPROVAL_TEXT);
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
     * Add mollie_single_click_desc foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addMolliePaymentSettingsSingleSlickDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::SINGLE_CLICK_PAYMENT_DESCRIPTION);
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
