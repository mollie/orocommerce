<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderLineTransitionService as OrderLineTransitionServiceInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Class OrderLineTransitionService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class OrderLineTransitionService implements OrderLineTransitionServiceInterface
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
     * OrderLineTransitionService constructor.
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
    public function payOrderLine($orderId, OrderLine $newOrderLine)
    {
        // Intentionally left blank. For oro integration this method is null operation
    }

    /**
     * {@inheritdoc}
     */
    public function cancelOrderLine($orderId, OrderLine $newOrderLine)
    {
        NotificationHub::pushInfo(
            new NotificationText('mollie.payment.webhook.notification.order_line_cancel_info.title'),
            new NotificationText('mollie.payment.webhook.notification.order_line_cancel_info.description')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authorizeOrderLine($orderId, OrderLine $newOrderLine)
    {
        // Intentionally left blank. For oro integration this method is null operation
    }

    /**
     * {@inheritdoc}
     */
    public function completeOrderLine($orderId, OrderLine $newOrderLine)
    {
        // Intentionally left blank. For oro integration this method is null operation
    }

    /**
     * {@inheritdoc}
     *
     * @param string $orderId
     * @param OrderLine $newOrderLine
     *
     * @return mixed|void
     */
    public function refundOrderLine($orderId, OrderLine $newOrderLine)
    {
        NotificationHub::pushInfo(
            new NotificationText('mollie.payment.webhook.notification.order_line_refund_info.title'),
            new NotificationText('mollie.payment.webhook.notification.order_line_refund_info.description')
        );
    }
}
