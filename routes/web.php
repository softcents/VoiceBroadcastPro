<?php

declare(strict_types=1);

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;

Route::get('auth/login', function () {
    return redirect('login');
})->name('login');

Route::view('/terms', 'terms')->name('terms');

Route::match(['post', 'get'], 'payments/{gateway}/callback/{deposit}', PaymentController::class)
    ->name('payments.callback')
    ->middleware(['auth:customer'])
    ->whereIn('gateway', ['piprapay']);

Route::match(['post', 'get'], 'webhooks/payment/{gateway}', PaymentWebhookController::class)
    ->name('payments.webhook')
    ->whereIn('gateway', ['piprapay']);
