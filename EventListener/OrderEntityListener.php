<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mollie\Bundle\PaymentBundle\Exceptions\MollieOperationForbiddenException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Tracking;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderBillingAddressChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderCanceledEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderClosedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderDeletedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderShippedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderShippingAddressChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderTotalChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\EventBus;
use Mollie\Bundle\PaymentBundle\Manager\OroPaymentMethodUtility;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class OrderEntityListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class OrderEntityListener
{
    /**
     * @var OroPaymentMethodUtility
     */
    private $paymentMethodUtility;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var bool
     */
    private $orderChangeIsProcessing = false;
    /**
     * @var EventBus
     */
    private $eventBus;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var MollieDtoMapperInterface
     */
    private $mollieDtoMapper;
    /**
     * @var OrderAddress|null
     */
    protected static $addressChanged;

    /**
     * OrderEntityListener constructor.
     *
     * @param Configuration $configService
     * @param EventBus $eventBus
     * @param OroPaymentMethodUtility $paymentMethodUtility
     * @param TranslatorInterface $translator
     * @param MollieDtoMapperInterface $mollieDtoMapper
     */
    public function __construct(
        Configuration $configService,
        EventBus $eventBus,
        OroPaymentMethodUtility $paymentMethodUtility,
        TranslatorInterface $translator,
        MollieDtoMapperInterface $mollieDtoMapper
    ) {
        $this->configService = $configService;
        $this->eventBus = $eventBus;
        $this->paymentMethodUtility = $paymentMethodUtility;
        $this->translator = $translator;
        $this->mollieDtoMapper = $mollieDtoMapper;
    }

    /**
     * @param Order $order
     * @param PreUpdateEventArgs $args
     * @throws MollieOperationForbiddenException
     */
    public function onPreUpdate(Order $order, PreUpdateEventArgs $args)
    {
        if (!$channelId = $this->paymentMethodUtility->getChannelId($order)) {
            return;
        }

        if ($this->isInternalStatusChanged($order, $args)) {
            $this->handleInternalStatusChangedEvents($order, $channelId);
            return;
        }

        if ($this->isTotalValueChanged($args)) {
            $this->handleOrderTotalChange($order, $channelId);
            return;
        }

        if (!$this->orderChangeIsProcessing && self::$addressChanged) {
            $this->handleAddressChange($order, $channelId);
        }
    }

    /**
     * @param Order $order
     */
    public function onPreRemove(Order $order)
    {
        if (!$channelId = $this->paymentMethodUtility->getChannelId($order)) {
            return;
        }

        if (!$this->orderChangeIsProcessing) {
            $this->configService->doWithContext((string)$channelId, function () use ($order) {
                $this->orderChangeIsProcessing = true;
                $this->eventBus->fire(new IntegrationOrderDeletedEvent($order->getIdentifier()));
            });
        }
    }

    /**
     * @param OrderAddress $address
     */
    public function onAddressPreUpdate(OrderAddress $address)
    {
        self::$addressChanged = $address;
    }

    /**
     * @param Order $order
     * @param string $channelId
     */
    protected function handleAddressChange(Order $order, $channelId)
    {
        $this->configService->doWithContext((string)$channelId, function () use ($order) {
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress && $billingAddress->getId() === self::$addressChanged->getId()) {
                try {
                    $this->eventBus->fire(new IntegrationOrderBillingAddressChangedEvent(
                        $order->getIdentifier(),
                        $this->mollieDtoMapper->getAddressData($billingAddress, $order->getEmail())
                    ));
                } catch (\Exception $exception) {
                    $this->handleAddressException($exception, 'billing');
                }
            }

            $shippingAddress = $order->getShippingAddress();
            if ($shippingAddress && $shippingAddress->getId() === self::$addressChanged->getId()) {
                try {
                    $this->eventBus->fire(new IntegrationOrderShippingAddressChangedEvent(
                        $order->getIdentifier(),
                        $this->mollieDtoMapper->getAddressData($shippingAddress, $order->getEmail())
                    ));
                } catch (\Exception $exception) {
                    $this->handleAddressException($exception, 'shipping');
                }
            }
        });
    }

    /**
     * @param \Exception $exception
     * @param $addressType
     * @throws MollieOperationForbiddenException
     */
    protected function handleAddressException(\Exception $exception, $addressType)
    {
        $message = $this->translator->trans(
            "mollie.payment.integration.event.notification.{$addressType}_address_change_error.description",
            ['{api_message}' => $exception->getMessage()]
        );

        throw new MollieOperationForbiddenException($message, 0, $exception);
    }
    /**
     * @param Order $order
     *
     * @return Tracking|null
     */
    protected function getTracking(Order $order)
    {
        if ($order->getShippingTrackings()->isEmpty()) {
            return null;
        }

        /** @var OrderShippingTracking $orderTracking */
        $orderTracking = $order->getShippingTrackings()->first();
        return Tracking::fromArray([
            'carrier' => $orderTracking->getMethod(),
            'code' => $orderTracking->getNumber(),
        ]);
    }

    /**
     * @param Order $order
     * @param PreUpdateEventArgs $args
     *
     * @return bool
     */
    protected function isInternalStatusChanged(Order $order, PreUpdateEventArgs $args)
    {
        return !$this->orderChangeIsProcessing &&
            $args->hasChangedField('internal_status') &&
            $order->getInternalStatus();
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @return bool
     */
    protected function isTotalValueChanged(PreUpdateEventArgs $args)
    {
        return !$this->orderChangeIsProcessing &&
            $args->hasChangedField('totalValue') &&
            ($args->getOldValue('totalValue') != $args->getNewValue('totalValue'));
    }

    /**
     * @param Order $order
     * @param $channelId
     */
    protected function handleInternalStatusChangedEvents(Order $order, $channelId)
    {
        $this->configService->doWithContext(
            (string)$channelId,
            function () use ($order) {
                $this->orderChangeIsProcessing = true;
                $internalStatusId = $order->getInternalStatus()->getId();
                if ($internalStatusId === OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED) {
                    $this->handleEvent(new IntegrationOrderShippedEvent($order->getIdentifier(), $this->getTracking($order)), 'ship');
                }

                if ($internalStatusId === OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED) {
                    $this->handleEvent(new IntegrationOrderClosedEvent($order->getIdentifier()), 'close');
                }

                if ($internalStatusId === OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED) {
                    $this->handleEvent(new IntegrationOrderCanceledEvent($order->getIdentifier()), 'cancel');
                }
            }
        );
    }

    /**
     * @param Event $event
     * @param string $eventType
     */
    protected function handleEvent($event, $eventType)
    {
        try {
            $this->eventBus->fire($event);
        } catch (\Exception $exception) {
            $this->handleStatusUpdateException($exception, $eventType);
        }
    }

    /**
     * @param \Exception $e
     * @param string $eventType
     */
    protected function handleStatusUpdateException(\Exception $e, $eventType)
    {
        throw new MollieOperationForbiddenException(
            $this->translator->trans(
                "mollie.payment.integration.event.notification.order_{$eventType}_error.description",
                ['{api_message}' => $e->getMessage()]
            ),
            0,
            $e
        );
    }

    /**
     * @param Order $order
     * @param string $channelId
     * @throws MollieOperationForbiddenException
     */
    protected function handleOrderTotalChange(Order $order, $channelId)
    {
        try {
            OrderLineEntityListener::setHandleLineEvent(false);
            $this->configService->doWithContext((string)$channelId, function () use ($order) {
                $this->orderChangeIsProcessing = true;
                $this->eventBus->fire(new IntegrationOrderTotalChangedEvent($order->getIdentifier()));
            });
        } catch (\Exception $exception) {
            $screenMessage = $this->translator->trans(
                'mollie.payment.integration.event.notification.order_total_change_error.description',
                ['{api_message}' => $exception->getMessage()]
            );

            throw new MollieOperationForbiddenException($screenMessage, 0, $exception);
        }
    }
}
