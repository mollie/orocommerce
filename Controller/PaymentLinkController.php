<?php

namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\Manager\AdminLinkVisitHandler;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PaymentLinkController
 *
 * @package Mollie\Bundle\PaymentBundle\Controller
 */
class PaymentLinkController extends AbstractController
{
    /**
     * @var AdminLinkVisitHandler
     */
    private $adminLinkHandler;

    /**
     * PaymentLinkController constructor.
     *
     * @param AdminLinkVisitHandler $adminLinkHandler
     */
    public function __construct(AdminLinkVisitHandler $adminLinkHandler)
    {
        $this->adminLinkHandler = $adminLinkHandler;
    }

    /**
     * @Route("/paymentlink/generate/{orderId}", name="mollie_payment_link", methods={"GET"})
     * @ParamConverter("order", class="OroOrderBundle:Order", options={"id" = "orderId"})
     * @param Order $order
     * #[AclAncestor("oro_order_frontend_view")]
     *
     * @return JsonResponse|RedirectResponse
     * @throws \Throwable
     */
    public function goToMolliePaymentPage(Order $order)
    {
        try {
            $link = $this->adminLinkHandler->handleAndGetCheckoutUrl($order);
        } catch (\Exception $exception) {
            Logger::logError(
                'Admin payment link failed',
                'Integration',
                [
                    'ExceptionMessage' => $exception->getMessage(),
                    'ExceptionTrace' => $exception->getTraceAsString(),
                ]
            );

            $link = $this->adminLinkHandler->generateOrderViewLink($order);
        }

        return new RedirectResponse($link);
    }
}
