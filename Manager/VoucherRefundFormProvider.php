<?php


namespace Mollie\Bundle\PaymentBundle\Manager;


use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefundPayment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Oro\Bundle\LocaleBundle\Twig\LocaleExtension;

class VoucherRefundFormProvider
{
    /**
     * @var OrderReference
     */
    private $orderReference;
    /**
     * @var LocaleExtension
     */
    private $localeExtension;

    /**
     * VoucherRefundFormProvider constructor.
     *
     * @param OrderReference $order
     * @param LocaleExtension $localeExtension
     */
    public function __construct(OrderReference $order, LocaleExtension $localeExtension)
    {
        $this->orderReference = $order;
        $this->localeExtension = $localeExtension;
    }

    /**
     * Check if voucher method is alone
     *
     * @return bool
     */
    public function isVoucherWithoutReminderMethod()
    {
        if (!$this->isVoucher()) {
            return false;
        }

        $reminderDetail = $this->getReminderDetail();

        return !($reminderDetail && $reminderDetail->getRemainderMethod());
    }

    /**
     * Check if payment method is voucher
     *
     * @return bool
     */
    public function isVoucher()
    {
        $order = $this->getOrder();

        return $order && implode('', $order->getMethod()) === 'voucher';
    }

    /**
     * Builds form for voucher refund
     *
     * @return MollieRefund
     */
    public function buildRefundForm()
    {
        $order = $this->getOrder();
        $refund = new MollieRefund();
        $refund->setIsVoucher(true);

        if ($order) {
            $refund->setIsOrderApiUsed(false);
            $reminder = $this->getReminderDetail();
            $reminderAmount = $reminder ? $reminder->getReminderAmount() : $order->getAmount();
            $paymentStatus = $this->getPayment() ? $this->getPayment()->getStatus() : false;

            $currency = $order->getAmount()->getCurrency();
            $refund->setIsOrderRefundable($paymentStatus === 'paid');
            $refunded = $order->getAmountRefunded()->getAmountValue();
            $refund->setTotalRefunded($refunded);
            $refund->setTotalValue($reminderAmount->getAmountValue() - $refunded);
            $paymentRefund = new MollieRefundPayment();
            $paymentRefund->setAmount($reminderAmount->getAmountValue() - $refunded);
            $refund->setRefundPayment($paymentRefund);
            $refund->setCurrency($currency);
            $refund->setCurrencySymbol($this->localeExtension->getCurrencySymbolByCurrency($currency));
        }

        return $refund;
    }

    /**
     * @return \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Details|null
     */
    private function getReminderDetail()
    {
        if ($payment = $this->getPayment()) {
            return $payment->getDetails();
        }

        return null;
    }

    /**
     * @return Payment|null $payment
     */
    private function getPayment()
    {
        $order = $this->getOrder();
        if (!$order) {
            return null;
        }

        $embedded = $order->getEmbedded();


        return !empty($embedded['payments'][0]) ? $embedded['payments'][0] : null;
    }

    /**
     * @return Order|null
     */
    private function getOrder()
    {
        if ($this->orderReference->getApiMethod() !== PaymentMethodConfig::API_METHOD_ORDERS) {
            return null;
        }

        return Order::fromArray($this->orderReference->getPayload());
    }
}
