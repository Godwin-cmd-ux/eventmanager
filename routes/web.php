<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ClerkStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.session')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store']);
});

Route::middleware('supabase.user')->group(function () {
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');

    Route::get('status', [ClerkStatusController::class, 'show'])
        ->name('clerk.status');

    Route::middleware('clerk.active')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('scan', [ScanController::class, 'create'])
            ->name('scan');

        Route::post('scan', [ScanController::class, 'verify']);
    });
});
