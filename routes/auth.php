<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'no_cache'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.store');
});

Route::middleware(['auth', 'no_cache'])->group(function () {
    Route::middleware('active')->group(function () {
        Route::get('session/status', [\App\Http\Controllers\Auth\IdleSessionController::class, 'status'])->name('session.status');
        Route::post('session/keep-alive', [\App\Http\Controllers\Auth\IdleSessionController::class, 'keepAlive'])->middleware('throttle:10,1')->name('session.keep_alive');
    });
    Route::middleware('active')->group(function () {
    Route::get('security/change-password', [\App\Http\Controllers\Auth\AccountSecurityController::class, 'password'])->name('security.password');
    Route::post('security/change-password', [\App\Http\Controllers\Auth\AccountSecurityController::class, 'changePassword'])->middleware('throttle:6,1')->name('security.password.update');
    Route::get('security/setup', [\App\Http\Controllers\Auth\AccountSecurityController::class, 'setup'])->name('security.setup');
    Route::post('security/setup', [\App\Http\Controllers\Auth\AccountSecurityController::class, 'enroll'])->middleware('throttle:6,1')->name('security.enroll');
    Route::get('security/challenge', [\App\Http\Controllers\Auth\AccountSecurityController::class, 'challenge'])->name('security.challenge');
    Route::post('security/challenge', [\App\Http\Controllers\Auth\AccountSecurityController::class, 'verify'])->middleware('throttle:6,1')->name('security.verify');
    Route::get('security/recovery', [\App\Http\Controllers\Auth\AccountSecurityController::class, 'recovery'])->name('security.recovery');
    });
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
