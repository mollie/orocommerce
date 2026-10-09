<?php


namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\PaymentMethod;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ActivePaymentMethodsController
 *
 * @package Mollie\Bundle\PaymentBundle\Controller
 */
class ActivePaymentMethodsController extends AbstractController
{
    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * ActivePaymentMethodsController constructor.
     *
     * @param PaymentMethodService $paymentMethodService
     * @param Configuration $configService
     */
    public function __construct(PaymentMethodService $paymentMethodService, Configuration $configService)
    {
        $this->paymentMethodService = $paymentMethodService;
        $this->configService = $configService;
    }


    /**
     * @Route(
     *     "/methods/active/{channelId}/{profileId}",
     *     name="mollie_active_payment_methods",
     *     requirements={"channelId"="\d+", "profileId"="[A-Za-z0-9_]+"},
     *     methods={"GET"}
     * )
     * @ParamConverter("channel", class="Oro\Bundle\IntegrationBundle\Entity\Channel", options={"id" = "channelId"})
     *
     * @param string $channelId
     * @param string $profileId
     * @param Channel|null $channel Loaded only for the object-level VIEW check
     *
     * @return JsonResponse
     */
    #[AclAncestor('oro_integration_view')]
    public function getActivePaymentMethods($channelId, $profileId, ?Channel $channel = null)
    {
        try {
            $success = true;
            $activeMethods = $this->configService->doWithContext($channelId, function () use ($profileId) {
                return $this->paymentMethodService->getEnabledMethodsWithTempProfileId($profileId);
            });
        } catch (\Exception $exception) {
            $success = false;
            $activeMethods = [];
            Logger::logError("Failed to fetch enabled payment methods for profile: $profileId: {$exception->getMessage()}");
        }


        return new JsonResponse([
            'success' => $success,
            'activeMethods' => PaymentMethod::listPaymentMethodsAsString($activeMethods),
        ]);
    }
}
