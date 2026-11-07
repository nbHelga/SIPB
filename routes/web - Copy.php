<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Artisan;


// ===================== LANDING PAGE =====================
Route::get('/', [CustomerController::class, 'landing'])->name('home');
Route::post('/reservasi/cek-ketersediaan', [CustomerController::class, 'cekKetersediaan'])->name('reservasi.cek');

// ===================== CUSTOMER (Self Order) =====================
Route::prefix('customer')->name('customer.')->group(function () {
    // Self Order
    Route::get('/selforder', [CustomerController::class, 'selfOrder'])->name('selforder');
    Route::get('/menu/{id}/json', [CustomerController::class, 'menuJson'])->name('menu.json');
    Route::get('/menu/{id}', [CustomerController::class, 'menuDetail'])->name('menu.detail');

    // Cart 
    Route::get('/cart', [CustomerController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [CustomerController::class, 'cartAdd'])->name('cart.add');
    Route::post('/cart/update', [CustomerController::class, 'cartUpdate'])->name('cart.update');
    Route::delete('/cart/remove', [CustomerController::class, 'cartRemove'])->name('cart.remove');

    // Checkout
    Route::get('/checkout', [CustomerController::class, 'checkout'])->name('checkout');
    Route::post('/payment', [CustomerController::class, 'payment'])->name('payment');
    Route::get('/order/success/{id}', [CustomerController::class, 'orderSuccess'])->name('order.success');

    // Reservasi
    Route::get('/reservasi', [CustomerController::class, 'reservasiForm'])->name('reservasi.form');
    Route::get('/reservation/menu', [CustomerController::class, 'reservationMenu'])->name('reservation.menu');
    Route::get('/reservation/menu/{id}', [CustomerController::class, 'reservationMenuDetail'])->name('reservation.menu.detail');
    Route::post('/reservasi', [CustomerController::class, 'reservasiStore'])->name('reservasi.store');
    Route::get('/reservasi/payment', [CustomerController::class, 'reservasiPayment'])->name('reservasi.payment');
    Route::post('/reservasi/pay-dp', [CustomerController::class, 'reservasiPayDp'])->name('reservasi.pay-dp');
    Route::get('/reservasi/{id}/detail', [CustomerController::class, 'reservasiDetail'])->name('reservasi.detail');
    Route::post('/reservasi/{id}/update_menu', [CustomerController::class, 'reservasiUpdateMenu'])->name('reservasi.update_menu');

    // Midtrans
    Route::get('/checkout/create-transaction', [CustomerController::class, 'createCheckoutTransaction'])
        ->name('checkout.create');
    Route::get('/checkout/midtrans/{orderId}', [CustomerController::class, 'checkoutMidtransView'])
        ->name('checkout.midtrans'); // optional
    Route::post('/payment/callback', [CustomerController::class, 'paymentCallback'])
        ->name('payment.callback');

    // Reservasi DP
    Route::post('/reservasi/create-transaction', [CustomerController::class, 'createReservationTransaction'])
        ->name('reservasi.create_transaction');
    Route::post('/midtrans/notification', [CustomerController::class, 'midtransNotification']); // webhook
    Route::get('/reservasi/payment/success', [CustomerController::class, 'reservasiPaymentSuccess'])
    ->name('reservasi.payment.success');

    // SendReservasiNotifications artisan route (untuk testing via web jika perlu)
    Route::get('/test/send-res-notif', function() {
        Artisan::call('send:reservasi-notifications');
        return 'sent';
    });
});

// ===================== AUTH (Login) =====================
Route::prefix('admin01x')->name('login.')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('form');
    Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('authenticate');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ===================== ADMIN / KASIR (with auth middleware) =====================
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard (hanya admin)
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/sales-data', [AdminController::class, 'salesData'])->name('sales-data');

    // Self Order (Pesanan) — untuk kasir dan admin
    Route::get('/selforder/{id?}', [AdminController::class, 'selfOrder'])->name('selforder');
    Route::post('/order/{id}/update', [AdminController::class, 'orderUpdate'])->name('order.update');
    Route::get('/order/{id}/nota', [AdminController::class, 'orderNota'])->name('order.nota');

    // Menu (hanya admin)
    Route::get('/menu/{id?}', [AdminController::class, 'menu'])->name('menu');
    Route::get('/menu/{id}/json', [AdminController::class, 'menuJson'])->name('menu.json');
    Route::post('/menu/store', [AdminController::class, 'menuStore'])->name('menu.store');
    Route::put('/menu/{id}/update', [AdminController::class, 'menuUpdate'])->name('menu.update');
    Route::delete('/menu/{id}/delete', [AdminController::class, 'menuDelete'])->name('menu.delete');

    // User (hanya admin)
    Route::get('/user/{id?}', [AdminController::class, 'user'])->name('user');
    Route::get('/user/{id}/json', [AdminController::class, 'userJson'])->name('user.json');
    Route::post('/user/store', [AdminController::class, 'userStore'])->name('user.store');
    Route::put('/user/{id}/update', [AdminController::class, 'userUpdate'])->name('user.update');
    Route::delete('/user/{id}/delete', [AdminController::class, 'userDelete'])->name('user.delete');

    // Meja
    Route::get('/meja/{id?}', [AdminController::class, 'meja'])->name('meja');
    Route::get('/meja/{id}/json', [AdminController::class, 'mejaJson'])->name('meja.json');
    Route::post('/meja/store', [AdminController::class, 'mejaStore'])->name('meja.store');
    Route::put('/meja/{id}/update', [AdminController::class, 'mejaUpdate'])->name('meja.update');
    Route::post('/meja/{id}/assign-meja', [AdminController::class, 'assignMeja'])->name('meja.assign');

    // Reservasi
    Route::get('/reservasi/{id?}', [AdminController::class, 'reservasi'])->name('reservasi');
    Route::post('/reservasi/{id}/update', [AdminController::class, 'reservasiUpdate'])->name('reservasi.update');
});
