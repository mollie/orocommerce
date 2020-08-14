<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService as OrderTransitionServiceInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MolliePayment;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order as OroOrder;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Class OrderTransitionService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class OrderTransitionService implements OrderTransitionServiceInterface
{
    /**
     * @var PaymentTransactionProvider
     */
    private $paymentTransactionProvider;
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * OrderTransitionService constructor.
     *
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function payOrder($orderId, array $metadata)
    {
        Logger::logDebug(
            'Order transition handler entered',
            'Infrastructure',
            ['handler' => 'payOrder', 'orderId' => $orderId]
        );

        $result = $this->updatePaymentTransaction($orderId, MolliePayment::CAPTURE);
        $result &= $this->setOrderStatus($orderId, OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN);
        if (!$result) {
            NotificationHub::pushWarning(
                new NotificationText('mollie.payment.webhook.notification.order_pay_error.title'),
                new NotificationText('mollie.payment.webhook.notification.order_pay_error.description')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function expireOrder($orderId, array $metadata)
    {
        Logger::logDebug(
            'Order transition handler entered',
            'Infrastructure',
            ['handler' => 'expireOrder', 'orderId' => $orderId]
        );

        $result = $this->updatePaymentTransaction($orderId, null, false, false);
        $result &= $this->setOrderStatus($orderId, OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED);
        if (!$result) {
            NotificationHub::pushWarning(
                new NotificationText('mollie.payment.webhook.notification.order_expire_error.title'),
                new NotificationText('mollie.payment.webhook.notification.order_expire_error.description')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelOrder($orderId, array $metadata)
    {
        Logger::logDebug(
            'Order transition handler entered',
            'Infrastructure',
            ['handler' => 'cancelOrder', 'orderId' => $orderId]
        );

        $result = $this->updatePaymentTransaction($orderId, null, false, false);
        $result &= $this->setOrderStatus($orderId, OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED);
        if (!$result) {
            NotificationHub::pushWarning(
                new NotificationText('mollie.payment.webhook.notification.order_cancel_error.title'),
                new NotificationText('mollie.payment.webhook.notification.order_cancel_error.description')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function failOrder($orderId, array $metadata)
    {
        Logger::logDebug(
            'Order transition handler entered',
            'Infrastructure',
            ['handler' => 'failOrder', 'orderId' => $orderId]
        );

        $result = $this->updatePaymentTransaction($orderId, MolliePayment::PURCHASE, false, false);
        $result &= $this->setOrderStatus($orderId, OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED);
        if (!$result) {
            NotificationHub::pushWarning(
                new NotificationText('mollie.payment.webhook.notification.order_fail_error.title'),
                new NotificationText('mollie.payment.webhook.notification.order_fail_error.description')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function completeOrder($orderId, array $metadata)
    {
        Logger::logDebug(
            'Order transition handler entered',
            'Infrastructure',
            ['handler' => 'completeOrder', 'orderId' => $orderId]
        );

        $result = $this->updatePaymentTransaction($orderId, MolliePayment::CAPTURE);
        $result &= $this->setOrderStatus($orderId, OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED);
        if (!$result) {
            NotificationHub::pushWarning(
                new NotificationText('mollie.payment.webhook.notification.order_complete_error.title'),
                new NotificationText('mollie.payment.webhook.notification.order_complete_error.description')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function authorizeOrder($orderId, array $metadata)
    {
        Logger::logDebug(
            'Order transition handler entered',
            'Infrastructure',
            ['handler' => 'authorizeOrder', 'orderId' => $orderId]
        );

        $result = $this->updatePaymentTransaction($orderId, MolliePayment::AUTHORIZE);
        if (!$result) {
            NotificationHub::pushWarning(
                new NotificationText('mollie.payment.webhook.notification.order_authorize_error.title'),
                new NotificationText('mollie.payment.webhook.notification.order_authorize_error.description')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refundOrder($orderId, array $metadata)
    {
        Logger::logDebug(
            'Order transition handler entered',
            'Infrastructure',
            ['handler' => 'refundOrder', 'orderId' => $orderId]
        );

        $result = $this->setOrderStatus($orderId, OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED);
        if (!$result) {
            NotificationHub::pushWarning(
                new NotificationText('mollie.payment.webhook.notification.order_refund_error.title'),
                new NotificationText('mollie.payment.webhook.notification.order_refund_error.description')
            );
        }
    }

    /**
     * @param string $orderId
     * @param string $action
     * @param bool $successful
     * @param bool $active
     *
     * @return bool
     */
    protected function updatePaymentTransaction($orderId, $action = null, $successful = true, $active = true)
    {
        /** @var OroOrder $order */
        $order = $this->doctrineHelper->getEntityReference(OroOrder::class, $orderId);
        if (!$order) {
            Logger::logError(
                "Payment transaction could not be updated. Order with id({$orderId}) not found.",
                'Integration',
                ['OrderId' => $orderId]
            );

            return false;
        }

        /** @var PaymentTransaction $paymentTransaction */
        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction($order);
        if (!$paymentTransaction) {
            Logger::logError(
                "Payment transaction could not be updated. Payment transaction for order id({$orderId}) not found.",
                'Integration',
                ['OrderId' => $orderId]
            );

            return false;
        }

        $paymentTransaction->setSuccessful($successful);
        $paymentTransaction->setActive($active);
        if ($action) {
            $paymentTransaction->setAction($action);
        }

        return true;
    }

    /**
     * @param string $orderId
     * @param string $statusId
     *
     * @return bool
     */
    protected function setOrderStatus($orderId, $statusId)
    {
        $cancelStatus = $this->getInternalStatus($statusId);
        if (!$cancelStatus) {
            Logger::logError(
                "Order status could not be changed to '{$statusId}' status. Enum value not found.",
                'Integration',
                ['OrderId' => $orderId, 'StatusId' => $statusId]
            );

            return false;
        }

        /** @var OroOrder $order */
        $order = $this->doctrineHelper->getEntityReference(OroOrder::class, $orderId);
        if (!$order) {
            Logger::logError(
                "Order status could not be changed to '{$statusId}' status. Order with id({$orderId}) not found.",
                'Integration',
                ['OrderId' => $orderId, 'status' => $statusId]
            );

            return false;
        }

        $order->setInternalStatus($cancelStatus);

        return true;
    }

    /**
     * @param string $statusId
     *
     * @return object|AbstractEnumValue|null
     */
    protected function getInternalStatus($statusId)
    {
        $className = ExtendHelper::buildEnumValueClassName(OroOrder::INTERNAL_STATUS_CODE);
        $entityManager = $this->doctrineHelper->getEntityManagerForClass($className);
        if (!$entityManager) {
            return null;
        }

        return $entityManager->getRepository($className)->find($statusId);
    }
}
