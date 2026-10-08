<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Autentikasi (berbasis Laravel Breeze, stack Blade)
|--------------------------------------------------------------------------
| Aplikasi hanya memiliki satu jenis pengguna (Admin / Petugas Logistik),
| sehingga registrasi publik, verifikasi email, dan reset kata sandi via
| email sengaja tidak disediakan. Akun dibuat oleh AdminSeeder.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('lupa-sandi', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('lupa-sandi', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.recovery');
});

Route::middleware('auth')->group(function () {
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
