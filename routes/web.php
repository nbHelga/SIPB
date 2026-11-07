<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\admin\OrderController;
use App\Http\Controllers\admin\MenuController;
use App\Http\Controllers\admin\ReservationController as AdminReservationController;
use App\Http\Controllers\admin\TableController;
use App\Http\Controllers\admin\UserController;
use App\Http\Controllers\customer\SelfOrderController;
use App\Http\Controllers\customer\BookingController;
use App\Http\Controllers\customer\PaymentController;
use Illuminate\Support\Facades\Artisan;


// ===================== LANDING PAGE =====================
Route::get('/', [CustomerController::class, 'landing'])->name('home');
Route::post('/reservasi/cek-ketersediaan', [CustomerController::class, 'cekKetersediaan'])->name('reservasi.cek');

// ===================== CUSTOMER (Self Order) =====================
Route::prefix('customer')->name('customer.')->group(function () {
    // Self Order
    Route::get('/selforder', [SelfOrderController::class, 'selfOrder'])
        ->name('selforder');
    Route::get('/menu/{id}/json', [SelfOrderController::class, 'menuJson'])->name('menu.json');
    Route::get('/menu/{id}', [SelfOrderController::class, 'menuDetail'])->name('menu.detail');

    // Cart 
    Route::get('/cart', [SelfOrderController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [SelfOrderController::class, 'cartAdd'])->name('cart.add');
    Route::post('/cart/update', [SelfOrderController::class, 'cartUpdate'])->name('cart.update');
    Route::delete('/cart/remove', [SelfOrderController::class, 'cartRemove'])->name('cart.remove');

    // Checkout
    Route::get('/checkout', [SelfOrderController::class, 'checkout'])->name('checkout');
    Route::post('/payment', [SelfOrderController::class, 'payment'])->name('payment');
    //midtrans
    Route::post('/checkout/create-transaction', [SelfOrderController::class, 'createCheckoutTransaction'])
        ->name('checkout.create');

    Route::get('/order/detail/{kode_pesanan}', [SelfOrderController::class, 'orderDetail'])->name('order.detail');
    Route::get('/order-status/{kode_pesanan}', [SelfOrderController::class, 'orderStatus'])->name('order.status');
    Route::post('/payment/ulangi/{kode_pesanan}', [PaymentController::class, 'ulangiPembayaran'])->name('payment.ulangi');
    Route::post('/payment/batalkan/{kode_pesanan}', [PaymentController::class, 'batalkanPesanan'])->name('payment.batal');
    Route::get('/order/{id}/repay', [SelfOrderController::class, 'repayTransaction'])->name('order.repay');

    // Reservasi
    Route::get('/reservasi', [BookingController::class, 'reservasiForm'])->name('reservasi.form');
    Route::get('/reservation/menu', [BookingController::class, 'reservationMenu'])->name('reservation.menu');
    Route::get('/reservation/menu/{id}', [BookingController::class, 'reservationMenuDetail'])->name('reservation.menu.detail');
    Route::post('/reservasi', [BookingController::class, 'reservasiStore'])->name('reservasi.store');
    Route::get('/reservasi/payment', [BookingController::class, 'reservasiPayment'])->name('reservasi.payment');
    //midtrans
    Route::get('/reservasi/payment/success', [BookingController::class, 'reservasiPaymentSuccess'])
        ->name('reservasi.payment.success');
    Route::post('/reservasi/create-transaction', [BookingController::class, 'createReservationTransaction'])
        ->name('reservasi.create_transaction');

    // Route::post('/reservasi/pay-dp', [BookingController::class, 'reservasiPayDp'])->name('reservasi.pay-dp');
    Route::get('/reservasi/{kode_pesanan}/detail', [BookingController::class, 'reservasiDetail'])->name('reservasi.detail');
    Route::post('/reservasi/{id}/update_menu', [BookingController::class, 'reservasiUpdateMenu'])->name('reservasi.update_menu');
    Route::post('/reservasi/{id}/cancel-pending',[BookingController::class, 'cancelPendingReservation'])->name('reservasi.cancel_pending');
    Route::post('/reservasi/{id}/ulangi-pembayaran',[BookingController::class, 'retryReservationPayment'])->name('reservasi.retry_payment');


    // Route::get('/checkout/midtrans/{orderId}', [CustomerController::class, 'checkoutMidtransView'])
    //     ->name('checkout.midtrans'); // optional
    // Route::post('/payment/callback', [CustomerController::class, 'paymentCallback'])
    //     ->name('payment.callback');

    // Reservasi DP
    // Route::post('/midtrans/notification', [CustomerController::class, 'midtransNotification']); // webhook
    

    // SendReservasiNotifications artisan route (untuk testing via web jika perlu)
    Route::get('/test/send-res-notif', function() {
        Artisan::call('send:reservasi-notifications');
        return 'sent';
    });
});

// ===================== AUTH (Login) =====================
Route::prefix('admin01x')->name('login.')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])
        ->middleware('guest')
        ->name('form'); // tetap menghasilkan login.form
    Route::post('/authenticate', [AuthController::class, 'authenticate'])
        ->middleware('guest')
        ->name('authenticate');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');
});
Route::get('/admin01x', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('login');

// ===================== ADMIN / KASIR (with auth middleware) =====================
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard (hanya admin)
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/sales-data', [AdminController::class, 'salesData'])->name('sales-data');
    Route::get('/dashboard/rekap', [AdminController::class, 'dailyReport'])
        ->name('dashboard.report');
    Route::get('/notifications', [AdminController::class, 'notifications'])
    ->name('notifications');

    // Self Order (Pesanan) — untuk kasir dan admin
    Route::get('/selforder/{id?}', [OrderController::class, 'selfOrder'])->name('selforder');
    Route::post('/order/{id}/update', [OrderController::class, 'orderUpdate'])->name('order.update');
    Route::get('/order/{id}/nota', [OrderController::class, 'nota'])
        ->name('selforder.nota');

    Route::get('/menu/{id?}', [MenuController::class, 'menu'])->name('menu');
    Route::get('/menu/{id}/json', [MenuController::class, 'menuJson'])->name('menu.json');
    Route::patch('/menu/{id}/availability', [MenuController::class, 'menuAvailability'])
    ->name('menu.availability');

    Route::middleware('admin')->group(function () {
        // Menu (hanya admin)
        Route::post('/menu/store', [MenuController::class, 'menuStore'])->name('menu.store');
        Route::put('/menu/{id}/update', [MenuController::class, 'menuUpdate'])->name('menu.update');
        Route::delete('/menu/{id}/delete', [MenuController::class, 'menuDelete'])->name('menu.delete');

        // User (hanya admin)
        Route::get('/user/{id?}', [UserController::class, 'user'])->name('user');
        Route::get('/user/{id}/json', [UserController::class, 'userJson'])->name('user.json');
        Route::post('/user/store', [UserController::class, 'userStore'])->name('user.store');
        Route::put('/user/{id}/update', [UserController::class, 'userUpdate'])->name('user.update');
        Route::delete('/user/{id}/delete', [UserController::class, 'userDelete'])->name('user.delete');
    });

    // Meja
    Route::get('/meja/{id?}', [TableController::class, 'table'])->name('meja');
    // Route::get('/meja/{id}/json', [TableController::class, 'mejaJson'])->name('meja.json');
    Route::post('/meja/store', [TableController::class, 'mejaStore'])->name('meja.store');
    // Route::put('/meja/{id}/update', [TableController::class, 'mejaUpdate'])->name('meja.update');
    Route::post('/meja/{id}/assign-meja', [TableController::class, 'assignMeja'])->name('meja.assign');

    // Reservasi
    Route::get('/reservasi/{id?}', [AdminReservationController::class, 'reservasi'])->name('reservasi');
    Route::post('/reservasi/{id}/update', [AdminReservationController::class, 'reservasiUpdate'])->name('reservasi.update');
    Route::post('/reservasi/{id}/lunas', [AdminReservationController::class, 'lunas'])->name('reservasi.lunas');
    Route::get('/reservasi/{id}/nota', [AdminReservationController::class, 'nota'])
        ->name('reservasi.nota');
});
