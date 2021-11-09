<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mollie\Bundle\PaymentBundle\Exceptions\MollieOperationForbiddenException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderLineChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\EventBus;
use Mollie\Bundle\PaymentBundle\Manager\OroPaymentMethodUtility;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class OrderLineEntityListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class OrderLineEntityListener
{
    public static $MOLLIE_MAPPED_ATTRIBUTES = ['freeFormProduct', 'quantity', 'priceType'];
    /**
     * @var MollieDtoMapperInterface
     */
    private $mollieDtoMapper;
    /**
     * @var OroPaymentMethodUtility
     */
    private $paymentMethodUtility;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var EventBus
     */
    private $eventBus;
    /**
     * @var OrderReferenceService
     */
    private $orderReferenceService;

    /**
     * @var bool
     */
    /**
     * @var FlashBagInterface
     */
    private $flashBag;
    /**
     * @var bool
     */
    private static $handleLineEvent = true;

    /**
     * OrderLineEntityListener constructor.
     *
     * @param Configuration $configService
     * @param EventBus $eventBus
     * @param OrderReferenceService $orderReferenceService
     * @param OroPaymentMethodUtility $paymentMethodUtility
     * @param MollieDtoMapperInterface $mollieDtoMapper
     * @param TranslatorInterface $translator
     * @param FlashBagInterface $flashBag
     */
    public function __construct(
        Configuration $configService,
        EventBus $eventBus,
        OrderReferenceService $orderReferenceService,
        OroPaymentMethodUtility $paymentMethodUtility,
        MollieDtoMapperInterface $mollieDtoMapper,
        TranslatorInterface $translator,
        FlashBagInterface $flashBag
    ) {
        $this->configService = $configService;
        $this->eventBus = $eventBus;
        $this->orderReferenceService = $orderReferenceService;
        $this->mollieDtoMapper = $mollieDtoMapper;
        $this->paymentMethodUtility = $paymentMethodUtility;
        $this->translator = $translator;
        $this->flashBag = $flashBag;
    }

    /**
     * @return bool
     */
    public static function isHandleLineEvent(): bool
    {
        return self::$handleLineEvent;
    }

    /**
     * @param bool $handleLineEvent
     */
    public static function setHandleLineEvent(bool $handleLineEvent)
    {
        self::$handleLineEvent = $handleLineEvent;
    }

    /**
     * @param OrderLineItem $orderLineItem
     */
    public function onPreUpdate(OrderLineItem $orderLineItem, PreUpdateEventArgs $args)
    {
        if (!static::isHandleLineEvent() || !$this->isOrderLineChanged($args)) {
            return;
        }

        try {
            $channelId = $this->paymentMethodUtility->getChannelId($orderLineItem->getOrder());
            $this->configService->doWithContext((string)$channelId, function () use ($orderLineItem) {
                $orderReference = $this->orderReferenceService->getByShopReference(
                    $orderLineItem->getOrder()->getIdentifier()
                );

                // If order reference does not exist for the changed order line merchant changed order
                // identifier manually in the DB (or via shop API), and we should ignore this order from
                // further sync.
                if (!$orderReference) {
                    return;
                }

                $lineForUpdate = $this->mollieDtoMapper->getOrderLine($orderLineItem);
                $lineForUpdate->setId($this->getLineIdFromMollie($orderLineItem));

                $this->eventBus->fire(new IntegrationOrderLineChangedEvent(
                    $orderLineItem->getOrder()->getIdentifier(),
                    $lineForUpdate
                ));
            });
        } catch (\Exception $exception) {
            $message = $this->translator->trans(
                'mollie.payment.integration.event.notification.order_line_changed_error.description',
                ['{api_message}' => $exception->getMessage()]
            );

            $this->flashBag->add('error', $message);
            throw new ForbiddenOperationException($message, 0, $exception);
        }
    }

    /**
     * @param OrderLineItem $oroLineItem
     *
     * @return string|null
     */
    private function getLineIdFromMollie(OrderLineItem $oroLineItem)
    {
        if ($orderReference = $this->orderReferenceService->getByShopReference($oroLineItem->getOrder()->getIdentifier())) {
            $storedOrder = Order::fromArray($orderReference->getPayload());
            foreach ($storedOrder->getLines() as $line) {
                $metadata = $line->getMetadata();
                if (!empty($metadata['order_line_id']) && ($metadata['order_line_id'] === $oroLineItem->getEntityIdentifier())) {
                    return $line->getId();
                }
            }
        }

        return null;
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @return bool
     */
    private function isOrderLineChanged(PreUpdateEventArgs $args)
    {
        $changeSetKeys = array_keys($args->getEntityChangeSet());
        if (!$this->isChangeForMollie($changeSetKeys)) {
            return false;
        }

        foreach ($changeSetKeys as $key) {
            if ($args->getOldValue($key) != $args->getNewValue($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if changed attribute is of interest for mollie
     *
     * @param array $changeSetKeys
     *
     * @return bool
     */
    private function isChangeForMollie($changeSetKeys)
    {
        $intersect = array_intersect($changeSetKeys, static::$MOLLIE_MAPPED_ATTRIBUTES);

        return !empty($intersect);
    }
}
