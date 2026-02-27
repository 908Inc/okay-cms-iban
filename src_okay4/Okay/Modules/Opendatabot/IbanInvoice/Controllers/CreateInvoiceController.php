<?php

namespace Okay\Modules\Opendatabot\IbanInvoice\Controllers;

use Okay\Controllers\AbstractController;
use Okay\Core\Money;
use Okay\Core\Router;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Modules\Opendatabot\IbanInvoice\Helpers\IbanValidator;
use Okay\Modules\Opendatabot\IbanInvoice\OpendatabotApi;
use Psr\Log\LoggerInterface;

class CreateInvoiceController extends AbstractController
{
    public function createInvoice(
        OrdersEntity $ordersEntity,
        PaymentsEntity $paymentsEntity,
        CurrenciesEntity $currenciesEntity,
        Money $money,
        LoggerInterface $logger
    )
    {
        $orderUrl = trim((string) $this->request->post('order_url'));
        if (empty($orderUrl)) {
            $this->setPaymentError('opendatabot_iban_invoice_error_api');
            $this->response->redirectTo(Router::generateUrl('main', [], true));
            return;
        }

        $order = $ordersEntity->get($orderUrl);
        if (empty($order) || empty($order->id)) {
            $this->setPaymentError('opendatabot_iban_invoice_error_api');
            $this->response->redirectTo(Router::generateUrl('main', [], true));
            return;
        }

        $paymentMethod = $paymentsEntity->get((int) $order->payment_method_id);
        if (empty($paymentMethod) || empty($paymentMethod->id)) {
            $this->setPaymentError('opendatabot_iban_invoice_error_api');
            $this->response->redirectTo(Router::generateUrl('order', ['url' => $order->url], true));
            return;
        }

        if ((string) $paymentMethod->module !== 'Opendatabot/IbanInvoice') {
            $this->setPaymentError('opendatabot_iban_invoice_error_api');
            $this->response->redirectTo(Router::generateUrl('order', ['url' => $order->url], true));
            return;
        }

        $paymentCurrency = $currenciesEntity->get((int) $paymentMethod->currency_id);
        if (empty($paymentCurrency) || strtoupper((string) $paymentCurrency->code) !== 'UAH') {
            $this->setPaymentError('opendatabot_iban_invoice_error_only_uah');
            $this->response->redirectTo(Router::generateUrl('order', ['url' => $order->url], true));
            return;
        }

        $settings = $paymentsEntity->getPaymentSettings((int) $paymentMethod->id);

        $iban = IbanValidator::normalizeIban(isset($settings['iban']) ? $settings['iban'] : '');
        $code = IbanValidator::normalizeDigits(isset($settings['code']) ? $settings['code'] : '');
        $apiKey = trim((string) (isset($settings['apiKey']) ? $settings['apiKey'] : ''));
        $clientName = trim((string) (isset($settings['clientName']) ? $settings['clientName'] : ''));
        $purposeTemplate = trim((string) (isset($settings['purpose']) ? $settings['purpose'] : ''));

        if ($apiKey === '') {
            $apiKey = OpendatabotApi::DEFAULT_CLIENT_KEY;
        }
        if ($clientName === '') {
            $clientName = OpendatabotApi::DEFAULT_CLIENT_NAME;
        }
        if ($purposeTemplate === '') {
            $purposeTemplate = $this->getLangVar('opendatabot_iban_invoice_purpose_default', 'Оплата за замовлення №%order_id%');
        }

        if ($iban === '' || $code === '' || $apiKey === '' || $clientName === '') {
            $this->setPaymentError('opendatabot_iban_invoice_error_not_configured');
            $this->response->redirectTo(Router::generateUrl('order', ['url' => $order->url], true));
            return;
        }

        if (!IbanValidator::isValidUaIban($iban)) {
            $this->setPaymentError('opendatabot_iban_invoice_error_invalid_iban');
            $this->response->redirectTo(Router::generateUrl('order', ['url' => $order->url], true));
            return;
        }

        if (!IbanValidator::isValidCode($code)) {
            $this->setPaymentError('opendatabot_iban_invoice_error_invalid_code');
            $this->response->redirectTo(Router::generateUrl('order', ['url' => $order->url], true));
            return;
        }

        $amount = (float) $money->convert($order->total_price, $paymentMethod->currency_id, false);
        $amount = IbanValidator::formatAmount($amount);

        $purpose = str_replace('%order_id%', (string) $order->id, $purposeTemplate);
        $purpose = trim($purpose);

        $fields = [
            'code' => $code,
            'iban' => $iban,
            'amount' => $amount,
            'purpose' => $purpose,
            'x-client-key' => $apiKey,
            'x-client-name' => $clientName,
            'redirect' => 'true',
        ];

        $redirectUrl = $this->requestInvoiceRedirectUrl($fields, $logger);
        if ($redirectUrl === null) {
            $this->setPaymentError('opendatabot_iban_invoice_error_api');
            $this->response->redirectTo(Router::generateUrl('order', ['url' => $order->url], true));
            return;
        }

        $this->response->redirectTo($redirectUrl);
    }

