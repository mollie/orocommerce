<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Mollie\Bundle\PaymentBundle\Entity\ChannelSettings;
use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\Entity\Repository\ChannelSettingsRepository;
use Mollie\Bundle\PaymentBundle\Form\Type\PaymentMethodSettingsType;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Image;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\PaymentMethodController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\WebsiteProfileController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpBaseException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory\PaymentConfigFactoryInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class MolliePaymentConfigProvider
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider
 */
class MolliePaymentConfigProvider implements MolliePaymentConfigProviderInterface
{
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var PaymentConfigInterface
     */
    protected $configFactory;

    /**
     * @var PaymentMethodController
     */
    protected $paymentMethodController;

    /**
     * @var WebsiteProfileController
     */
    protected $websiteProfileController;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var MolliePaymentConfigInterface[]
     */
    protected $configs = [];

    /**
     * MolliePaymentConfigProvider constructor.
     *
     * @param Configuration $configService
     * @param ManagerRegistry $doctrine
     * @param PaymentConfigFactoryInterface $configFactory
     * @param PaymentMethodController $paymentMethodController
     * @param WebsiteProfileController $websiteProfileController
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Configuration $configService,
        ManagerRegistry $doctrine,
        PaymentConfigFactoryInterface $configFactory,
        PaymentMethodController $paymentMethodController,
        WebsiteProfileController $websiteProfileController,
        TranslatorInterface $translator
    ) {
        $this->configService = $configService;
        $this->doctrine = $doctrine;
        $this->configFactory = $configFactory;
        $this->paymentMethodController = $paymentMethodController;
        $this->websiteProfileController = $websiteProfileController;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfigs()
    {
        if (0 === count($this->configs)) {
            return $this->configs = $this->collectConfigs();
        }

        return $this->configs;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
            if (empty($paymentConfigs)) {
                $paymentLinkMethod = $this->createPaymentLinkMethodSetting(
                    $paymentMethodSetting->getChannelSettings(),
                    PaymentMethodConfig::fromArray($paymentMethodSetting->getPaymentMethodConfig()->toArray())
                );

                $paymentLinkConfig = $this->configFactory->create($paymentLinkMethod);
                $paymentConfigs[$paymentLinkConfig->getPaymentMethodIdentifier()] = $paymentLinkConfig;
            }

            $config = $this->configFactory->create($paymentMethodSetting);
            $paymentConfigs[$config->getPaymentMethodIdentifier()] = $config;
        }

        return $paymentConfigs;
    }

    /**
     * @param ChannelSettings $channelSettings
     * @param PaymentMethodConfig $paymentMethodConfig
     *
     * @return PaymentMethodSettings
     */
    protected function createPaymentLinkMethodSetting(
        ChannelSettings $channelSettings,
        PaymentMethodConfig $paymentMethodConfig
    ) {
        $paymentLinkMethod = new PaymentMethodSettings();
        $paymentLinkMethod->setEnabled(true);
        $paymentLinkMethod->setChannelSettings($channelSettings);
        $paymentLinkMethod->setMollieMethodId(MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID);
        $paymentLinkMethod->addName((new LocalizedFallbackValue())->setString('Admin payment link'));
        $paymentLinkMethod->addDescription((new LocalizedFallbackValue())->setString('Admin payment link'));
        $paymentLinkMethod->setPaymentMethodConfig($paymentMethodConfig);
        $paymentLinkMethod->getPaymentMethodConfig()->getOriginalAPIConfig()->setId(null);
        $paymentLinkMethod->getPaymentMethodConfig()->getOriginalAPIConfig()->setImage(Image::fromArray([]));
        $paymentLinkMethod->getPaymentMethodConfig()->setApiMethod(PaymentMethodConfig::API_METHOD_PAYMENT);
        $paymentLinkMethod->getPaymentMethodConfig()->setSurcharge(0);

        return $paymentLinkMethod;
    }

    /**
     * @return PaymentMethodSettings[]
     */
    protected function getEnabledPaymentMethodSettings()
    {
        $enabledPaymentMethodSettings = [];

        foreach ($this->getEnabledIntegrationSettings() as $channelSetting) {
            $enabledPaymentMethodSettings[] = $this->configService->doWithContext(
                (string)$channelSetting->getChannel()->getId(),
                function () use ($channelSetting) {
                    return $this->getPaymentMethodConfigurations($channelSetting);
                }
            );
        }

        return !empty($enabledPaymentMethodSettings) ? array_merge(...$enabledPaymentMethodSettings) : [];
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
     * @param ChannelSettings $channelSettings
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

            if ($paymentMethodSetting->getPaymentDescriptions()->isEmpty()) {
                $paymentMethodSetting->addPaymentDescription(
                    (new LocalizedFallbackValue())->setString(
                        $this->translator->trans('mollie.payment.config.payment_methods.payment.description.default.value')
                    )
                );
            }

            if ($paymentMethodSetting->getTransactionDescriptions()->isEmpty()) {
                $paymentMethodSetting->addTransactionDescription(
                    (new LocalizedFallbackValue())->setString(PaymentMethodSettingsType::DEFAULT_TRANSACTION_DESCRIPTION));
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
     * @return PaymentMethodConfig[]
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
