<?php

namespace Mollie\Bundle\PaymentBundle\Form\EventListener;

use Mollie\Bundle\PaymentBundle\Entity\ChannelSettings;
use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\Form\Type\PaymentMethodSettingsType;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\OrgToken\OrgToken;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\PaymentMethodController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\WebsiteProfileController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\VersionCheckService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ChannelSettingsTypeSubscriber
 *
 * @package Mollie\Bundle\PaymentBundle\Form\EventListener
 */
class ChannelSettingsTypeSubscriber implements EventSubscriberInterface
{
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AuthorizationService
     */
    private $authorizationService;
    /**
     * @var WebsiteProfileController
     */
    private $websiteProfileController;
    /**
     * @var PaymentMethodController
     */
    private $paymentMethodController;
    /**
     * @var array
     */
    private $tokenValidationCache = [];

    /**
     * @var VersionCheckService
     */
    private $versionCheckService;

    /**
     * ChannelSettingsTypeSubscriber constructor.
     *
     * @param Configuration $configService
     * @param TranslatorInterface $translator
     * @param AuthorizationService $authorizationService
     * @param WebsiteProfileController $websiteProfileController
     * @param PaymentMethodController $paymentMethodController
     */
    public function __construct(
        Configuration $configService,
        TranslatorInterface $translator,
        AuthorizationService $authorizationService,
        WebsiteProfileController $websiteProfileController,
        PaymentMethodController $paymentMethodController,
        VersionCheckService $versionCheckService
    ) {
        $this->configService = $configService;
        $this->translator = $translator;
        $this->authorizationService = $authorizationService;
        $this->websiteProfileController = $websiteProfileController;
        $this->paymentMethodController = $paymentMethodController;
        $this->versionCheckService = $versionCheckService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA  => [['onPreSetData', 10], ['setWebsiteProfile']],
            FormEvents::POST_SET_DATA  => 'onPostSetData',
            FormEvents::PRE_SUBMIT   => 'onPreSubmit',
            FormEvents::POST_SUBMIT   => 'onPostSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var ChannelSettings|null $channelSettings */
        $channelSettings = $event->getData();
        if (!$channelSettings) {
            return;
        }

        $channelSettings->setMollieSaveMarker(uniqid('mollie', true));

        if (!$channelSettings->getId()) {
            return;
        }

        $this->configService->doWithContext(
            (string)$channelSettings->getChannel()->getId(),
            function () use ($channelSettings) {
                $channelSettings->setAuthToken($this->configService->getAuthorizationToken());
                $channelSettings->setTestMode($this->configService->isTestMode());
            }
        );

        $form = $event->getForm();
        if (!$channelSettings->getAuthToken() || $form->isSubmitted()) {
            return;
        }

        if (!$this->isTokenValid($channelSettings->getAuthToken(), $channelSettings->isTestMode())) {
            $form->get('authToken')->addError(
                new FormError($this->translator->trans('mollie.payment.config.authorization.verification.fail.message'))
            );
        }
    }