    private function requestInvoiceRedirectUrl(array $fields, LoggerInterface $logger): ?string
    {
        $postString = http_build_query($fields, '', '&', PHP_QUERY_RFC3986);

        if (!function_exists('curl_init')) {
            $logger->warning('Opendatabot IBAN: curl extension is not available');
            return null;
        }

        $ch = curl_init();
        if ($ch === false) {
            $logger->warning('Opendatabot IBAN: curl_init() failed');
            return null;
        }

        curl_setopt($ch, CURLOPT_URL, OpendatabotApi::INVOICE_ENDPOINT);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            $logger->warning('Opendatabot IBAN: curl_exec() failed: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);

        if (in_array($status, [301, 302, 303, 307, 308], true)) {
            $location = $this->extractHeaderValue($rawHeaders, 'Location');
            if ($location !== null) {
                return $this->normalizeRedirectUrl($location);
            }
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            foreach (['url', 'invoice_url', 'redirect_url', 'redirectUrl'] as $key) {
                if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                    return $this->normalizeRedirectUrl($decoded[$key]);
                }
            }
            if (!empty($decoded['data']) && is_array($decoded['data'])) {
                foreach (['url', 'invoice_url', 'redirect_url', 'redirectUrl'] as $key) {
                    if (!empty($decoded['data'][$key]) && is_string($decoded['data'][$key])) {
                        return $this->normalizeRedirectUrl($decoded['data'][$key]);
                    }
                }
            }
        }

        $logger->warning('Opendatabot IBAN: unexpected response', [
            'status' => $status,
            'headers' => $rawHeaders,
            'body' => substr((string) $body, 0, 1024),
        ]);

        return null;
    }

    private function extractHeaderValue(string $rawHeaders, string $name): ?string
    {
        $nameLower = strtolower($name);
        $lines = preg_split("/\\r\\n|\\n|\\r/", $rawHeaders);
        if (!is_array($lines)) {
            return null;
        }

        $value = null;
        foreach ($lines as $line) {
            $pos = strpos($line, ':');
            if ($pos === false) {
                continue;
            }

            $headerName = strtolower(trim(substr($line, 0, $pos)));
            if ($headerName !== $nameLower) {
                continue;
            }

            $headerValue = trim(substr($line, $pos + 1));
            if ($headerValue !== '') {
                $value = $headerValue;
            }
        }

        return $value;
    }

    private function normalizeRedirectUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }

        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }

        if ($url[0] === '/') {
            return rtrim(OpendatabotApi::BASE_URL, '/') . $url;
        }

        return rtrim(OpendatabotApi::BASE_URL, '/') . '/' . $url;
    }

    private function setPaymentError(string $errorKeyOrMessage): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return;
        }

        $_SESSION['opendatabot_iban_invoice_error'] = $errorKeyOrMessage;
    }

    private function getLangVar(string $key, string $fallback): string
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
}
