<?php

namespace App\Console\Commands;

use App\Services\PendingSelfOrderExpiryService;
use Illuminate\Console\Command;

class ExpirePendingSelfOrders extends Command
{
    /**
     * Execute the console command.
     */
    protected $signature = 'selforder:expire-pending';
    protected $description = 'Batalkan self-order pending yang melewati batas waktu pembayaran.';

    public function handle(PendingSelfOrderExpiryService $expiryService): int
    {
        $expiryService->expireDue();
        return self::SUCCESS;
    }
}
