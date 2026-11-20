<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\BranchDormController;
use App\Http\Controllers\Owner\RoomController;

use App\Http\Controllers\Guest\HomeController;
use App\Http\Controllers\Guest\PropertyController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;

use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\BookingController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\RenewalController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\MyRoomController;
use App\Http\Controllers\Tenant\RentInformationController;
use App\Http\Controllers\Tenant\PaymentHistoryController;
use App\Http\Controllers\Tenant\ProfileController;

Route::get('/', [HomeController::class, 'index'])
    ->name('guest.home');

Route::view('/untuk-pemilik', 'pages.guest.owner')
    ->name('guest.owner');

Route::get('/kost/{id}', [PropertyController::class, 'show'])
    ->whereNumber('id')
    ->name('guest.properties.show');

Route::get('/rooms/{roomId}/availability', [PropertyController::class, 'availability'])
    ->whereNumber('roomId')
    ->name('guest.rooms.availability');


Route::middleware('guest')->group(function () {
    Route::get('/register/role', [AuthController::class, 'chooseRole'])
        ->name('register.role');

    Route::get('/register/{role}', [AuthController::class, 'showRegister'])
        ->whereIn('role', ['owner', 'tenant'])
        ->name('register.create');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');

    Route::get('/email/verify', [EmailVerificationController::class, 'show'])->name('email.verification.show');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:5,1')->name('email.verification.verify');
    // Cooldown 60 detik ditangani oleh EmailVerificationService.
    // Throttle tetap dipertahankan sebagai lapisan proteksi tambahan, dengan batas
    // lebih longgar agar pengguna menerima pesan cooldown yang ramah terlebih dahulu.
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:5,1')->name('email.verification.send');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');


Route::middleware('auth')->group(function () {

    // OWNER
    Route::prefix('owner')
    ->name('owner.')
    ->middleware('role:owner')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        
        Route::get('/branches/geocode', [BranchDormController::class, 'geocode'])
            ->middleware('throttle:5,1')
            ->name('branches.geocode');

        Route::resource('branches', BranchDormController::class)
            ->parameters(['branches' => 'branch']);

        Route::resource('rooms', RoomController::class)->names('rooms');

        
    });

    // // MANAGER
    // Route::prefix('manager')
    //     ->name('manager.')
    //     ->middleware('role:manager')
    //     ->group(function () {
    //         Route::get('/dashboard', [DashboardController::class, 'index'])
    //             ->name('dashboard');

    //         // Tambahkan route operasional cabang di sini.
    //     });

    // TENANT
    Route::prefix('tenant')
        ->name('tenant.')
        ->middleware('role:tenant')
        ->group(function () {
           Route::get('/dashboard', [TenantDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/kamar-saya', [MyRoomController::class, 'index'])
                ->name('kamar-saya');

            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/notifications/{notificationId}/open', [NotificationController::class, 'open'])->name('notifications.open');
            Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
            
            Route::post('/bookings', [BookingController::class, 'store'])
                ->name('bookings.store');

            Route::get('/rents/{rentId}/renewal', [RenewalController::class, 'create'])
                ->whereNumber('rentId')
                ->name('rents.renewal.create');

            Route::post('/rents/{rentId}/renewal', [RenewalController::class, 'store'])
                ->whereNumber('rentId')
                ->name('rents.renewal.store');

            Route::get('/bookings/{id}', [BookingController::class, 'show'])
                ->whereNumber('id')
                ->name('bookings.show');

            Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel'])
                ->whereNumber('id')
                ->name('bookings.cancel');

            Route::get(
                    '/bookings/{bookingId}/payments',
                    [PaymentController::class, 'create']
                )
                    ->whereNumber('bookingId')
                    ->name('bookings.payments.create');

            Route::post(
                    '/bookings/{bookingId}/payments/method',
                    [PaymentController::class, 'selectMethod']
                )
                    ->whereNumber('bookingId')
                    ->name('bookings.payments.method');

            Route::get(
                    '/bookings/{bookingId}/payments/{paymentId}',
                    [PaymentController::class, 'show']
                )
                    ->whereNumber('bookingId')
                    ->whereNumber('paymentId')
                    ->name('bookings.payments.show');

            Route::post(
                    '/bookings/{bookingId}/payments/{paymentId}/simulate',
                    [PaymentController::class, 'simulate']
                )
                    ->whereNumber('bookingId')
                    ->whereNumber('paymentId')
                    ->name('bookings.payments.simulate');
        
            Route::get('/informasi-sewa', [RentInformationController::class, 'index'])
                ->name('informasi-sewa');
            
            Route::get('/riwayat-pembayaran', [PaymentHistoryController::class, 'index'])
                ->name('riwayat-pembayaran');

            Route::get('/riwayat-pembayaran/{paymentId}', [PaymentHistoryController::class, 'show'])
                ->whereNumber('paymentId')
                ->name('riwayat-pembayaran.show');

            Route::get('/profil', [ProfileController::class, 'index'])->name('profil.index');
            Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
            Route::post('/profil/email/send', [ProfileController::class, 'sendEmailOtp'])
                ->middleware('throttle:5,1')
                ->name('profil.email.send');
            Route::post('/profil/email/verify', [ProfileController::class, 'verifyEmail'])
                ->middleware('throttle:5,1')
                ->name('profil.email.verify');
            Route::delete('/profil/email/cancel', [ProfileController::class, 'cancelEmailChange'])
                ->name('profil.email.cancel');
                Route::put('/profil/password', [ProfileController::class, 'updatePassword'])
            ->middleware('throttle:5,1')
            ->name('profil.password.update');

        });
});
