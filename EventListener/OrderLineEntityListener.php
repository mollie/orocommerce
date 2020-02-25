<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderLineChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\EventBus;
use Mollie\Bundle\PaymentBundle\Manager\OroPaymentMethodUtility;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class OrderLineEntityListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class OrderLineEntityListener
{
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
     * @var FlashBagInterface
     */
    private $flashBag;

    public static $handleLineEvent = true;

    /**
     * OrderLineEntityListener constructor.
     *
     * @param OroPaymentMethodUtility $paymentMethodUtility
     * @param MollieDtoMapperInterface $mollieDtoMapper
     * @param TranslatorInterface $translator
     * @param FlashBagInterface $flashBag
     */
    public function __construct(
        OroPaymentMethodUtility $paymentMethodUtility,
        MollieDtoMapperInterface $mollieDtoMapper,
        TranslatorInterface $translator,
        FlashBagInterface $flashBag
    ) {
        $this->mollieDtoMapper = $mollieDtoMapper;
        $this->paymentMethodUtility = $paymentMethodUtility;
        $this->translator = $translator;
        $this->flashBag = $flashBag;
    }

    /**
     * @param OrderLineItem $orderLineItem
     */
    public function onPreUpdate(OrderLineItem $orderLineItem, PreUpdateEventArgs $args)
    {
        if (!static::$handleLineEvent || !$this->isOrderLineChanged($args)) {
            return;
        }

        try {
            $channelId = $this->paymentMethodUtility->getChannelId($orderLineItem->getOrder());
            /** @var Configuration $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            $configService->doWithContext((string)$channelId, function () use ($orderLineItem) {
                $lineForUpdate = $this->mollieDtoMapper->getOrderLine($orderLineItem);
                $lineForUpdate->setId($this->getLineIdFromMollie($orderLineItem));
                /** @var EventBus $eventBus */
                $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);
                $eventBus->fire(new IntegrationOrderLineChangedEvent($orderLineItem->getOrder()->getIdentifier(), $lineForUpdate));
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
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        if ($orderReference = $orderReferenceService->getByShopReference($oroLineItem->getOrder()->getIdentifier())) {
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
        foreach ($changeSetKeys as $key) {
            if ($args->getOldValue($key) != $args->getNewValue($key)) {
                return true;
            }
        }

        return false;
    }
}
