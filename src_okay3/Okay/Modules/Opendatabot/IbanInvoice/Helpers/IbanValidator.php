<?php

namespace Okay\Modules\Opendatabot\IbanInvoice\Helpers;

class IbanValidator
{
    public static function normalizeIban($iban): string
    {
        $iban = strtoupper((string) $iban);
        $iban = preg_replace('/\s+/', '', $iban);

        return is_string($iban) ? $iban : '';
    }

    public static function normalizeDigits($value): string
    {
        $value = preg_replace('/\\D+/', '', (string) $value);

        return is_string($value) ? $value : '';
    }

    public static function formatAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public static function isValidCode($code): bool
    {
        $code = (string) $code;

        if (empty($code)) {
            return false;
        }

        return (bool) preg_match('/^\d{8}$|^\d{10}$/', $code);
    }

    /**
     * Validate IBAN.
     *
     * Rules from Opendatabot docs:
     * - starts with "UA"
     * - total length 29 (UA + 27 digits)
     * - MOD-97 checksum is valid
     */
    public static function isValidUaIban($iban): bool
    {
        $iban = (string) $iban;

        if (!preg_match('/^UA\d{27}$/', $iban)) {
            return false;
        }

        return self::ibanMod97($iban);
    }

    private static function ibanMod97(string $iban): bool
    {
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        $expanded = '';
        $length = strlen($rearranged);
        for ($i = 0; $i < $length; $i++) {
            $ch = $rearranged[$i];
            if ($ch >= '0' && $ch <= '9') {
                $expanded .= $ch;
                continue;
            }

            if ($ch >= 'A' && $ch <= 'Z') {
                $expanded .= (string) (ord($ch) - 55); // A=10 ... Z=35
                continue;
            }

            return false;
        }

        $mod = 0;
        $expandedLength = strlen($expanded);
        for ($i = 0; $i < $expandedLength; $i++) {
            $mod = ($mod * 10 + (int) $expanded[$i]) % 97;
        }

        return $mod === 1;
    }
}
