<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservasi extends Model
{
    protected $table = 'reservasi';

    protected $fillable = [
        'id_customer', 
        'id_pesanan',
        'tanggal', 
        'jam_mulai', 
        'jam_selesai', 
        'jml_org', 
        'keterangan', 
        'cancellation_reason',
        'dp_forfeited',
        'rejection_reason',
        'approval_pending_customer_notif',
        'approval_pending_admin_notif',
        'approval_decision_customer_notif',
        // 'dp_bayar', 
        // 'status_notif'
        'customer_notif',
        'admin_notif'
        ];

    protected $casts = [
        'tanggal'    => 'date',
        // FIX MISC-01: jam_mulai/jam_selesai adalah TIME string (H:i:s), jangan di-cast ke datetime
        // Cast ke datetime membuat Eloquent parse sebagai Carbon dengan tanggal hari ini,
        // yang menyebabkan strtotime() di view mengembalikan nilai tidak terduga.
        // 'jam_mulai'  => 'datetime:H:i',  // DIHAPUS
        // 'jam_selesai' => 'datetime:H:i', // DIHAPUS
        'dp_bayar'   => 'decimal:2',
        'jml_org'    => 'integer',
        // 'status_notif' => 'boolean',
        'customer_notif' => 'boolean',
        'admin_notif' => 'boolean',
        'dp_forfeited' => 'boolean',
        'approval_pending_customer_notif' => 'boolean',
        'approval_pending_admin_notif' => 'boolean',
        'approval_decision_customer_notif' => 'boolean',
    ];

    // Status constants
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RESERVED = 'reserved';
    const STATUS_CONFIRMED = 'confirmed';
    
    public static $statuses = [
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_RESERVED => 'Reserved',
        self::STATUS_CONFIRMED => 'Confirmed' // FIX BUG-07: typo
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id');
    }

    public function detailReservasi()
    {
        return $this->hasMany(DetailReservasi::class, 'id_reservasi', 'id');
    }

    public function meja()
    {
        // Many-to-many via detail_reservasi (id_meja -> meja.id)
        return $this->belongsToMany(Meja::class, 'detail_reservasi', 'id_reservasi', 'id_meja');
    }

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id');
    }

    public function getStatusPesananAttribute()
    {
        return $this->pesanan ? $this->pesanan->status : null;
    }

    public function getDpBayarAttribute(): float
    {
        return (float) ($this->pesanan?->pembayaran()
            ->where('jenis', 'DP')
            ->where('status', 'Success')
            ->sum('nominal') ?? 0);
    }

    /**
     * Durasi batas waktu pembayaran reservasi (dalam menit).
     */
    public function getPaymentWindowMinutes(): int
    {
        return max(1, (int) config('services.reservation.payment_window_minutes', 60));
    }

    /**
     * Menghitung batas waktu (deadline) pembayaran DP reservasi secara sentral.
     * 
     * Aturan Bisnis:
     * 1. Skala besar (>10 pax) saat status 'Waiting': TIDAK MEMILIKI DEADLINE (menunggu persetujuan admin).
     * 2. Skala besar setelah disetujui: deadline dihitung sejak persetujuan admin / dimulainya pembayaran DP.
     * 3. Skala kecil (<=10 pax): deadline dihitung sejak pembayaran DP dibuat / dicoba ulang.
     */
    public function paymentDeadline(): \Carbon\Carbon
    {
        $order = $this->pesanan;
        $isLargeReservation = (int) $this->jml_org > 10;
        $orderStatus = strtolower(trim((string) ($order?->status ?? '')));

        // Reservasi skala besar yang masih menunggu persetujuan tidak memiliki batas waktu pembayaran
        if ($isLargeReservation && $orderStatus === 'waiting') {
            return now()->addYears(100);
        }

        $payment = $order?->pembayaran()
            ->whereRaw("LOWER(TRIM(jenis)) = 'dp'")
            ->latest('id')
            ->first();

        if ($isLargeReservation) {
            // Untuk skala besar: batas waktu dimulai sejak admin menyetujui reservasi ($order->updated_at).
            // Jika customer sudah menekan 'Mulai Pembayaran DP' atau retry ($payment->midtrans_order_id terisi
            // dan diperbarui setelah persetujuan), gunakan waktu pembayaran dimulai tersebut.
            $approvalTime = $order?->updated_at ?? $this->created_at ?? now();
            $paymentStartedTime = ($payment && $payment->midtrans_order_id) ? $payment->updated_at : null;

            $deadlineStart = ($paymentStartedTime && $paymentStartedTime->gt($approvalTime))
                ? $paymentStartedTime
                : $approvalTime;
        } else {
            // Skala kecil (<=10 pax): dihitung sejak pembayaran DP dibuat atau dicoba ulang (updated_at)
            $deadlineStart = $payment?->updated_at
                ?? $payment?->created_at
                ?? $order?->created_at
                ?? $this->created_at
                ?? now();
        }

        return \Carbon\Carbon::parse($deadlineStart)->addMinutes($this->getPaymentWindowMinutes());
    }
}
