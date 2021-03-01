<?php


namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\VersionCheckService as BaseService;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class VersionCheckService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class VersionCheckService extends BaseService
{

    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var TranslatorInterface
     */
    private $translationService;

    /**
     * @param FlashBagInterface $flashBag
     * @param TranslatorInterface $translationService
     *
     * @return VersionCheckService
     */
    public static function create(FlashBagInterface $flashBag, TranslatorInterface $translationService)
    {
        $instance = static::getInstance();
        $instance->flashBag = $flashBag;
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
            '{downloadUrl}' => $this->getConfigService()->getExtensionDownloadUrl(),
        ];

        $message = $this->translationService->trans($messageKey, $params);

        $this->flashBag->add('info', $message);
    }
}