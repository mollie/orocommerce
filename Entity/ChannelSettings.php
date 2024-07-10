<?php

namespace Mollie\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Entity with settings for Mollie integration
 */
#[ORM\Entity(repositoryClass: \Mollie\Bundle\PaymentBundle\Entity\Repository\ChannelSettingsRepository::class)]
class ChannelSettings extends Transport
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'mollie_save_marker', type: 'string', length: 255, nullable: false)]
    protected $mollieSaveMarker;

    /**
     * @var string Mollie API token
     */
    private $authToken;
    /**
     * @var bool
     */
    private $testMode = false;
    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile
     */
    private $websiteProfile;
    /**
     * @var \Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings[]|\Doctrine\Common\Collections\ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: \Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings::class, mappedBy: 'channelSettings', cascade: ['ALL'], orphanRemoval: true)]
    protected $paymentMethodSettings;
    /**
     * @var ParameterBag
     */
    private $settings;

    /**
     * ChannelSettings constructor.
     */
    public function __construct()
    {
        $this->paymentMethodSettings = new ArrayCollection();
    }

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'mollie_save_marker' => $this->getMollieSaveMarker(),
                    'auth_token' => $this->getAuthToken(),
                    'test_mode' => $this->isTestMode(),
                    'website_profile' => $this->getWebsiteProfile(),
                ]
            );
        }

        return $this->settings;
    }

    /**
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * @param string $authToken
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }

    /**
     * @param bool $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
    }

    /**
     * @return string
     */
    public function getMollieSaveMarker()
    {
        return $this->mollieSaveMarker;
    }

    /**
     * @param string $mollieSaveMarker
     */
    public function setMollieSaveMarker($mollieSaveMarker)
    {
        $this->mollieSaveMarker = $mollieSaveMarker;
    }

    /**
     * @return \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile
     */
    public function getWebsiteProfile()
    {
        return $this->websiteProfile;
    }

    /**
     * @param \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile
     */
    public function setWebsiteProfile($websiteProfile)
    {
        $this->websiteProfile = $websiteProfile;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings[]
     */
    public function getPaymentMethodSettings()
    {
        return $this->paymentMethodSettings;
    }

    /**
     * Resets payment method setting collection by replacing whole collection with empty collection (this will avoid doctrine
     * persistence)
     */
    public function resetPaymentMethodSettings()
    {
        $this->paymentMethodSettings = new ArrayCollection();
    }

    /**
     * @param \Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings $paymentMethodSettings
     *
     * @return $this
     */
    public function addPaymentMethodSetting(PaymentMethodSettings $paymentMethodSettings)
    {
        if (!$this->getPaymentMethodSettings()->contains($paymentMethodSettings)) {
            $this->getPaymentMethodSettings()->add($paymentMethodSettings);
            $paymentMethodSettings->setChannelSettings($this);
        }

        return $this;
    }

    /**
     * @param \Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings $paymentMethodSettings
     *
     * @return bool
     */
    public function removePaymentMethodSetting(PaymentMethodSettings $paymentMethodSettings)
    {
        $paymentMethodSettings->setChannelSettings(null);
        return $this->getPaymentMethodSettings()->removeElement($paymentMethodSettings);
    }
}
