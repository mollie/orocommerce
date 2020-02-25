<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentContextAwareConfigProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\Action\CaptureActionInterface;
use Oro\Bundle\PaymentBundle\Method\Action\PurchaseActionInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class MolliePayment implements PaymentMethodInterface, PurchaseActionInterface, CaptureActionInterface
{
    /**
     * @var MolliePaymentConfigInterface
     */
    protected $config;
    /**
     * @var MolliePaymentContextAwareConfigProviderInterface
     */
    protected $contextAwareConfigProvider;
    /**
     * @var RouterInterface
     */
    protected $router;
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;
    /**
     * @var string
     */
    protected $webhooksUrlReplacement;

    /**
     * @param MolliePaymentConfigInterface $config
     * @param MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider
     * @param RouterInterface $router
     * @param LocalizationHelper $localizationHelper
     * @param string $webhooksUrlReplacement
     */
    public function __construct(
        MolliePaymentConfigInterface $config,
        MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider,
        RouterInterface $router,
        LocalizationHelper $localizationHelper,
        $webhooksUrlReplacement = ''
    ) {
        $this->config = $config;
        $this->contextAwareConfigProvider = $contextAwareConfigProvider;
        $this->router = $router;
        $this->localizationHelper = $localizationHelper;
        $this->webhooksUrlReplacement = $webhooksUrlReplacement;
    }

    /**
     * Creates payment instance (payment or order) on mollie api
     *
     * @param PaymentTransaction $paymentTransaction
     *
     * @return MolliePaymentResultInterface|null
     */
    abstract protected function createMolliePayment(PaymentTransaction $paymentTransaction);

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!method_exists($this, $action)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '"%s" payment method "%s" action is not supported',
                    $this->getConfig()->getAdminLabel(),
                    $action
                )
            );
        }

        return $this->$action($paymentTransaction);
    }

    /**
     * @inheritDoc
     */
    public function purchase(PaymentTransaction $paymentTransaction): array
    {
        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);

        $purchaseRedirectUrl = $configuration->doWithContext(
            (string)$this->config->getChannelId(),
            function () use ($paymentTransaction) {
                $paymentCreationResult = $this->createMolliePayment($paymentTransaction);
                if (!$paymentCreationResult) {
                    return null;
                }

                $paymentTransaction->setReference($paymentCreationResult->getId());
                $redirectLink = $paymentCreationResult->getRedirectLink();
                return $redirectLink ? $redirectLink->getHref() : null;
            }
        );

        $paymentTransaction->setAction(self::PENDING);
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);

        return !empty($purchaseRedirectUrl) ? ['purchaseRedirectUrl' => $purchaseRedirectUrl] : [];
    }

    /**
     * @inheritDoc
     */
    public function capture(PaymentTransaction $paymentTransaction): array
    {
        $paymentTransaction->setAction($this->getSourceAction());

        return [
            'successful' => false,
            'message' => 'mollie.payment.capture.message',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return \in_array($actionName, [self::PURCHASE, self::CAPTURE], true);
    }

    /**
     * @inheritDoc
     */
    public function getSourceAction(): string
    {
        return self::AUTHORIZE;
    }

    /**
     * @inheritDoc
     */
    public function useSourcePaymentTransaction(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        if (!$context->getBillingAddress()) {
            return false;
        }

        $this->contextAwareConfigProvider->setPaymentContext($context);
        return $this->contextAwareConfigProvider->hasPaymentConfig($this->getIdentifier());
    }

    /**
     * @return MolliePaymentConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Replaces schema and host from the provided url with debug url set in configuration. Used for development to set
     * tunneling (ngrok) url for webhooks.
     *
     * @param string $url
     *
     * @return string
     */
    protected function ensureDebugWebhookUrl($url)
    {
        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        if (empty($this->webhooksUrlReplacement) || !$configuration->isDebugModeEnabled()) {
            return $url;
        }

        return preg_replace(
            '/http(s)?:\/\/[^\/]*\//i',
            rtrim($this->webhooksUrlReplacement, '/') . '/',
            $url
        );
    }
}