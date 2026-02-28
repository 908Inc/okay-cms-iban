<?php

namespace Okay\Modules\Opendatabot\IbanInvoice;

use Okay\Core\EntityFactory;
use Okay\Core\Modules\AbstractModule;
use Okay\Core\Modules\Interfaces\PaymentFormInterface;
use Okay\Core\Router;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Modules\Opendatabot\IbanInvoice\Helpers\IbanValidator;

class PaymentForm extends AbstractModule implements PaymentFormInterface
{
    /**
     * @var EntityFactory
     */
    private $entityFactory;

    public function __construct(EntityFactory $entityFactory)
    {
        parent::__construct();
        $this->entityFactory = $entityFactory;
    }

    /**
     * @inheritDoc
     */
    public function checkoutForm($orderId)
    {
        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $this->entityFactory->get(OrdersEntity::class);

        /** @var PaymentsEntity $paymentsEntity */
        $paymentsEntity = $this->entityFactory->get(PaymentsEntity::class);

        /** @var CurrenciesEntity $currenciesEntity */
        $currenciesEntity = $this->entityFactory->get(CurrenciesEntity::class);

        $order = $ordersEntity->get((int) $orderId);
        if (empty($order) || empty($order->id)) {
            return '';
        }

        $paymentMethod = $paymentsEntity->get((int) $order->payment_method_id);
        if (empty($paymentMethod) || empty($paymentMethod->id)) {
            return '';
        }

        $paymentCurrency = $currenciesEntity->get((int) $paymentMethod->currency_id);
        if (empty($paymentCurrency) || strtoupper((string) $paymentCurrency->code) !== 'UAH') {
            return $this->renderError($this->getLangVar('opendatabot_iban_invoice_error_only_uah', 'This payment method is available only for UAH orders.'));
        }

        $settings = $paymentsEntity->getPaymentSettings((int) $paymentMethod->id);

        $iban = IbanValidator::normalizeIban(isset($settings['iban']) ? $settings['iban'] : '');
        $code = IbanValidator::normalizeDigits(isset($settings['code']) ? $settings['code'] : '');

        if ($iban === '' || $code === '') {
            return $this->renderError($this->getLangVar('opendatabot_iban_invoice_error_not_configured', 'Payment method is not configured.'));
        }
        if (!IbanValidator::isValidUaIban($iban)) {
            return $this->renderError($this->getLangVar('opendatabot_iban_invoice_error_invalid_iban', 'Payment method is not configured (invalid IBAN).'));
        }
        if (!IbanValidator::isValidCode($code)) {
            return $this->renderError($this->getLangVar('opendatabot_iban_invoice_error_invalid_code', 'Payment method is not configured (invalid company code).'));
        }

        $action = Router::generateUrl('Opendatabot_IbanInvoice_create_invoice', [], true);

        $this->design->assign('action', $action);
        $this->design->assign('fields', [
            'order_url' => (string) $order->url,
        ]);
        $this->design->assign('button_text', $this->getLangVar('opendatabot_iban_invoice_pay_button', 'Оплатити IBAN рахунок'));
        $this->design->assign('error', $this->consumeFlashError());

        return $this->design->fetch('form.tpl');
    }

    private function getLangVar($key, $fallback)
    {
        $lang = $this->design->getVar('lang');
        if (!is_object($lang)) {
            return $fallback;
        }

        $value = $lang->$key;
        if ($value === null || $value === '') {
            return $fallback;
        }

        return $value;
    }

    private function renderError($message)
    {
        $this->design->assign('action', '');
        $this->design->assign('fields', []);
        $this->design->assign('button_text', '');
        $this->design->assign('error', (string) $message);

        return $this->design->fetch('form.tpl');
    }

    private function consumeFlashError(): string
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return '';
        }

        if (empty($_SESSION['opendatabot_iban_invoice_error']) || !is_string($_SESSION['opendatabot_iban_invoice_error'])) {
            return '';
        }

        $keyOrMessage = trim($_SESSION['opendatabot_iban_invoice_error']);
        unset($_SESSION['opendatabot_iban_invoice_error']);

        if ($keyOrMessage === '') {
            return '';
        }

        return $this->getLangVar($keyOrMessage, $keyOrMessage);
    }
}
