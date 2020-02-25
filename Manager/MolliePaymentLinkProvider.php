<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Form\Entity\MolliePaymentLink;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class MolliePaymentLinkProvider
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class MolliePaymentLinkProvider
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var OroPaymentMethodUtility
     */
    private $paymentMethodUtility;

    /**
     * MolliePaymentLinkProvider constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param OroPaymentMethodUtility $paymentMethodUtility
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, OroPaymentMethodUtility $paymentMethodUtility)
    {
        $this->urlGenerator = $urlGenerator;
        $this->paymentMethodUtility = $paymentMethodUtility;
    }

    /**
     * Checks if refund button should be displayed
     *
     * @param Order $order
     *
     * @return bool
     */
    public function displayGeneratePaymentLinkButton($order)
    {
        if ($order) {
            $isMollieSelected = $this->paymentMethodUtility->hasMolliePaymentConfig($order);
            /** @var OrderReferenceService $orderReferenceService */
            $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
            $orderReference = $orderReferenceService->getByShopReference($order->getIdentifier());

            return $isMollieSelected && ($orderReference !== null);
        }

        return false;
    }

    /**
     * @param Order $order
     *
     * @return MolliePaymentLink
     */
    public function generatePaymentLink(Order $order)
    {
        $paymentLink = new MolliePaymentLink();
        $paymentLink->setPaymentLink($this->urlGenerator->generate('mollie_payment_link', ['orderId' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL));

        return $paymentLink;
    }
}
