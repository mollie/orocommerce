<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Factory;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\Manager\PaymentLinkConfigProviderInterface;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentContextAwareConfigProviderInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MollieOrdersApiPaymentCreator;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MolliePayment;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MolliePaymentApiPaymentCreator;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MolliePaymentCreatorInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MolliePaymentLinkPaymentCreator;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class MolliePaymentPaymentMethodFactory
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Factory
 */
class MolliePaymentPaymentMethodFactory implements MolliePaymentPaymentMethodFactoryInterface
{

    /**
     * @var MolliePaymentContextAwareConfigProviderInterface
     */
    private $contextAwareConfigProvider;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;
    /**
     * @var string
     */
    private $webhooksUrlReplacement;
    /**
     * @var MollieDtoMapperInterface
     */
    private $mollieDtoMapper;
    /**
     * @var PaymentLinkConfigProviderInterface
     */
    private $paymentLinkConfigProvider;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var PaymentService
     */
    private $paymentService;
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * MolliePaymentPaymentMethodFactory constructor.
     *
     * @param Configuration $configService
     * @param PaymentService $paymentService
     * @param OrderService $orderService
     * @param MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider
     * @param RouterInterface $router
     * @param LocalizationHelper $localizationHelper
     * @param MollieDtoMapperInterface $mollieDtoMapper
     * @param PaymentLinkConfigProviderInterface $paymentLinkConfigProvider
     * @param string $webhooksUrlReplacement
     */
    public function __construct(
        Configuration $configService,
        PaymentService $paymentService,
        OrderService $orderService,
        MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider,
        RouterInterface $router,
        LocalizationHelper $localizationHelper,
        MollieDtoMapperInterface $mollieDtoMapper,
        PaymentLinkConfigProviderInterface $paymentLinkConfigProvider,
        $webhooksUrlReplacement = ''
    ) {
        $this->configService = $configService;
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
        $this->contextAwareConfigProvider = $contextAwareConfigProvider;
        $this->router = $router;
        $this->localizationHelper = $localizationHelper;
        $this->mollieDtoMapper = $mollieDtoMapper;
        $this->paymentLinkConfigProvider = $paymentLinkConfigProvider;
        $this->webhooksUrlReplacement = $webhooksUrlReplacement;
    }

    /**
     * {@inheritdoc}
     */
    public function create(MolliePaymentConfigInterface $config)
    {
        return new MolliePayment(
            $this->configService,
            $config,
            $this->createPaymentCreator($config),
            $this->contextAwareConfigProvider,
            $this->router,
            $this->localizationHelper,
            $this->webhooksUrlReplacement
        );
    }

    /**
     * @param MolliePaymentConfigInterface $config
     * @return MolliePaymentCreatorInterface
     */
    private function createPaymentCreator(MolliePaymentConfigInterface $config): MolliePaymentCreatorInterface
    {
        if ($config->getPaymentMethodIdentifier() === MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
            $mapper = new MolliePaymentLinkMapperDecorator(
                $this->mollieDtoMapper,
                $config,
                $this->paymentLinkConfigProvider
            );
            return new MolliePaymentLinkPaymentCreator(
                new MolliePaymentApiPaymentCreator($mapper, $this->paymentService),
                new MollieOrdersApiPaymentCreator($mapper, $this->orderService),
                $this->paymentLinkConfigProvider
            );
        }

        $mapper = new MollieConfigMapperDecorator($this->mollieDtoMapper, $config);

        if ($config->getApiMethod() === PaymentMethodConfig::API_METHOD_ORDERS) {
            return new MollieOrdersApiPaymentCreator($mapper, $this->orderService);
        }

        return new MolliePaymentApiPaymentCreator($mapper, $this->paymentService);
    }
}
