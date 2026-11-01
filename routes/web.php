<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\BranchDormController;
use App\Http\Controllers\Owner\RoomController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\Customer\PropertyController;
use App\Http\Controllers\AuthController;

Route::get('/', [HomeController::class, 'index'])
    ->middleware('redirect.authenticated')  
    ->name('customer.home');

Route::get('/kost/{id}', [PropertyController::class, 'show'])
    ->whereNumber('id')
    ->name('customer.properties.show');


Route::middleware('guest')->group(function () {
    Route::get('/register/role', [AuthController::class, 'chooseRole'])
        ->name('register.role');

    Route::get('/register/{role}', [AuthController::class, 'showRegister'])
        ->whereIn('role', ['owner', 'tenant'])
        ->name('register.create');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');

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
    Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
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

    // // TENANT
    // Route::prefix('tenant')
    //     ->name('tenant.')
    //     ->middleware('role:tenant')
    //     ->group(function () {
    //         Route::get('/dashboard', function () {
    //             return view('tenant.dashboard');
    //         })->name('dashboard');

    //         // Tambahkan route booking, sewa, dan pembayaran di sini.
    //     });
});
