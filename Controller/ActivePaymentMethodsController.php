<?php


namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\PaymentMethod;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

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
     *
     * @param string $profileId
     * @return JsonResponse
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/methods/active/{channelId}/{profileId}', name: 'mollie_active_payment_methods', methods: ['GET'])]
    public function getActivePaymentMethods($channelId, $profileId)
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
