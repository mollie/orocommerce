<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderLineTransitionService as OrderLineTransitionServiceInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

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

    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        DoctrineHelper $doctrineHelper
    )
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @inheritDoc
     */
    public function payOrderLine($orderId, OrderLine $newOrderLine)
    {
        // Intentionally left blank. For oro integration this method is null operation
    }

    /**
     * @inheritDoc
     */
    public function cancelOrderLine($orderId, OrderLine $newOrderLine)
    {
        NotificationHub::pushInfo(
            new NotificationText('mollie.payment.webhook.notification.order_line_cancel_info.title'),
            new NotificationText('mollie.payment.webhook.notification.order_line_cancel_info.description')
        );
    }

    /**
     * @inheritDoc
     */
    public function authorizeOrderLine($orderId, OrderLine $newOrderLine)
    {
        // Intentionally left blank. For oro integration this method is null operation
    }

    /**
     * @inheritDoc
     */
    public function completeOrderLine($orderId, OrderLine $newOrderLine)
    {
        // Intentionally left blank. For oro integration this method is null operation
    }

    public function refundOrderLine($orderId, OrderLine $newOrderLine)
    {
        NotificationHub::pushInfo(
            new NotificationText('mollie.payment.webhook.notification.order_line_refund_info.title'),
            new NotificationText('mollie.payment.webhook.notification.order_line_refund_info.description')
        );
    }
}