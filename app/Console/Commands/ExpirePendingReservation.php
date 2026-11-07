<?php

namespace App\Console\Commands;

use App\Http\Controllers\customer\BookingController;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reservation:expire-pending')]
#[Description('Membatalkan reservasi pending yang melewati batas pembayaran.')]
class ExpirePendingReservation extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BookingController $bookingController): int
    {
        $bookingController->expirePendingReservations();

        return self::SUCCESS;
    }
}
