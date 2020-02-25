<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Mollie\Bundle\PaymentBundle\Entity\ChannelSettings;
use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\Entity\Repository\ChannelSettingsRepository;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\PaymentMethodController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\WebsiteProfileController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpBaseException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory\PaymentConfigFactoryInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

class MolliePaymentConfigProvider implements MolliePaymentConfigProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var PaymentConfigInterface
     */
    protected $configFactory;

    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\PaymentMethodController
     */
    protected $paymentMethodController;

    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\WebsiteProfileController
     */
    protected $websiteProfileController;

    /**
     * @var MolliePaymentConfigInterface[]
     */
    protected $configs = [];

    /**
     * @param ManagerRegistry $doctrine
     * @param PaymentConfigFactoryInterface $configFactory
     * @param PaymentMethodController $paymentMethodController
     * @param WebsiteProfileController $websiteProfileController
     */
    public function __construct(
        ManagerRegistry $doctrine,
        PaymentConfigFactoryInterface $configFactory,
        PaymentMethodController $paymentMethodController,
        WebsiteProfileController $websiteProfileController
    ) {
        $this->doctrine = $doctrine;
        $this->configFactory = $configFactory;
        $this->paymentMethodController = $paymentMethodController;
        $this->websiteProfileController = $websiteProfileController;
    }

    /**
     * @inheritDoc
     */
    public function getPaymentConfigs()
    {
        if (0 === count($this->configs)) {
            return $this->configs = $this->collectConfigs();
        }

        return $this->configs;
    }

    /**
     * @inheritDoc
     */
    public function getPaymentConfig($identifier)
    {
        if (!$this->hasPaymentConfig($identifier)) {
            return null;
        }

        $configs = $this->getPaymentConfigs();

        return $configs[$identifier];
    }

    /**
     * @inheritDoc
     */
    public function hasPaymentConfig($identifier)
    {
        $configs = $this->getPaymentConfigs();

        return array_key_exists($identifier, $configs);
    }

    /**
     * @return MolliePaymentConfigInterface[]
     */
    protected function collectConfigs()
    {
        $paymentConfigs = [];

        foreach ($this->getEnabledPaymentMethodSettings() as $paymentMethodSetting) {
            $config = $this->configFactory->create($paymentMethodSetting);
            $paymentConfigs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $paymentConfigs;
    }

    /**
     * @return PaymentMethodSettings[]
     */
    protected function getEnabledPaymentMethodSettings()
    {
        $enabledPaymentMethodSettings = [];
        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);

        foreach ($this->getEnabledIntegrationSettings() as $channelSetting) {
            $enabledPaymentMethodSettings[] =  $configuration->doWithContext(
                (string)$channelSetting->getChannel()->getId(),
                function () use ($channelSetting) {
                    return $this->getPaymentMethodConfigurations($channelSetting);
                }
            );
        }

        return array_merge(...$enabledPaymentMethodSettings);
    }

    /**
     * @return ChannelSettings[]
     */
    protected function getEnabledIntegrationSettings()
    {
        try {
            $manager = $this->doctrine->getManagerForClass(ChannelSettings::class);
            if (!$manager) {
                Logger::logWarning(
                    'No payment methods found because doctrine object manager for class "ChannelSettings" does not exit.',
                    'Integration'
                );
                return [];
            }

            /** @var ChannelSettingsRepository $repository */
            $repository = $manager->getRepository(ChannelSettings::class);

            return $repository->getEnabledSettings();
        } catch (\UnexpectedValueException $e) {
            Logger::logError(
                'Failed to load mollie integration configuration',
                'Integration',
                [
                    'ExceptionMessage' => $e->getMessage(),
                    'ExceptionTrace' => $e->getTraceAsString(),
                ]
            );

            return [];
        }
    }

    /**
     * @param \Mollie\Bundle\PaymentBundle\Entity\ChannelSettings $channelSettings
     *
     * @return PaymentMethodSettings[]
     */
    protected function getPaymentMethodConfigurations(ChannelSettings $channelSettings)
    {
        $savedPaymentMethodSettingsMap = [];
        foreach ($channelSettings->getPaymentMethodSettings() as $paymentMethodSetting) {
            $savedPaymentMethodSettingsMap[$paymentMethodSetting->getMollieMethodId()] = $paymentMethodSetting;
        }

        $enabledPaymentMethodSettings = [];
        $paymentMethodConfigs = $this->getMolliePaymentMethodConfigs();
        foreach ($paymentMethodConfigs as $paymentMethodConfig) {

            if (!$paymentMethodConfig->isEnabled()) {
                continue;
            }

            $paymentMethodSetting = null;
            if (array_key_exists($paymentMethodConfig->getMollieId(), $savedPaymentMethodSettingsMap)) {
                $paymentMethodSetting = $savedPaymentMethodSettingsMap[$paymentMethodConfig->getMollieId()];
            }

            if (!$paymentMethodSetting) {
                $paymentMethodSetting = new PaymentMethodSettings();
                $paymentMethodSetting->setChannelSettings($channelSettings);
                $channelSettings->addPaymentMethodSetting($paymentMethodSetting);

                $paymentMethodSetting->setMollieMethodId($paymentMethodConfig->getMollieId());
                $paymentMethodSetting->setMollieMethodDescription($paymentMethodConfig->getOriginalAPIConfig()->getDescription());
            }

            if ($paymentMethodSetting->getNames()->isEmpty()) {
                $paymentMethodSetting->addName(
                    (new LocalizedFallbackValue())->setString($paymentMethodConfig->getMollieId())
                );
            }

            if ($paymentMethodSetting->getDescriptions()->isEmpty()) {
                $paymentMethodSetting->addDescription(
                    (new LocalizedFallbackValue())->setString(
                        $paymentMethodConfig->getOriginalAPIConfig()->getDescription()
                    )
                );
            }

            $paymentMethodSetting->setPaymentMethodConfig($paymentMethodConfig);
            $paymentMethodSetting->setEnabled($paymentMethodConfig->isEnabled());
            $paymentMethodSetting->setSurcharge($paymentMethodConfig->getSurcharge());
            $paymentMethodSetting->setMethod($paymentMethodConfig->getApiMethod());
            $paymentMethodSetting->setOriginalImagePath($paymentMethodConfig->getOriginalAPIConfig()->getImage()->getSize2x());
            $paymentMethodSetting->setImagePath(
                $paymentMethodConfig->hasCustomImage() ? $paymentMethodConfig->getImage() : null
            );

            $enabledPaymentMethodSettings[] = $paymentMethodSetting;
        }

        return $enabledPaymentMethodSettings;
    }

    /**
     * @return \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig[]
     */
    protected function getMolliePaymentMethodConfigs()
    {
        try {
            $websiteProfile = $this->websiteProfileController->getCurrent();
            if (!$websiteProfile) {
                Logger::logWarning(
                    'No payment methods found because website profile is not set in configuration.',
                    'Integration'
                );
                return [];
            }

            return $this->paymentMethodController->getAll($websiteProfile->getId());
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