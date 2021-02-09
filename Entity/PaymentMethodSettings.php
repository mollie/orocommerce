<?php

namespace Mollie\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Class PaymentMethodSettings Entity with settings for Mollie payment methods
 * @package Mollie\Bundle\PaymentBundle\Entity
 *
 * @ORM\Table(name="mollie_payment_settings")
 * @ORM\Entity
 */
class PaymentMethodSettings
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \Mollie\Bundle\PaymentBundle\Entity\ChannelSettings
     *
     * @ORM\ManyToOne(targetEntity="Mollie\Bundle\PaymentBundle\Entity\ChannelSettings", inversedBy="paymentMethodSettings", cascade={"persist"})
     * @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $channelSettings;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="mollie_payment_settings_name",
     *      joinColumns={
     *          @ORM\JoinColumn(name="payment_setting_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $names;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="mollie_payment_settings_desc",
     *      joinColumns={
     *          @ORM\JoinColumn(name="payment_setting_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $descriptions;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="mollie_payment_settings_p_des",
     *      joinColumns={
     *          @ORM\JoinColumn(name="payment_setting_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $paymentDescriptions;

    /**
     * @var string
     *
     * @ORM\Column(name="mollie_method_id", type="string", length=255, nullable=false)
     */
    protected $mollieMethodId;

    /**
     * @var string
     *
     * @ORM\Column(name="mollie_method_description", type="string", length=255, nullable=false)
     */
    protected $mollieMethodDescription;

    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig
     */
    private $paymentMethodConfig;

    /**
     * @var bool
     */
    private $enabled = false;

    /**
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    private $image;

    /**
     * @var string
     */
    private $imagePath;

    /**
     * @var string
     */
    private $originalImagePath;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $surcharge;

    /**
     * @var boolean
     */
    private $mollieComponents;

    /**
     * @var string
     */
    private $issuerListStyle;

    /**
     * PaymentMethodSettings constructor.
     */
    public function __construct()
    {
        $this->names = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
        $this->paymentDescriptions = new ArrayCollection();
    }

    /**
     * @return \Mollie\Bundle\PaymentBundle\Entity\ChannelSettings
     */
    public function getChannelSettings()
    {
        return $this->channelSettings;
    }

    /**
     * @param \Mollie\Bundle\PaymentBundle\Entity\ChannelSettings $channelSettings
     */
    public function setChannelSettings($channelSettings)
    {
        $this->channelSettings = $channelSettings;
    }

    /**
     * @return string
     */
    public function getMollieMethodId()
    {
        return $this->mollieMethodId;
    }

    /**
     * @param string $mollieMethodId
     */
    public function setMollieMethodId($mollieMethodId)
    {
        $this->mollieMethodId = $mollieMethodId;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function addName(LocalizedFallbackValue $name)
    {
        if (!$this->names->contains($name)) {
            $this->names->add($name);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $name
     *
     * @return $this
     */
    public function removeName(LocalizedFallbackValue $name)
    {
        if ($this->names->contains($name)) {
            $this->names->removeElement($name);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getPaymentDescriptions()
    {
        return $this->paymentDescriptions;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $paymentDescriptions
     */
    public function setPaymentDescriptions($paymentDescriptions)
    {
        $this->paymentDescriptions = $paymentDescriptions;
    }

    public function addPaymentDescription(LocalizedFallbackValue $description)
    {
        if (!$this->paymentDescriptions->contains($description)) {
            $this->paymentDescriptions->add($description);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return $this
     */
    public function removePaymentDescription(LocalizedFallbackValue $description)
    {
        if ($this->paymentDescriptions->contains($description)) {
            $this->paymentDescriptions->removeElement($description);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return $this
     */
    public function addDescription(LocalizedFallbackValue $description)
    {
        if (!$this->descriptions->contains($description)) {
            $this->descriptions->add($description);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return $this
     */
    public function removeDescription(LocalizedFallbackValue $description)
    {
        if ($this->descriptions->contains($description)) {
            $this->descriptions->removeElement($description);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMollieMethodDescription()
    {
        return $this->mollieMethodDescription;
    }

    /**
     * @param string $mollieMethodDescription
     */
    public function setMollieMethodDescription($mollieMethodDescription)
    {
        $this->mollieMethodDescription = $mollieMethodDescription;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig
     */
    public function getPaymentMethodConfig()
    {
        return $this->paymentMethodConfig;
    }

    /**
     * @param \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig $paymentMethodConfig
     */
    public function setPaymentMethodConfig($paymentMethodConfig)
    {
        $this->paymentMethodConfig = $paymentMethodConfig;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * @param string $imagePath
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * @return string
     */
    public function getOriginalImagePath()
    {
        return $this->originalImagePath;
    }

    /**
     * @param string $originalImagePath
     */
    public function setOriginalImagePath($originalImagePath)
    {
        $this->originalImagePath = $originalImagePath;
    }

    /**
     * @return string
     */
    public function getSurcharge()
    {
        return $this->surcharge;
    }

    /**
     * @param string $surcharge
     */
    public function setSurcharge($surcharge)
    {
        $this->surcharge = $surcharge;
    }

    /**
     * @return mixed
     */
    public function getMollieComponents()
    {
        return $this->mollieComponents;
    }

    /**
     * @param bool $mollieComponents
     */
    public function setMollieComponents($mollieComponents)
    {
        $this->mollieComponents = $mollieComponents;
    }

    /**
     * @return string
     */
    public function getIssuerListStyle()
    {
        return $this->issuerListStyle;
    }

    /**
     * @param string $issuerListStyle
     */
    public function setIssuerListStyle($issuerListStyle)
    {
        $this->issuerListStyle = $issuerListStyle;
    }
}
