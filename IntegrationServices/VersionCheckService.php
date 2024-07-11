<?php


namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\VersionCheckService as BaseService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class VersionCheckService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class VersionCheckService extends BaseService
{
    /**
     * @var TranslatorInterface
     */
    private $translationService;
    /**
     * @var RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translationService
     *
     * @return VersionCheckService
     */
    public static function create(RequestStack $requestStack, TranslatorInterface $translationService)
    {
        $instance = static::getInstance();
        $instance->requestStack = $requestStack;
        $instance->translationService = $translationService;

        return $instance;
    }

    /**
     * @inheritDoc
     */
    protected function flashMessage($latestVersion)
    {
        $messageKey = 'mollie.payment.config.payment_methods.versionCheck.message';
        $params = [
            '{versionNumber}' => $latestVersion,
            '{downloadUrl}' => $this->getConfigService()->getExtensionDownloadUrl($latestVersion),
        ];

        $message = $this->translationService->trans($messageKey, $params);

        $this->requestStack->getSession()?->getFlashBag()->add('warning', $message);
    }
}
