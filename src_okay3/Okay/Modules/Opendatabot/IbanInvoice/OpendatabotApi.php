<?php

namespace Okay\Modules\Opendatabot\IbanInvoice;

final class OpendatabotApi
{
    public const BASE_URL = 'https://iban.opendatabot.ua';
    public const INVOICE_ENDPOINT = 'https://iban.opendatabot.ua/api/invoice';

    // Public key from Opendatabot form example.
    public const DEFAULT_CLIENT_KEY = 'KUI8gwVJb3OQN1LuTKEsBx8feSYOJK2m';
    public const DEFAULT_CLIENT_NAME = 'public';

    private function __construct()
    {
    }
}
