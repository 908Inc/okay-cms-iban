<?php

namespace Okay\Modules\Opendatabot\IbanInvoice\Init;

use Okay\Admin\Helpers\BackendValidateHelper;
use Okay\Admin\Requests\BackendPaymentsRequest;
use Okay\Core\Modules\AbstractInit;
use Okay\Helpers\OrdersHelper;
use Okay\Helpers\PaymentsHelper;
use Okay\Modules\Opendatabot\IbanInvoice\Extenders\BackendExtender;
use Okay\Modules\Opendatabot\IbanInvoice\Extenders\FrontExtender;

class Init extends AbstractInit
{
    public function install()
    {
        $this->setModuleType(MODULE_TYPE_PAYMENT);
    }

    public function init()
    {
        $this->registerChainExtension(
            ['class' => BackendPaymentsRequest::class, 'method' => 'postSettings'],
            ['class' => BackendExtender::class, 'method' => 'postSettings']
        );

        $this->registerChainExtension(
            ['class' => BackendValidateHelper::class, 'method' => 'getPaymentValidateError'],
            ['class' => BackendExtender::class, 'method' => 'getPaymentValidateError']
        );

        $this->registerChainExtension(
            ['class' => PaymentsHelper::class, 'method' => 'getCartPaymentsList'],
            ['class' => FrontExtender::class, 'method' => 'getCartPaymentsList']
        );

        $this->registerChainExtension(
            ['class' => OrdersHelper::class, 'method' => 'getOrderPaymentMethodsList'],
            ['class' => FrontExtender::class, 'method' => 'getOrderPaymentMethodsList']
        );
    }
}
