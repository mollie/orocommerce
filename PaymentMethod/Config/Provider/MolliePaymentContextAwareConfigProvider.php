<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpBaseException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class MolliePaymentContextAwareConfigProvider extends MolliePaymentConfigProvider implements
    MolliePaymentContextAwareConfigProviderInterface
{

    /**
     * @var \Oro\Bundle\PaymentBundle\Context\PaymentContextInterface
     */
    protected $context;

    public function setPaymentContext(PaymentContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getPaymentConfigs()
    {
        $cacheKey = 'no_context';
        if ($this->context) {
            $cacheKey = md5(
                $this->context->getBillingAddress() ? $this->context->getBillingAddress()->getCountryIso2() : ''.
                (string)$this->context->getTotal() .
                $this->context->getCurrency()
            );
        }

        if (empty($this->configs[$cacheKey])) {
            return $this->configs[$cacheKey] = $this->collectConfigs();
        }

        return $this->configs[$cacheKey];
    }
    
    /**
     * @return \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig[]
     */
    protected function getMolliePaymentMethodConfigs()
    {
        try {
            $amount = null;
            if ($this->context->getCurrency()) {
                $amount = Amount::fromArray([
                    'value' => (string)$this->context->getTotal(),
                    'currency' => $this->context->getCurrency(),
                ]);
            }

            $websiteProfile = $this->websiteProfileController->getCurrent();
            if (!$websiteProfile) {
                Logger::logWarning(
                    'No website profile could be found during payment configuration fetching.',
                    'Integration'
                );
                return [];
            }

            $billingAddress = $this->context->getBillingAddress();
            return $this->paymentMethodController->getEnabled(
                $websiteProfile->getId(),
                $billingAddress ? $billingAddress->getCountryIso2() : null,
                $amount
            );
        } catch (HttpBaseException $e) {
            Logger::logError(
                'Failed to load mollie payment method configuration',
                'Integration',
                [
                    'ExceptionMessage' => $e->getMessage(),
                    'ExceptionTrace' => $e->getTraceAsString(),
                ]
            );
            return [];
        }
    }
}