    /**
     * @param FormEvent $event
     */
    public function setWebsiteProfile(FormEvent $event)
    {
        /** @var ChannelSettings|null $channelSettings */
        $channelSettings = $event->getData();
        if (!$channelSettings || !$channelSettings->getId()) {
            return;
        }

        $form = $event->getForm();
        if ($form->get('authToken')->getErrors()->count() > 0) {
            return;
        }

        // Set website profile if not already set. Oro sets form data multiple times during submit, presetting data once is enough.
        if (!$channelSettings->getWebsiteProfile()) {
            $this->configService->doWithContext(
                (string)$channelSettings->getChannel()->getId(),
                function () use ($channelSettings) {
                    $savedProfile = $this->websiteProfileController->getCurrent();
                    if (!$savedProfile || $this->isWebsiteProfileDeleted($savedProfile)) {
                        $this->configService->setWebsiteProfile(null);
                        $this->configService->setAuthorizationToken('');
                        $channelSettings->setAuthToken('');

                        return;
                    }

                    $channelSettings->setWebsiteProfile($savedProfile);
                    $this->setPaymentMethodConfigurations($channelSettings);
                }
            );
        }

        if (empty($channelSettings->getAuthToken())) {
            $form->get('authToken')->addError(
                new FormError($this->translator->trans(
                    'mollie.payment.config.authorization.verification.deleted_website.message'
                ))
            );
            $form->get('isTokenOnlySubmit')->setData('1');

            return;
        }

        $this->addWebsiteProfileAndPaymentSettingsToForm($form, $channelSettings);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        $this->versionCheckService->checkForNewVersion();
        $form = $event->getForm();

        $form->get('mollieVersion')->setData($this->configService->getExtensionVersion());

        if ($form->get('authToken')->getErrors()->count() > 0) {
            $form->get('isTokenOnlySubmit')->setData('1');
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var ChannelSettings|null $channelSettings */
        $channelSettings = $form->getData();
        $data = $event->getData();
        if (!$channelSettings || !$channelSettings->getId()) {
            return;
        }

        if (!$this->isTokenValid($data['authToken'], (bool)$data['testMode'])) {
            $form->get('authToken')->addError(
                new FormError($this->translator->trans('mollie.payment.config.authorization.verification.fail.message'))
            );
            $form->remove('websiteProfile');
            $form->remove('websiteProfileChangeMarker');
            $form->remove('paymentMethodSettings');
            unset($data['websiteProfile'], $data['websiteProfileChangeMarker'], $data['paymentMethodSettings']);

            $form->get('isTokenOnlySubmit')->setData('1');
            $data['isTokenOnlySubmit'] = '1';
            $event->setData($data);

            return;
        }

        $configToken = $this->configService->doWithContext(
            (string)$channelSettings->getChannel()->getId(),
            function () {
                return $this->configService->getAuthorizationToken();
            }
        );

        if (
            (!empty($configToken) && $data['authToken'] !== $configToken) ||
            $data['isTokenOnlySubmit']
        ) {
            $form->remove('websiteProfile');
            $form->remove('websiteProfileChangeMarker');
            $form->remove('paymentMethodSettings');
            unset($data['websiteProfile'], $data['websiteProfileChangeMarker'], $data['paymentMethodSettings']);

            $form->get('formRefreshRequired')->setData('1');
            $data['formRefreshRequired'] = '1';
            $event->setData($data);

            $channelSettings->setAuthToken($data['authToken']);
            $channelSettings->setTestMode((bool)$data['testMode']);
            $this->configService->doWithContext(
                (string)$channelSettings->getChannel()->getId(),
                function () use ($channelSettings) {
                    $this->configService->setAuthorizationToken($channelSettings->getAuthToken());
                    $this->configService->setTestMode($channelSettings->isTestMode());
                }
            );

            return;
        }

        $channelSettings->setAuthToken($data['authToken']);
        $channelSettings->setTestMode((bool)$data['testMode']);

        $this->configService->doWithContext(
            (string)$channelSettings->getChannel()->getId(),
            function () use ($channelSettings, $data) {
                $oldToken = $this->configService->getAuthorizationToken();
                $oldTestMode = $this->configService->isTestMode();

                $this->configService->setAuthorizationToken($channelSettings->getAuthToken());
                $this->configService->setTestMode($channelSettings->isTestMode());


                $oldWebsiteProfile = $this->configService->getWebsiteProfile();
                $newWebsiteProfile = null;
                if (!empty($data['websiteProfile'])) {
                    foreach ($this->websiteProfileController->getAll() as $websiteProfile) {
                        if ($websiteProfile->getId() === $data['websiteProfile']) {
                            $newWebsiteProfile = $websiteProfile;
                            break;
                        }
                    }
                }

                if (!$newWebsiteProfile && !$oldWebsiteProfile) {
                    $newWebsiteProfile = $this->websiteProfileController->getCurrent();
                    $data['websiteProfile'] = $newWebsiteProfile->getId();
                }

                // If new profile does not exist or if it matches already saved do not reset payment methods
                if (
                    !$newWebsiteProfile ||
                    ($oldWebsiteProfile && $newWebsiteProfile->getId() === $oldWebsiteProfile->getId())
                ) {
                    $this->configService->setAuthorizationToken($oldToken);
                    $this->configService->setTestMode($oldTestMode);

                    return;
                }

                $channelSettings->setWebsiteProfile($newWebsiteProfile);
                if (array_key_exists('websiteProfileChangeMarker', $data) && $data['websiteProfileChangeMarker']) {
                    // Just remove from memory for current form view rendering
                    $channelSettings->resetPaymentMethodSettings();
                } else {
                    // Remove from DB
                    $channelSettings->getPaymentMethodSettings()->clear();
                }

                $this->setPaymentMethodConfigurations($channelSettings, true);

                $this->configService->setAuthorizationToken($oldToken);
                $this->configService->setTestMode($oldTestMode);
            }
        );

        $this->addWebsiteProfileAndPaymentSettingsToForm($form, $channelSettings);

        if (array_key_exists('websiteProfileChangeMarker', $data) && $data['websiteProfileChangeMarker']) {
            unset($data['paymentMethodSettings']);
        }

        $form->get('websiteProfile')->setData($channelSettings->getWebsiteProfile());
        $form->get('paymentMethodSettings')->setData($channelSettings->getPaymentMethodSettings());

        $event->setData($data);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var ChannelSettings|null $channelSettings */
        $channelSettings = $event->getData();
        $form = $event->getForm();
        if (!$channelSettings || !$form->isSubmitted() || !$form->isValid()) {
            return;
        }

        if (!$this->isTokenValid($channelSettings->getAuthToken(), $channelSettings->isTestMode())) {
            $form->get('authToken')->addError(
                new FormError($this->translator->trans('mollie.payment.config.authorization.verification.fail.message'))
            );
        }
    }

    /**
     * @param ChannelSettings $channelSettings
     * @param bool $forceCreate If true current payment method settings configuration will be disregarded
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function setPaymentMethodConfigurations(ChannelSettings $channelSettings, $forceCreate = false)
    {
        $paymentMethodSettingsMap = [];
        if (!$forceCreate) {
            foreach ($channelSettings->getPaymentMethodSettings() as $paymentMethodSetting) {
                $paymentMethodSettingsMap[$paymentMethodSetting->getMollieMethodId()] = $paymentMethodSetting;
            }
        }

        $paymentMethodConfigs = $this->paymentMethodController->getAll($channelSettings->getWebsiteProfile()->getId());
        foreach ($paymentMethodConfigs as $paymentMethodConfig) {
            $paymentMethodSetting = null;
	        $paymentMethodConfigMollieIds[] = $paymentMethodConfig->getMollieId();
	        if (array_key_exists($paymentMethodConfig->getMollieId(), $paymentMethodSettingsMap)) {
                $paymentMethodSetting = $paymentMethodSettingsMap[$paymentMethodConfig->getMollieId()];
            }

            if (!$paymentMethodSetting) {
                $paymentMethodSetting = new PaymentMethodSettings();
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
                    (new LocalizedFallbackValue())->setString($this->translator->trans('mollie.payment.config.payment_methods.payment.description.default.value'))
                );
            }

            if ($paymentMethodSetting->getTransactionDescriptions()->isEmpty()) {
                $paymentMethodSetting->addTransactionDescription(
                    (new LocalizedFallbackValue())->setString(PaymentMethodSettingsType::DEFAULT_TRANSACTION_DESCRIPTION)
                );
            }

            $paymentMethodSetting->setPaymentMethodConfig($paymentMethodConfig);
            $paymentMethodSetting->setEnabled($paymentMethodConfig->isEnabled());
            $paymentMethodSetting->setSurcharge($paymentMethodConfig->getSurcharge());
            $paymentMethodSetting->setMethod($paymentMethodConfig->getApiMethod());
            $paymentMethodSetting->setMollieComponents($paymentMethodConfig->useMollieComponents());
            $paymentMethodSetting->setIssuerListStyle($paymentMethodConfig->getIssuerListStyle());
            $paymentMethodSetting->setOrderExpiryDays($paymentMethodConfig->getDaysToOrderExpire());
            $paymentMethodSetting->setPaymentExpiryDays($paymentMethodConfig->getDaysToPaymentExpire());
            $paymentMethodSetting->setVoucherCategory($paymentMethodConfig->getVoucherCategory());
            $paymentMethodSetting->setProductAttribute($paymentMethodConfig->getProductAttribute());
            $paymentMethodSetting->setOriginalImagePath($paymentMethodConfig->getOriginalAPIConfig()->getImage()->getSize2x());
            $paymentMethodSetting->setImagePath(
                $paymentMethodConfig->hasCustomImage() ? $paymentMethodConfig->getImage() : null
            );
        }

	    // Remove any saved method setting that is now missing in the Mollie API
	    foreach ($paymentMethodSettingsMap as $mollieMethodId => $paymentMethodSetting) {
		    if (!in_array($mollieMethodId, $paymentMethodConfigMollieIds)) {
			    $channelSettings->removePaymentMethodSetting($paymentMethodSetting);
		    }
	    }
    }

    /**
     * @param string $token
     * @param bool $testMode
     * @return bool
     */
    protected function isTokenValid($token, $testMode)
    {
        if (!array_key_exists($token, $this->tokenValidationCache)) {
            $result = $this->authorizationService->validateToken(new OrgToken($token, $testMode));
            $this->tokenValidationCache[$token] = $result;
        }

        return (bool)$this->tokenValidationCache[$token];
    }

    /**
     * @param WebsiteProfile $websiteProfileToCheck
     * @return bool
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function isWebsiteProfileDeleted(WebsiteProfile $websiteProfileToCheck)
    {
        foreach ($this->websiteProfileController->getAll() as $websiteProfile) {
            if ((string)$websiteProfileToCheck->getId() === (string)$websiteProfile->getId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param FormInterface $form
     * @param ChannelSettings $channelSettings
     */
    private function addWebsiteProfileAndPaymentSettingsToForm(FormInterface $form, ChannelSettings $channelSettings)
    {
        if (!$form->has('websiteProfile')) {
            $form->add('websiteProfile', ChoiceType::class, [
                'choices' => $this->configService->doWithContext(
                    (string)$channelSettings->getChannel()->getId(),
                    function () use ($channelSettings) {
                        $oldToken = $this->configService->getAuthorizationToken();
                        $oldTestMode = $this->configService->isTestMode();

                        $this->configService->setAuthorizationToken($channelSettings->getAuthToken());
                        $this->configService->setTestMode($channelSettings->isTestMode());

                        try {
                            $choices = $this->websiteProfileController->getAll();
                        } catch (\Exception $e) {
                            $choices = [];
                        }

                        $this->configService->setAuthorizationToken($oldToken);
                        $this->configService->setTestMode($oldTestMode);

                        return $choices;
                    }
                ),
                'choice_label' => static function (WebsiteProfile $profile) {
                    return $profile->getName();
                },
                'choice_value' => static function (WebsiteProfile $profile = null) {
                    return $profile ? $profile->getId() : '';
                },
                'label' => 'mollie.payment.config.website_profile.id.label',
                'required' => true,
                'placeholder' => false,
            ]);
        }

        if (!$form->has('websiteProfileChangeMarker')) {
            $form->add('websiteProfileChangeMarker', HiddenType::class, [
                'mapped' => false
            ]);
        }

        if (!$form->has('paymentMethodSettings')) {
            $form->add('paymentMethodSettings', CollectionType::class, [
                'entry_type' => PaymentMethodSettingsType::class,
                'entry_options' => ['label' => false],
                'by_reference' => false,
                'allow_add' => true,
            ]);
        }
    }
}
