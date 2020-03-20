<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

class PaymentMethodController
{

    /**
     * Gets list of payment method configurations for all available Mollie payment methods.
     *
     * @param string $profileId Website profile id
     *
     * @return PaymentMethodConfig[]
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getAll($profileId)
    {
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = ServiceRegister::getService(PaymentMethodService::CLASS_NAME);
        return $paymentMethodService->getAllPaymentMethodConfigurations($profileId);
    }

    /**
     * Gets list of payment method configurations for enabled Mollie payment methods.
     *
     * @param string $profileId Website profile id
     * @param string|null $billingCountry The billing country of your customer in ISO 3166-1 alpha-2 format.
     * @param Amount|null $amount
     * @param string $apiMethod Api method to use for availability checking. Default is orders api
     *
     * @return PaymentMethodConfig[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function getEnabled(
        $profileId,
        $billingCountry = null,
        $amount = null,
        $apiMethod = PaymentMethodConfig::API_METHOD_ORDERS
    ) {
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = ServiceRegister::getService(PaymentMethodService::CLASS_NAME);
        return $paymentMethodService->getEnabledPaymentMethodConfigurations(
            $profileId,
            $billingCountry,
            $amount,
            $apiMethod
        );
    }

    /**
     * Saves list of payment method configurations
     *
     * @param PaymentMethodConfig[] $paymentMethodConfigs
     *
     * @throws RepositoryNotRegisteredException
     */
    public function save(array $paymentMethodConfigs)
    {
        $paymentMethodConfigsRepo = RepositoryRegistry::getRepository(PaymentMethodConfig::CLASS_NAME);
        foreach ($paymentMethodConfigs as $paymentMethodConfig) {
            if ($paymentMethodConfig->getId()) {
                $paymentMethodConfigsRepo->update($paymentMethodConfig);
            } else {
                $paymentMethodConfigsRepo->save($paymentMethodConfig);
            }
        }
    }
}