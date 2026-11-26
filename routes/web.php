<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\BookingController as OwnerBookingController;
use App\Http\Controllers\Owner\BranchDormController;
use App\Http\Controllers\Owner\RoomController;
use App\Http\Controllers\Owner\TransactionController;
use App\Http\Controllers\Owner\HousemaidController;
use App\Http\Controllers\Owner\TenantController;
use App\Http\Controllers\Owner\FacilityController;
use App\Http\Controllers\Owner\NotificationController as OwnerNotificationController;

use App\Http\Controllers\Guest\HomeController;
use App\Http\Controllers\Guest\PropertyController;
use App\Http\Controllers\Guest\RoomDetailController;

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

Route::get('/kost/{property}', [PropertyController::class, 'show'])
    ->where('property', '[A-Za-z0-9-]+')
    ->name('guest.properties.show');

Route::get('/kamar/{roomCode}', [RoomDetailController::class, 'show'])
    ->where('roomCode', '[A-Za-z0-9-]+')
    ->name('guest.rooms.show');

Route::get('/rooms/{roomCode}/availability', [PropertyController::class, 'availability'])
    ->where('roomCode', '[A-Za-z0-9-]+')
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

        Route::get('/notifications', [OwnerNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notificationId}/open', [OwnerNotificationController::class, 'open'])->name('notifications.open');
        Route::patch('/notifications/{notificationId}/read', [OwnerNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::patch('/notifications/read-all', [OwnerNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        
        Route::get('/branches/geocode', [BranchDormController::class, 'geocode'])
            ->middleware('throttle:5,1')
            ->name('branches.geocode');

        Route::resource('branches', BranchDormController::class)
            ->parameters(['branches' => 'branch']);

        Route::resource('fasilitas', FacilityController::class)->parameters(['fasilitas' => 'facility'])->except(['create', 'show', 'edit'])->names('facilities');

        Route::get('/penyewa', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/penyewa/{publicId}', [TenantController::class, 'show'])->whereUuid('publicId')->name('tenants.show');

        Route::resource('rooms', RoomController::class)->names('rooms');

        Route::patch('/housemaids/{housemaid}/status', [HousemaidController::class, 'updateStatus'])
            ->whereUuid('housemaid')
            ->name('housemaids.status');
        Route::resource('housemaids', HousemaidController::class)->parameters(['housemaids' => 'housemaid'])->names('housemaids');
        Route::patch('/rooms/{room}/status', [RoomController::class, 'updateStatus'])
            ->where('room', '[A-Za-z0-9-]+')
            ->name('rooms.update-status');

        Route::get('/bookings', [OwnerBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{bookingCode}', [OwnerBookingController::class, 'show'])->where('bookingCode', '[A-Za-z0-9-]+')->name('bookings.show');

        Route::get('/rents', [TransactionController::class, 'rents'])->name('rents');
        Route::get('/payments', [TransactionController::class, 'payments'])->name('payments');

        
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

            Route::get('/rents/{rentCode}/renewal', [RenewalController::class, 'create'])
                ->name('rents.renewal.create');

            Route::post('/rents/{rentCode}/renewal', [RenewalController::class, 'store'])
                ->name('rents.renewal.store');

            Route::get('/bookings/{bookingCode}', [BookingController::class, 'show'])
                ->name('bookings.show');

            Route::post('/bookings/{bookingCode}/cancel', [BookingController::class, 'cancel'])
                ->name('bookings.cancel');

            Route::get('/bookings/{bookingCode}/payments', [PaymentController::class, 'create'])
                ->name('bookings.payments.create');

            Route::post('/bookings/{bookingCode}/payments/method', [PaymentController::class, 'selectMethod'])
                ->name('bookings.payments.method');

            Route::get('/bookings/{bookingCode}/payments/{paymentCode}', [PaymentController::class, 'show'])
                ->name('bookings.payments.show');

            Route::post('/bookings/{bookingCode}/payments/{paymentCode}/simulate', [PaymentController::class, 'simulate'])
                ->name('bookings.payments.simulate');

            Route::get('/informasi-sewa', [RentInformationController::class, 'index'])
                ->name('informasi-sewa');
            
            Route::get('/riwayat-pembayaran', [PaymentHistoryController::class, 'index'])
                ->name('riwayat-pembayaran');

            Route::get('/riwayat-pembayaran/{paymentCode}', [PaymentHistoryController::class, 'show'])
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
