<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentContextAwareConfigProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Symfony\Component\Routing\RouterInterface;

class MolliePaymentApiPayment extends MolliePayment
{
    /**
     * @var MollieDtoMapperInterface
     */
    private $configMapperDecorator;

    /**
     * MolliePaymentApiPayment constructor.
     *
     * @param MolliePaymentConfigInterface $config
     * @param MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider
     * @param RouterInterface $router
     * @param LocalizationHelper $localizationHelper
     * @param MollieDtoMapperInterface $configMapperDecorator
     * @param string $webhooksUrlReplacement
     */
    public function __construct(
        MolliePaymentConfigInterface $config,
        MolliePaymentContextAwareConfigProviderInterface $contextAwareConfigProvider,
        RouterInterface $router,
        LocalizationHelper $localizationHelper,
        MollieDtoMapperInterface $configMapperDecorator,
        $webhooksUrlReplacement = ''
    ) {
        parent::__construct($config, $contextAwareConfigProvider, $router, $localizationHelper, $webhooksUrlReplacement);
        $this->configMapperDecorator = $configMapperDecorator;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return MolliePaymentResultInterface|null
     *
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function createMolliePayment(PaymentTransaction $paymentTransaction)
    {
        /** @var PaymentService $paymentService */
        $paymentService = ServiceRegister::getService(PaymentService::CLASS_NAME);
        $payment = $paymentService->createPayment(
            $paymentTransaction->getEntityIdentifier(),
            $this->configMapperDecorator->getPaymentData($paymentTransaction)
        );

        return new MolliePaymentApiPaymentResult($payment);
    }
}