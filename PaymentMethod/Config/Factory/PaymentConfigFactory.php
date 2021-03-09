<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\PaymentMethodController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfig;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * Class PaymentConfigFactory
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory
 */
class PaymentConfigFactory implements PaymentConfigFactoryInterface
{
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;
    /**
     * @var PaymentConfigIdentifierGenerator
     */
    private $identifierGenerator;
    /**
     * @var PaymentMethodController
     */
    private $methodController;

    /**
     * PaymentConfigFactory constructor.
     *
     * @param Configuration $configService
     * @param LocalizationHelper $localizationHelper
     * @param PaymentConfigIdentifierGenerator $identifierGenerator
     * @param PaymentMethodController $methodController
     */
    public function __construct(
        Configuration $configService,
        LocalizationHelper $localizationHelper,
        PaymentConfigIdentifierGenerator $identifierGenerator,
        PaymentMethodController $methodController
    ) {
        $this->configService = $configService;
        $this->localizationHelper = $localizationHelper;
        $this->identifierGenerator = $identifierGenerator;
        $this->methodController = $methodController;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PaymentMethodSettings $paymentMethodSetting)
    {
        $channelSetting = $paymentMethodSetting->getChannelSettings();
        $channel = $channelSetting->getChannel();
        $mollieMethodConfig = $paymentMethodSetting->getPaymentMethodConfig();

        $paymentDescription = $this->getLocalizedValue($paymentMethodSetting->getPaymentDescriptions());
        $transactionDescription = $this->getLocalizedValue($paymentMethodSetting->getTransactionDescriptions());

        $paymentLabel = $this->getLocalizedValue($paymentMethodSetting->getDescriptions());
        $adminLabel = "{$channel->getName()} - {$paymentLabel}";
        $paymentIdentifier = $this->identifierGenerator->generateIdentifier($channel, $paymentMethodSetting);

        if ($paymentMethodSetting->getMollieMethodId() === MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
            $paymentIdentifier = $paymentMethodSetting->getMollieMethodId();
            $adminLabel = $paymentLabel;
        }

        $configParams = $this->configService->doWithContext((string)$channel->getId(), function () {
            return [
                MolliePaymentConfig::API_TOKEN => $this->configService->getAuthorizationToken(),
                MolliePaymentConfig::TEST_MODE => $this->configService->isTestMode(),
            ];
        });

        $useMollieComponents = $mollieMethodConfig->useMollieComponents() && $mollieMethodConfig->getMollieId() === 'creditcard';

        $configParams[MolliePaymentConfig::FIELD_LABEL] = $paymentLabel;
        $configParams[MolliePaymentConfig::PAYMENT_DESCRIPTION] = $paymentDescription;
        $configParams[MolliePaymentConfig::TRANSACTION_DESCRIPTION] = $transactionDescription;
        $configParams[MolliePaymentConfig::FIELD_SHORT_LABEL] = $this->getLocalizedValue($paymentMethodSetting->getNames());
        $configParams[MolliePaymentConfig::FIELD_ADMIN_LABEL] = $adminLabel;
        $configParams[MolliePaymentConfig::FIELD_PAYMENT_METHOD_IDENTIFIER] = $paymentIdentifier;
        $configParams[MolliePaymentConfig::ICON] = $mollieMethodConfig->getImage();
        $configParams[MolliePaymentConfig::MOLLIE_ID] = $mollieMethodConfig->getMollieId();
        $configParams[MolliePaymentConfig::API_METHOD] = $mollieMethodConfig->getApiMethod();
        $configParams[MolliePaymentConfig::IS_API_METHOD_RESTRICTED] = $mollieMethodConfig->isApiMethodRestricted();
        $configParams[MolliePaymentConfig::PROFILE_ID] = $mollieMethodConfig->getProfileId();
        $configParams[MolliePaymentConfig::CHANNEL_ID] = $channel->getId();
        $configParams[MolliePaymentConfig::SURCHARGE_AMOUNT] = $mollieMethodConfig->getSurcharge();
        $configParams[MolliePaymentConfig::ISSUER_LIST_STYLE] = $mollieMethodConfig->getIssuerListStyle();
        $configParams[MolliePaymentConfig::USE_MOLLIE_COMPONENTS] = $useMollieComponents;
        $configParams[MolliePaymentConfig::ISSUERS] = $this->getIssuers($mollieMethodConfig, $channel->getId());
        $configParams[MolliePaymentConfig::ORDER_EXPIRY_DAYS] = $mollieMethodConfig->getDaysToOrderExpire();
        $configParams[MolliePaymentConfig::PAYMENT_EXPIRY_DAYS] = $mollieMethodConfig->getDaysToPaymentExpire();

        return new MolliePaymentConfig($configParams);
    }

    /**
     * @param Collection $values
     *
     * @return string
     */
    private function getLocalizedValue(Collection $values)
    {
        return (string)$this->localizationHelper->getLocalizedValue($values);
    }

    /**
     * @param PaymentMethodConfig $paymentMethod
     * @param int $channelId
     *
     * @return array
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    private function getIssuers(PaymentMethodConfig $paymentMethod, $channelId)
    {
        $issuers = [];
        $enabledMethodConfig = $this->getEnableConfiguration($paymentMethod, $channelId);
        if (!$enabledMethodConfig) {
            return $issuers;
        }

        foreach ($enabledMethodConfig->getOriginalAPIConfig()->getIssuers() as $issuer) {
            $issuers[] = $issuer->toArray();
        }

        return $issuers;
    }

    /**
     * @param PaymentMethodConfig $paymentMethod
     * @param int $channelId
     *
     * @return PaymentMethodConfig|null
     */
    private function getEnableConfiguration(PaymentMethodConfig $paymentMethod, $channelId)
    {
        return $this->configService->doWithContext($channelId, function () use ($paymentMethod) {
            if ($paymentMethod->isEnabled() && $paymentMethod->isIssuerListSupported()) {
                $enabledMethods = $this->methodController->getEnabled($paymentMethod->getProfileId());
                foreach ($enabledMethods as $enabledMethod) {
                    if ($enabledMethod->getMollieId() === $paymentMethod->getMollieId()) {
                        return $enabledMethod;
                    }
                }
            }

            return null;
        });
    }
}
