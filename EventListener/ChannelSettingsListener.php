<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Mollie\Bundle\PaymentBundle\Entity\ChannelSettings;
use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\PaymentMethodController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\WebsiteProfileController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationServices\FileUploader;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ChannelSettingsListener
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class ChannelSettingsListener
{
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var WebsiteProfileController
     */
    private $websiteProfileController;
    /**
     * @var PaymentMethodController
     */
    private $paymentMethodController;
    /**
     * @var FileUploader
     */
    private $fileUploader;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var string
     */
    private $publicImagePath;

    /**
     * ChannelSettingsListener constructor.
     *
     * @param Configuration $configService
     * @param WebsiteProfileController $websiteProfileController
     * @param PaymentMethodController $paymentMethodController
     * @param FileUploader $fileUploader
     * @param TranslatorInterface $translator
     * @param FlashBagInterface $flashBag
     * @param string $publicImagePath
     */
    public function __construct(
        Configuration $configService,
        WebsiteProfileController $websiteProfileController,
        PaymentMethodController $paymentMethodController,
        FileUploader $fileUploader,
        TranslatorInterface $translator,
        FlashBagInterface $flashBag,
        $publicImagePath
    ) {
        $this->configService = $configService;
        $this->websiteProfileController = $websiteProfileController;
        $this->paymentMethodController = $paymentMethodController;
        $this->fileUploader = $fileUploader;
        $this->translator = $translator;
        $this->flashBag = $flashBag;
        $this->publicImagePath = $publicImagePath;
    }

    /**
     * @param Channel $channel
     */
    public function onNewChannel(Channel $channel)
    {
        if (!$channel->getId()) {
            return;
        }

        if ($channel->getTransport() instanceof ChannelSettings) {
            $this->updateFor($channel);
        }
    }

    /**
     * @param ChannelSettings $entity
     */
    public function updateConfig(ChannelSettings $entity)
    {
        if (!$entity->getChannel()) {
            return;
        }

        $this->updateFor($entity->getChannel());
    }

    /**
     * Updates payment method configurations for a provided channel
     *
     * @param Channel $channel
     */
    protected function updateFor(Channel $channel)
    {
        /** @var ChannelSettings $channelSettings */
        $channelSettings = $channel->getTransport();

        $this->uploadImages($channel, $channelSettings);
        $this->configService->doWithContext((string)$channel->getId(), function () use ($channel, $channelSettings) {
            $this->configService->setAuthorizationToken($channelSettings->getAuthToken());
            $this->configService->setTestMode($channelSettings->isTestMode());

            $oldWebsiteProfile = $this->configService->getWebsiteProfile();
            $newWebsiteProfile = $channelSettings->getWebsiteProfile();
            if (
                $oldWebsiteProfile &&
                (!$newWebsiteProfile || $oldWebsiteProfile->getId() !== $newWebsiteProfile->getId())
            ) {
                $this->fileUploader->removeAllWithPrefix("{$channel->getId()}-{$oldWebsiteProfile->getId()}");
            }

            $this->websiteProfileController->save($newWebsiteProfile);
            $this->updatePaymentMethodConfigurations($channelSettings);
        });
    }

    /**
     * @param Channel $channel
     * @param ChannelSettings $channelSettings
     */
    protected function uploadImages(Channel $channel, $channelSettings)
    {
        $websiteProfile = $channelSettings->getWebsiteProfile();
        if (!$websiteProfile) {
            return;
        }

        $websiteProfileId = $channelSettings->getWebsiteProfile()->getId();
        foreach ($channelSettings->getPaymentMethodSettings() as $paymentMethodSetting) {
            if (!$paymentMethodSetting->getImage()) {
                continue;
            }

            $uploadedImageName = $this->uploadImage(
                $paymentMethodSetting->getImage(),
                "{$channel->getId()}-{$websiteProfileId}-{$paymentMethodSetting->getMollieMethodId()}"
            );
            if ($uploadedImageName) {
                $paymentMethodSetting->setImagePath("{$this->publicImagePath}/{$uploadedImageName}");
            }
        }
    }

    /**
     * Uploads file to a target directory and returns new file name. If operation fail, return vale will be null.
     *
     * @param UploadedFile $image
     * @param $fileNamePrefix
     * @return string|null
     */
    public function uploadImage(UploadedFile $image, $fileNamePrefix)
    {
        $uploadedImageName = $this->fileUploader->upload($image, $fileNamePrefix);

        if (!$uploadedImageName) {
            $this->flashBag->add(
                'warning',
                $this->translator->trans(
                    'mollie.payment.config.payment_methods.image.upload_error',
                    ['{image_name}' => $image->getClientOriginalName()]
                )
            );
        }

        return $uploadedImageName;
    }

    /**
     * @param ChannelSettings $channelSettings
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RepositoryNotRegisteredException
     */
    protected function updatePaymentMethodConfigurations(ChannelSettings $channelSettings)
    {
        $websiteProfile = $channelSettings->getWebsiteProfile();
        if (!$websiteProfile) {
            return;
        }

        /** @var PaymentMethodSettings[] $paymentMethodSettingsMap */
        $paymentMethodSettingsMap = [];
        foreach ($channelSettings->getPaymentMethodSettings() as $paymentMethodSetting) {
            $paymentMethodSettingsMap[$paymentMethodSetting->getMollieMethodId()] = $paymentMethodSetting;
        }

        $paymentMethodConfigs = $this->paymentMethodController->getAll($channelSettings->getWebsiteProfile()->getId());
        foreach ($paymentMethodConfigs as $paymentMethodConfig) {
            if (!array_key_exists($paymentMethodConfig->getMollieId(), $paymentMethodSettingsMap)) {
                continue;
            }

            $paymentMethodSetting = $paymentMethodSettingsMap[$paymentMethodConfig->getMollieId()];

            if (
                $paymentMethodConfig->hasCustomImage() &&
                $paymentMethodConfig->getImage() !== $paymentMethodSetting->getImagePath()
            ) {
                $this->fileUploader->remove(
                    str_replace("{$this->publicImagePath}/", '', $paymentMethodConfig->getImage())
                );
            }

            $paymentMethodConfig->setSurcharge($paymentMethodSetting->getSurcharge());
            $paymentMethodConfig->setApiMethod($paymentMethodSetting->getMethod());
            $paymentMethodConfig->setUseMollieComponents($paymentMethodSetting->getMollieComponents());
            $paymentMethodConfig->setIssuerListStyle($paymentMethodSetting->getIssuerListStyle());
            $paymentMethodConfig->setVoucherCategory($paymentMethodSetting->getVoucherCategory());
            $paymentMethodConfig->setProductAttribute($paymentMethodSetting->getProductAttribute());
            $paymentMethodConfig->setImage(
                !empty($paymentMethodSetting->getImagePath()) ? $paymentMethodSetting->getImagePath() : null
            );
        }

        $this->paymentMethodController->save($paymentMethodConfigs);
    }
}
