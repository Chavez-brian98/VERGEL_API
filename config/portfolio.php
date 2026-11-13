<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Saldo negativo en billetera
    |--------------------------------------------------------------------------
    |
    | Define si la cuenta corriente del cliente puede quedar con saldo negativo
    | (crédito). Por defecto se rechazan los cargos que dejen la billetera en
    | negativo; activar esta regla de negocio mediante variable de entorno.
    |
    */

    'allow_negative_cash_balance' => (bool) env('PORTFOLIO_ALLOW_NEGATIVE_CASH_BALANCE', false),

];
