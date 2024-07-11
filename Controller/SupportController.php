<?php

namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationServices\DebugService;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SupportController
 *
 * @package Mollie\Bundle\PaymentBundle\Controller
 */
class SupportController extends AbstractController
{
    const DEBUG_DATA_FILE_NAME = 'mollie-debug-data.zip';


    /**
     * @var Configuration
     */
    private $configService;

    /**
     * SupportController constructor.
     *
     * @param Configuration $configService
     */
    public function __construct(Configuration $configService)
    {
        $this->configService = $configService;
    }

    /**
     * @Route(
     *     "/support/status",
     *     name="mollie_payment_support_status",
     *     methods={"GET"}
     * )
     *
     * #[Acl(
     *      id="oro_integration_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="Oro\Bundle\IntegrationBundle\Entity\Channel"
     * )]
     *
     * @return JsonResponse
     */
    public function getDebugStatus()
    {
        return new JsonResponse(['isDebugModeEnabled' => $this->configService->isDebugModeEnabled()]);
    }

    /**
     * @Route(
     *     "/support/status",
     *     name="mollie_payment_support_status_update",
     *     methods={"POST"}
     * )
     *
     * #[Acl(
     *      id="oro_integration_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="Oro\Bundle\IntegrationBundle\Entity\Channel"
     * )]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDebugStatus(Request $request)
    {
        $content = $request->getContent();
        $data = json_decode($content, true);
        if (!isset($data['debugStatus']) || !is_bool($data['debugStatus'])) {
            return new JsonResponse(['success' => false], 400);
        }

        $this->configService->setDebugModeEnabled($data['debugStatus']);

        return new JsonResponse(['isDebugModeEnabled' => $data['debugStatus']]);
    }

    /**
     * @Route(
     *     "/support/download_debug_data",
     *     name="mollie_payment_support_download_debug",
     *     methods={"GET"}
     * )
     *
     * #[Acl(
     *      id="oro_integration_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="Oro\Bundle\IntegrationBundle\Entity\Channel"
     * )]
     *
     * @return BinaryFileResponse
     */
    public function downloadDebugData()
    {
        /** @var DebugService $debugService */
        $debugService = $this->get(DebugService::class);
        $debugFile = $debugService->getDebugDataFilePath();

        return $this->file($debugFile, self::DEBUG_DATA_FILE_NAME);
    }
}
