<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Carbon\Carbon;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\NotificationController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DatagridsListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class DatagridsListener
{

    /**
     * @var NotificationController
     */
    private $notificationController;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * DatagridsListener constructor.
     *
     * @param Configuration $configService
     * @param NotificationController $notificationController
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Configuration $configService,
        NotificationController $notificationController,
        TranslatorInterface $translator
    ) {
        $this->configService = $configService;
        $this->notificationController = $notificationController;
        $this->translator = $translator;
    }

    /**
     * @param BuildAfter $event
     *
     * @throws \Exception
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if (!$datasource instanceof ArrayDatasource) {
            throw new UnexpectedTypeException($datasource, ArrayDatasource::class);
        }

        $pageParams = $datagrid->getParameters()->get('_pager') ?: [];

        $channelId = $datagrid->getParameters()->get('channelId');
        $limit = array_key_exists('_per_page', $pageParams) ? $pageParams['_per_page'] : 25;
        $page = array_key_exists('_page', $pageParams) ? $pageParams['_page'] : 1;
        $offset = ($page - 1) * $limit;
        $result = $this->getPaginatedNotifications($limit, $offset, $channelId);

        $datasource->setArraySource($result);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param int $channelId
     *
     * @return array
     */
    public function getPaginatedNotifications($limit, $offset, $channelId)
    {
        $response = $this->configService->doWithContext($channelId, function () use ($limit, $offset) {
            return $this->notificationController->get($limit, (int)$offset);
        });

        $paginatedNotifications = [];

        foreach ($response->getNotifications() as $notification) {
            $paginatedNotifications[] = $this->createNotificationMap($notification);
        }

        $result = array_fill(0, $response->getTotalCount(), null);
        array_splice($result, $offset, count($paginatedNotifications), $paginatedNotifications);

        return $result;
    }



    /**
     * Returns notification map
     *
     * @param Notification $notification
     *
     * @return mixed
     */
    public function createNotificationMap(Notification $notification)
    {
        $date = Carbon::createFromTimestamp($notification->getTimestamp());
        $desc = $notification->getDescription();
        $message = $notification->getMessage();
        $notificationArray['id'] = $notification->getId();
        $notificationArray['website'] = $notification->getWebsiteName();
        $notificationArray['order'] = $notification->getOrderNumber();
        $notificationArray['severity'] = $notification->getSeverity();
        $notificationArray['message'] = $this->translator->trans($message->getMessageKey(), $this->adjustParameters($message->getMessageParams()));
        $notificationArray['description'] = $this->translator->trans($desc->getMessageKey(), $this->adjustParameters($desc->getMessageParams()));
        $notificationArray['date'] = $date->format('M d, Y, h:i A');

        return $notificationArray;
    }

    /**
     * @param array $messageParams
     * @return array
     */
    private function adjustParameters(array $messageParams)
    {
        $adjustedParameters = [];
        foreach ($messageParams as $key => $value) {
            $adjustedParameters['{' . $key . '}'] = $value;
        }

        return $adjustedParameters;
    }
}
