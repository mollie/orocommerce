<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Factory;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentContextAwareConfigProviderInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MollieOrdersApiPayment;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MolliePaymentApiPayment;
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
     * @var \Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentContextAwareConfigProviderInterface
     */
    private $contextAwareConfigProvider;
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;
    /**
     * @var \Oro\Bundle\LocaleBundle\Helper\LocalizationHelper
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
     * MolliePaymentPaymentMethodFactory constructor.
     *
     * @param MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider
     * @param RouterInterface $router
     * @param LocalizationHelper $localizationHelper
     * @param MollieDtoMapperInterface $mollieDtoMapper
     * @param string $webhooksUrlReplacement
     */
    public function __construct(
        MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider,
        RouterInterface $router,
        LocalizationHelper $localizationHelper,
        MollieDtoMapperInterface $mollieDtoMapper,
        $webhooksUrlReplacement = ''
    ) {
        $this->contextAwareConfigProvider = $contextAwareConfigProvider;
        $this->router = $router;
        $this->localizationHelper = $localizationHelper;
        $this->mollieDtoMapper = $mollieDtoMapper;
        $this->webhooksUrlReplacement = $webhooksUrlReplacement;
    }

    /**
     * {@inheritdoc}
     */
    public function create(MolliePaymentConfigInterface $config)
    {
        $configMapperDecorator = new MollieConfigMapperDecorator($this->mollieDtoMapper, $config);
        if ($config->getApiMethod() === PaymentMethodConfig::API_METHOD_ORDERS) {
            return new MollieOrdersApiPayment(
                $config,
                $this->contextAwareConfigProvider,
                $this->router,
                $this->localizationHelper,
                $configMapperDecorator,
                $this->webhooksUrlReplacement
            );
        }

        return new MolliePaymentApiPayment(
            $config,
            $this->contextAwareConfigProvider,
            $this->router,
            $this->localizationHelper,
            $configMapperDecorator,
            $this->webhooksUrlReplacement
        );
    }
}