<?php

use App\Http\Controllers\Connect\Admin\ConnectBusinessController;
use App\Http\Controllers\Connect\Business\ConnectStatusController;
use App\Http\Controllers\Connect\Webhooks\TwilioCallController;
use App\Http\Controllers\Connect\Webhooks\TwilioCallStatusController;
use App\Http\Controllers\Connect\Webhooks\TwilioSmsController;
use App\Http\Middleware\ValidateTwilioSignature;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;

/*
|--------------------------------------------------------------------------
| Frequenters Connect routes
|--------------------------------------------------------------------------
| Loaded by App\Providers\ConnectServiceProvider.
|
| IMPORTANT: the webhook routes must be EXEMPT from CSRF protection because
| Twilio cannot send a CSRF token. They are protected instead by the
| ValidateTwilioSignature middleware. See README for the CSRF exemption.
*/


// ---- Twilio webhooks (public, signature-verified) -----------------------
Route::middleware([ValidateTwilioSignature::class])
    ->prefix('webhooks/frequenters-connect/twilio')
    ->name('connect.webhooks.')
    ->group(function () {
        Route::post('call', TwilioCallController::class)->name('call');
        Route::post('sms', TwilioSmsController::class)->name('sms');
        Route::post('call-status', TwilioCallStatusController::class)->name('call-status');
    });

// ---- Admin (protect with YOUR admin auth middleware) --------------------
Route::middleware(['web', 'auth', RoleMiddleware::class . ':admin'])
    ->prefix('admin/connect')
    ->name('connect.admin.')
    ->group(function () {
        Route::get('businesses', [ConnectBusinessController::class, 'index'])->name('index');
        Route::get('businesses/create', [ConnectBusinessController::class, 'create'])->name('create');
        Route::post('businesses', [ConnectBusinessController::class, 'store'])->name('store');
        Route::get('businesses/{business}/edit', [ConnectBusinessController::class, 'edit'])->name('edit');
        Route::put('businesses/{business}', [ConnectBusinessController::class, 'update'])->name('update');
        Route::get('businesses/{business}/logs', [ConnectBusinessController::class, 'logs'])->name('logs');
        Route::post('businesses/{business}/test-sms', [ConnectBusinessController::class, 'testSms'])->name('test-sms');
        Route::post('businesses/{business}/toggle-pause', [ConnectBusinessController::class, 'togglePause'])->name('toggle-pause');
    });

// ---- Business-facing status page ----------------------------------------
// Route::middleware(['web', 'auth'])
//     ->get('business/connect', [ConnectStatusController::class, 'show'])
//     ->name('connect.business.status');
