<?php

namespace Mollie\Bundle\PaymentBundle\Controller;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxMollieController
 *
 * @package Mollie\Bundle\PaymentBundle\Controller
 */
class AjaxMollieController extends AbstractController
{
    /**
     * @Route("/validate-connection/{channelId}/", name="mollie_payment_validate_connection", methods={"POST"})
     * @AclAncestor("oro_integration_update")
     * @ParamConverter("channel", class="OroIntegrationBundle:Channel", options={"id" = "channelId"})
     * @CsrfProtection()
     *
     * @param Request      $request
     * @param Channel|null $channel
     *
     * @return JsonResponse
     */
    public function validateConnectionAction(Request $request, Channel $channel = null)
    {
        if (!$channel) {
            $channel = new Channel();
        }

        $form = $this->createForm(
            ChannelType::class,
            $channel
        );
        $form->handleRequest($request);

        if (!$form->get('transport')->get('authToken')->isValid()) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->get('translator')->trans('mollie.payment.config.authorization.verification.fail.message'),
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => $this->get('translator')->trans('mollie.payment.config.authorization.verification.success.message'),
        ]);
    }
}
