<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace Mollie\Bundle\PaymentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class MolliePaymentBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->updateOroIntegrationTransportTable($schema);
        $this->createMollieEntityTable($schema);
        $this->createMolliePaymentSettingsTable($schema);
        $this->createMolliePaymentSettingsDescriptionTable($schema);
        $this->createMolliePaymentSettingsNameTable($schema);

        /** Foreign keys generation **/
        $this->addMolliePaymentSettingsForeignKeys($schema);
        $this->addMolliePaymentSettingsDescriptionForeignKeys($schema);
        $this->addMolliePaymentSettingsNameForeignKeys($schema);

        $this->addExtensionSurchargeColumnToCoreTables($schema);
    }

    /**
     * @param Schema $schema
     */
    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        if (!$table->hasColumn('mollie_save_marker')) {
            $table->addColumn('mollie_save_marker', 'string', ['notnull' => false, 'length' => 255]);
        }
    }

    /**
     * Create mollie_entity table
     *
     * @param Schema $schema
     */
    protected function createMollieEntityTable(Schema $schema)
    {
        $table = $schema->createTable('mollie_entity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 128]);
        $table->addColumn('context', 'string', ['length' => 128, 'notnull' => false]);
        $table->addColumn('index1', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('index2', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('index3', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('index4', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('index5', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('index6', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('index7', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('data', 'text');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['type'], 'mollie_entity_type_idx');
        $table->addIndex(['type', 'index1'], 'mollie_entity_type_index1_idx');
        $table->addIndex(['type', 'index2'], 'mollie_entity_type_index2_idx');
        $table->addIndex(['type', 'index3'], 'mollie_entity_type_index3_idx');
        $table->addIndex(['type', 'index4'], 'mollie_entity_type_index4_idx');
        $table->addIndex(['type', 'index5'], 'mollie_entity_type_index5_idx');
        $table->addIndex(['type', 'index6'], 'mollie_entity_type_index6_idx');
        $table->addIndex(['type', 'index7'], 'mollie_entity_type_index7_idx');
        $table->addIndex(['type', 'context', 'index1'], 'mollie_entity_type_context_index1_idx');
        $table->addIndex(['type', 'context', 'index2'], 'mollie_entity_type_context_index2_idx');
        $table->addIndex(['type', 'context', 'index3'], 'mollie_entity_type_context_index3_idx');
        $table->addIndex(['type', 'context', 'index4'], 'mollie_entity_type_context_index4_idx');
        $table->addIndex(['type', 'context', 'index5'], 'mollie_entity_type_context_index5_idx');
        $table->addIndex(['type', 'context', 'index6'], 'mollie_entity_type_context_index6_idx');
        $table->addIndex(['type', 'context', 'index7'], 'mollie_entity_type_context_index7_idx');
    }

    /**
     * Create mollie_payment_settings table
     *
     * @param Schema $schema
     */
    protected function createMolliePaymentSettingsTable(Schema $schema)
    {
        $table = $schema->createTable('mollie_payment_settings');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('transport_id', 'integer', ['notnull' => false]);
        $table->addColumn('mollie_method_id', 'string', ['length' => 255]);
        $table->addColumn('mollie_method_description', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['transport_id'], 'IDX_AB11F0299909C13F', []);
    }

    /**
     * Create mollie_payment_settings_desc table
     *
     * @param Schema $schema
     */
    protected function createMolliePaymentSettingsDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('mollie_payment_settings_desc');
        $table->addColumn('payment_setting_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['payment_setting_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_919F823AEB576E89');
        $table->addIndex(['payment_setting_id'], 'IDX_919F823ADBC33CA7', []);
    }

    /**
     * Create mollie_payment_settings_name table
     *
     * @param Schema $schema
     */
    protected function createMolliePaymentSettingsNameTable(Schema $schema)
    {
        $table = $schema->createTable('mollie_payment_settings_name');
        $table->addColumn('payment_setting_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['payment_setting_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_19A6F9AAEB576E89');
        $table->addIndex(['payment_setting_id'], 'IDX_19A6F9AADBC33CA7', []);
    }

    /**
     * Add mollie_payment_settings foreign keys.
     *
     * @param Schema $schema
     */
    protected function addMolliePaymentSettingsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('mollie_payment_settings');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add mollie_payment_settings_desc foreign keys.
     *
     * @param Schema $schema
     */
    protected function addMolliePaymentSettingsDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('mollie_payment_settings_desc');
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
     * Add mollie_payment_settings_name foreign keys.
     *
     * @param Schema $schema
     */
    protected function addMolliePaymentSettingsNameForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('mollie_payment_settings_name');
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

    protected function addExtensionSurchargeColumnToCoreTables(Schema $schema)
    {
        $this->addExtensionSurchargeColumnToCoreTable($schema, 'oro_order');
        $this->addExtensionSurchargeColumnToCoreTable($schema, 'oro_checkout');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     *
     * @param string $tableName
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addExtensionSurchargeColumnToCoreTable(Schema $schema, $tableName)
    {
        $table = $schema->getTable($tableName);
        $table->addColumn(
            'mollie_surcharge_amount',
            'money',
            [
                'notnull' => false,
                'precision' => 19,
                'scale' => 4,
                'comment' => '(DC2Type:money)',
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_SYSTEM],
                ],
            ]
        );
    }
}
