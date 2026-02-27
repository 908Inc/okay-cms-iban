<?php

namespace Okay\Modules\Opendatabot\IbanInvoice\Extenders;

use Okay\Core\EntityFactory;
use Okay\Core\Design;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\ServiceLocator;
use Okay\Entities\CurrenciesEntity;

class FrontExtender implements ExtensionInterface
{
    private const MODULE_NAME = 'Opendatabot/IbanInvoice';

    /**
     * Hide the payment method from cart payments list when current storefront currency is not UAH.
     *
     * @param array $payments
     * @param object $cart
     * @return array
     */
    public function getCartPaymentsList($payments, $cart)
    {
        if (!is_array($payments) || $this->isCurrentCurrencyUah()) {
            return $payments;
        }

        foreach ($payments as $paymentId => $payment) {
            if (is_object($payment) && isset($payment->module) && (string) $payment->module === self::MODULE_NAME) {
                unset($payments[$paymentId]);
            }
        }

        return $payments;
    }

    /**
     * Hide the payment method from order payment methods list when current storefront currency is not UAH.
     *
     * @param array $paymentMethods
     * @param object $order
     * @return array
     */
    public function getOrderPaymentMethodsList($paymentMethods, $order)
    {
        if (!is_array($paymentMethods) || $this->isCurrentCurrencyUah()) {
            return $paymentMethods;
        }

        $result = [];
        foreach ($paymentMethods as $paymentMethod) {
            if (is_object($paymentMethod) && isset($paymentMethod->module) && (string) $paymentMethod->module === self::MODULE_NAME) {
                continue;
            }
            $result[] = $paymentMethod;
        }

        return $result;
    }

    private function isCurrentCurrencyUah(): bool
    {
        $sl = ServiceLocator::getInstance();

        if ($sl->hasService(Design::class)) {
            /** @var Design $design */
            $design = $sl->getService(Design::class);
            $currency = $design->getVar('currency');
            if (is_object($currency) && isset($currency->code) && is_string($currency->code) && $currency->code !== '') {
                return strtoupper($currency->code) === 'UAH';
            }
        }

        /** @var EntityFactory $entityFactory */
        $entityFactory = $sl->getService(EntityFactory::class);

        /** @var CurrenciesEntity $currenciesEntity */
        $currenciesEntity = $entityFactory->get(CurrenciesEntity::class);

        $currency = null;
        if (isset($_SESSION['currency_id'])) {
            $currency = $currenciesEntity->get((int) $_SESSION['currency_id']);
        }

        if (empty($currency) || empty($currency->code)) {
            $currency = $currenciesEntity->getMainCurrency();
        }

        if (empty($currency) || empty($currency->code)) {
            return true;
        }

        return strtoupper((string) $currency->code) === 'UAH';
    }
}
