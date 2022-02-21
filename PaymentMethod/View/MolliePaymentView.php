<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

/**
 * Class MolliePaymentView
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\View
 */
class MolliePaymentView implements PaymentMethodViewInterface
{
    /**
     * @var MolliePaymentConfigInterface
     */
    protected $config;
    /**
     * @var ApplicablePaymentMethodsProvider
     */
    protected $paymentMethodProvider;

    /**
     * MolliePaymentView constructor.
     *
     * @param MolliePaymentConfigInterface $config
     * @param ApplicablePaymentMethodsProvider $provider
     */
    public function __construct(MolliePaymentConfigInterface $config, ApplicablePaymentMethodsProvider $provider)
    {
        $this->config = $config;
        $this->paymentMethodProvider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(PaymentContextInterface $context)
    {
        return [
            'isApplePay' => false !== strpos($this->config->getPaymentMethodIdentifier(), 'applepay'),
            'icon' => $this->config->getIcon(),
            'surchargeAmount' => $this->config->getSurchargeAmount(),
            'currency' => $context->getCurrency(),
            'useMollieComponents' => $this->config->useMollieComponents() && !$this->isMultipleCreditCard($context),
            'useSingleClickPayment' => $this->config->useMollieComponents() && !$this->isMultipleCreditCard($context) &&
                $this->config->useSingleClickPayment(),
            'singleClickPaymentApprovalText' => $this->config->getSingleClickPaymentApprovalText(),
            'singleClickPaymentDescription' => $this->config->getSingleClickPaymentDescription(),
            'issuerListStyle' => $this->config->getIssuerListStyle(),
            'issuers' => $this->config->getIssuers(),
            'paymentMethod' => $this->config->getPaymentMethodIdentifier(),
            'isTestMode' => $this->config->isTestModeEnabled(),
            'profileId' => $this->config->getProfileId(),
            'lang' => '',
            'paymentDescription' => $this->config->getPaymentDescription(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlock()
    {
        return '_payment_methods_mollie_payment_widget';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * @param PaymentContextInterface $context
     *
     * @return bool
     */
    private function isMultipleCreditCard(PaymentContextInterface $context)
    {
        $applicablePaymentMethods = $this->paymentMethodProvider->getApplicablePaymentMethods($context);
        $numberOfMollieCreditCards = 0;
        foreach ($applicablePaymentMethods as $key => $config) {
            if (preg_match('/^mollie.*creditcard$/', $key)) {
                $numberOfMollieCreditCards++;
            }
        }

        return $numberOfMollieCreditCards > 1;
    }
}
