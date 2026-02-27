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
            [BackendPaymentsRequest::class, 'postSettings'],
            [BackendExtender::class, 'postSettings']
        );

        $this->registerChainExtension(
            [BackendValidateHelper::class, 'getPaymentValidateError'],
            [BackendExtender::class, 'getPaymentValidateError']
        );

        $this->registerChainExtension(
            [PaymentsHelper::class, 'getCartPaymentsList'],
            [FrontExtender::class, 'getCartPaymentsList']
        );

        $this->registerChainExtension(
            [OrdersHelper::class, 'getOrderPaymentMethodsList'],
            [FrontExtender::class, 'getOrderPaymentMethodsList']
        );
    }
}
