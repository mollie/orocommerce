<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentContextAwareConfigProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\Action\CaptureActionInterface;
use Oro\Bundle\PaymentBundle\Method\Action\PurchaseActionInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class MolliePayment
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
class MolliePayment implements PaymentMethodInterface, PurchaseActionInterface, CaptureActionInterface
{
    /**
     * @var MolliePaymentConfigInterface
     */
    protected $config;
    /**
     * @var MolliePaymentCreatorInterface
     */
    protected $paymentCreator;
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
     * @var Configuration
     */
    protected $configService;

    /**
     * MolliePayment constructor.
     *
     * @param MolliePaymentConfigInterface $config
     * @param MolliePaymentCreatorInterface $paymentCreator
     * @param MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider
     * @param RouterInterface $router
     * @param LocalizationHelper $localizationHelper
     * @param string $webhooksUrlReplacement
     */
    public function __construct(
        Configuration $configService,
        MolliePaymentConfigInterface $config,
        MolliePaymentCreatorInterface $paymentCreator,
        MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider,
        RouterInterface $router,
        LocalizationHelper $localizationHelper,
        $webhooksUrlReplacement = ''
    ) {
        $this->configService = $configService;
        $this->config = $config;
        $this->paymentCreator = $paymentCreator;
        $this->contextAwareConfigProvider = $contextAwareConfigProvider;
        $this->router = $router;
        $this->localizationHelper = $localizationHelper;
        $this->webhooksUrlReplacement = $webhooksUrlReplacement;
    }

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
     * @inheritdoc
     */
    public function purchase(PaymentTransaction $paymentTransaction): array
    {
        $purchaseRedirectUrl = $this->configService->doWithContext(
            (string)$this->config->getChannelId(),
            function () use ($paymentTransaction) {
                $paymentCreationResult = $this->paymentCreator->createMolliePayment($paymentTransaction);
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getSourceAction(): string
    {
        return self::AUTHORIZE;
    }

    /**
     * {@inheritdoc}
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
        if (empty($this->webhooksUrlReplacement) || !$this->configService->isDebugModeEnabled()) {
            return $url;
        }

        return preg_replace(
            '/http(s)?:\/\/[^\/]*\//i',
            rtrim($this->webhooksUrlReplacement, '/') . '/',
            $url
        );
    }
}
