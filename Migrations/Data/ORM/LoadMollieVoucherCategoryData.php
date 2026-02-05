<?php

namespace Mollie\Bundle\PaymentBundle\Migrations\Data\ORM;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Mollie\Bundle\PaymentBundle\Migrations\Schema\v1_1\MollieProductAttribute as MollieProductAttributeMigration;

class LoadMollieVoucherCategoryData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getEnumCode(): string
    {
        return MollieProductAttributeMigration::VOUCHER_CATEGORY_FIELD_CODE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getData(): array
    {
        return [
            PaymentMethodConfig::VOUCHER_CATEGORY_NONE => 'None',
            PaymentMethodConfig::VOUCHER_CATEGORY_GIFT => 'Gift',
            PaymentMethodConfig::VOUCHER_CATEGORY_ECO => 'Eco',
            PaymentMethodConfig::VOUCHER_CATEGORY_MEAL => 'Meal',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue(): string
    {
        return PaymentMethodConfig::VOUCHER_CATEGORY_NONE;
    }
}
