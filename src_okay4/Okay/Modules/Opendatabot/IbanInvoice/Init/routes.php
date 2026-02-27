<?php

namespace Okay\Modules\Opendatabot\IbanInvoice;

return [
    'Opendatabot_IbanInvoice_create_invoice' => [
        'slug' => 'payment/Opendatabot/IbanInvoice/create-invoice',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\CreateInvoiceController',
            'method' => 'createInvoice',
        ],
    ],
];

