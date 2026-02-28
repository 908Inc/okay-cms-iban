<?php

$lang = [];

$lang['opendatabot_iban_invoice_settings'] = 'Opendatabot IBAN invoice';
$lang['opendatabot_iban_invoice_iban'] = 'IBAN';
$lang['opendatabot_iban_invoice_iban_desc'] = 'IBAN';
$lang['opendatabot_iban_invoice_company_code'] = 'Code (TIN/EDRPOU)';
$lang['opendatabot_iban_invoice_company_code_desc'] = '8 digits (EDRPOU) or 10 digits (TIN)';
$lang['opendatabot_iban_invoice_api_key'] = 'Key';
$lang['opendatabot_iban_invoice_api_key_desc'] = 'Leave empty to use the public key (saved automatically).';
$lang['opendatabot_iban_invoice_client_name'] = 'Client name';
$lang['opendatabot_iban_invoice_client_name_desc'] = 'Leave empty to use "public" (saved automatically).';
$lang['opendatabot_iban_invoice_purpose'] = 'Payment purpose (use %order_id%)';
$lang['opendatabot_iban_invoice_error_empty_iban'] = 'Please fill IBAN in module settings.';
$lang['opendatabot_iban_invoice_error_invalid_iban'] = 'Please provide a valid IBAN.';
$lang['opendatabot_iban_invoice_error_empty_code'] = 'Please fill code (TIN/EDRPOU) in module settings.';
$lang['opendatabot_iban_invoice_error_invalid_code'] = 'Please provide a valid code (8 digits EDRPOU or 10 digits TIN).';

return $lang;
