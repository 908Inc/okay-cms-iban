<?php

namespace Okay\Modules\Opendatabot\IbanInvoice\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Core\BackendTranslations;
use Okay\Core\Request;
use Okay\Core\ServiceLocator;
use Okay\Modules\Opendatabot\IbanInvoice\Helpers\IbanValidator;
use Okay\Modules\Opendatabot\IbanInvoice\OpendatabotApi;

class BackendExtender implements ExtensionInterface
{
    private const MODULE_NAME = 'Opendatabot/IbanInvoice';

    /**
     * Normalize and fill defaults for payment method settings on save.
     *
     * @param array $paymentSettings
     * @return array
     */
    public function postSettings($paymentSettings)
    {
        if (!is_array($paymentSettings)) {
            return $paymentSettings;
        }

        $sl = ServiceLocator::getInstance();

        /** @var Request $request */
        $request = $sl->getService(Request::class);

        $module = (string) $request->post('module');
        if ($module !== self::MODULE_NAME) {
            return $paymentSettings;
        }

        $iban = IbanValidator::normalizeIban(isset($paymentSettings['iban']) ? $paymentSettings['iban'] : '');
        $code = IbanValidator::normalizeDigits(isset($paymentSettings['code']) ? $paymentSettings['code'] : '');
        $apiKey = trim((string) (isset($paymentSettings['apiKey']) ? $paymentSettings['apiKey'] : ''));
        $clientName = trim((string) (isset($paymentSettings['clientName']) ? $paymentSettings['clientName'] : ''));
        $purpose = trim((string) (isset($paymentSettings['purpose']) ? $paymentSettings['purpose'] : ''));

        if ($apiKey === '') {
            $apiKey = OpendatabotApi::DEFAULT_CLIENT_KEY;
        }

        if ($clientName === '') {
            $clientName = OpendatabotApi::DEFAULT_CLIENT_NAME;
        }

        $paymentSettings['iban'] = $iban;
        $paymentSettings['code'] = $code;
        $paymentSettings['apiKey'] = $apiKey;
        $paymentSettings['clientName'] = $clientName;
        $paymentSettings['purpose'] = $purpose;

        return $paymentSettings;
    }

    /**
     * Validate required payment settings before saving the payment method.
     *
     * @param string $error
     * @param object $payment
     * @return string
     */
    public function getPaymentValidateError($error, $payment)
    {
        if (!empty($error) || !is_object($payment)) {
            return $error;
        }

        $module = isset($payment->module) ? (string) $payment->module : '';
        if ($module !== self::MODULE_NAME) {
            return $error;
        }

        $sl = ServiceLocator::getInstance();

        /** @var Request $request */
        $request = $sl->getService(Request::class);

        /** @var BackendTranslations $backendTranslations */
        $backendTranslations = $sl->getService(BackendTranslations::class);

        $paymentSettings = $request->post('payment_settings', null, []);
        if (!is_array($paymentSettings)) {
            $paymentSettings = [];
        }

        $iban = IbanValidator::normalizeIban(isset($paymentSettings['iban']) ? $paymentSettings['iban'] : '');
        $code = IbanValidator::normalizeDigits(isset($paymentSettings['code']) ? $paymentSettings['code'] : '');

        if ($iban === '') {
            return $this->getTranslation($backendTranslations, 'opendatabot_iban_invoice_error_empty_iban', 'IBAN is required.');
        }

        if (!IbanValidator::isValidUaIban($iban)) {
            return $this->getTranslation($backendTranslations, 'opendatabot_iban_invoice_error_invalid_iban', 'Invalid IBAN.');
        }

        if ($code === '') {
            return $this->getTranslation($backendTranslations, 'opendatabot_iban_invoice_error_empty_code', 'Company code is required.');
        }

        if (!IbanValidator::isValidCode($code)) {
            return $this->getTranslation($backendTranslations, 'opendatabot_iban_invoice_error_invalid_code', 'Invalid company code (TIN/EDRPOU).');
        }

        return $error;
    }

    private function getTranslation(BackendTranslations $backendTranslations, $key, $fallback)
    {
        $translation = $backendTranslations->getTranslation((string) $key);
        if (!empty($translation)) {
            return $translation;
        }

        return $fallback;
    }
}
