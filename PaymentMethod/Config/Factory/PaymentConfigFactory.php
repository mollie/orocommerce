<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory;

use Doctrine\Common\Collections\Collection;
use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfig;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

class PaymentConfigFactory implements PaymentConfigFactoryInterface
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var PaymentConfigIdentifierGenerator
     */
    private $identifierGenerator;

    /**
     * @param LocalizationHelper $localizationHelper
     * @param PaymentConfigIdentifierGenerator $identifierGenerator
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        PaymentConfigIdentifierGenerator $identifierGenerator
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->identifierGenerator = $identifierGenerator;
    }

    /**
     * @inheritDoc
     */
    public function create(PaymentMethodSettings $paymentMethodSetting)
    {
        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $channelSetting = $paymentMethodSetting->getChannelSettings();
        $channel = $channelSetting->getChannel();
        $mollieMethodConfig = $paymentMethodSetting->getPaymentMethodConfig();

        $paymentLabel = $this->getLocalizedValue($paymentMethodSetting->getDescriptions());
        $adminLabel = "{$channel->getName()} - {$paymentLabel}";
        $paymentIdentifier = $this->identifierGenerator->generateIdentifier($channel, $paymentMethodSetting);

        if ($paymentMethodSetting->getMollieMethodId() === MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
            $paymentIdentifier = $paymentMethodSetting->getMollieMethodId();
            $adminLabel = $paymentLabel;
        }

        $configParams = $configuration->doWithContext((string)$channel->getId(), function () use ($configuration) {
            return [
                MolliePaymentConfig::API_TOKEN => $configuration->getAuthorizationToken(),
                MolliePaymentConfig::TEST_MODE => $configuration->isTestMode(),
            ];
        });
        $configParams[MolliePaymentConfig::FIELD_LABEL] = $paymentLabel;
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
}