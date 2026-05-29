<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\Manager\PaymentLinkConfigProviderInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Class MolliePaymentLinkPaymentCreator
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
class MolliePaymentLinkPaymentCreator implements MolliePaymentCreatorInterface
{
    /**
     * @var MolliePaymentApiPaymentCreator
     */
    private $paymentCreator;
    /**
     * @var PaymentLinkConfigProviderInterface
     */
    private $paymentLinkConfigProvider;

    /**
     * MolliePaymentLinkPaymentCreator constructor.
     *
     * @param MolliePaymentApiPaymentCreator $paymentCreator
     * @param PaymentLinkConfigProviderInterface $paymentLinkConfigProvider
     */
    public function __construct(
        MolliePaymentApiPaymentCreator $paymentCreator,
        PaymentLinkConfigProviderInterface $paymentLinkConfigProvider
    ) {
        $this->paymentCreator = $paymentCreator;
        $this->paymentLinkConfigProvider = $paymentLinkConfigProvider;
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
    public function createMolliePayment(PaymentTransaction $paymentTransaction)
    {
        return $this->paymentCreator->createMolliePayment($paymentTransaction);
    }
}
