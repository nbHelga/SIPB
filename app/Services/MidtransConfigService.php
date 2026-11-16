<?php

namespace App\Services;

use Midtrans\Config as MidtransConfig;

class MidtransConfigService
{
    public function configure(): void
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key')
            ?: env('MIDTRANS_SERVER_KEY');
        MidtransConfig::$isProduction = filter_var(
            config('services.midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }
}
