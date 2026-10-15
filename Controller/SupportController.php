<?php

namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationServices\DebugService;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
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
     * @var DebugService
     */
    private $debugService;

    /**
     * SupportController constructor.
     *
     * @param Configuration $configService
     * @param DebugService $debugService
     */
    public function __construct(Configuration $configService, DebugService $debugService)
    {
        $this->configService = $configService;
        $this->debugService = $debugService;
    }

    /**
     * @Route(
     *     "/support/status",
     *     name="mollie_payment_support_status",
     *     methods={"GET"}
     * )
     *
     * @return JsonResponse
     */
    #[AclAncestor('oro_integration_view')]
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
     * @param Request $request
     * @return JsonResponse
     */
    #[AclAncestor('oro_integration_update')]
    #[CsrfProtection()]
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
     * @return BinaryFileResponse
     */
    #[AclAncestor('oro_integration_update')]
    public function downloadDebugData()
    {
        $debugFile = $this->debugService->getDebugDataFilePath();

        return $this->file($debugFile, self::DEBUG_DATA_FILE_NAME)->deleteFileAfterSend(true);
    }
}
