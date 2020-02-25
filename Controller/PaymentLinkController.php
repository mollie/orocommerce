<?php

namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\CheckoutLinkService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\Manager\OroPaymentMethodUtility;
use Oro\Bundle\OrderBundle\Entity\Order;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PaymentLinkController
 *
 * @package Mollie\Bundle\PaymentBundle\Controller
 */
class PaymentLinkController extends AbstractController
{
    /**
     * @Route("/paymentlink/generate/{orderId}", name="mollie_payment_link", methods={"GET"})
     * @ParamConverter("order", class="OroOrderBundle:Order", options={"id" = "orderId"})
     * @param Order $order
     *
     * @return JsonResponse|RedirectResponse
     */
    public function goToMolliePaymentPage(Order $order)
    {
        try {
            /** @var OroPaymentMethodUtility $paymentUtility */
            $paymentUtility = $this->get('mollie_payment.manager.oro_payment_method_utility');
            /** @var Configuration $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            return $configService->doWithContext($paymentUtility->getChannelId($order), function () use ($order) {
                /** @var CheckoutLinkService $checkoutLinkService */
                $checkoutLinkService = ServiceRegister::getService(CheckoutLinkService::CLASS_NAME);
                $link = $checkoutLinkService->getCheckoutLink($order->getIdentifier());

                return new RedirectResponse($link->getHref());
            });

        } catch (\Exception $exception) {
            $orderViewUrl = $this->generateUrl('oro_order_frontend_view', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new RedirectResponse($orderViewUrl);
        }
    }
}
