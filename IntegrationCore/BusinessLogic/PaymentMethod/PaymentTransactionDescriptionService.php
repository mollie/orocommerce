<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\DTO\DescriptionParameters;

/**
 * Class PaymentTransactionDescriptionService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod
 */
abstract class PaymentTransactionDescriptionService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Format payment method description (replace variables with the actual values)
     *
     * @param DescriptionParameters $descriptionParameters
     * @param $methodIdentifier
     *
     * @return string
     */
    public function formatPaymentDescription(DescriptionParameters $descriptionParameters, $methodIdentifier)
    {
        $description = $this->getDescription($methodIdentifier);

        return strtr($description, $descriptionParameters->toArray());
    }

    /**
     * Returns user defined payment description
     *
     * @param string $methodIdentifier
     *
     * @return string
     */
    abstract protected function getDescription($methodIdentifier);
}
