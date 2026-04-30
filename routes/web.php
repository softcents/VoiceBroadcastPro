<?php

declare(strict_types=1);

use App\Http\Controllers\Payment\PipraPayController;
use App\Http\Controllers\Webhook\AsteriskController;
use App\Livewire\Payments\Cancel;
use App\Livewire\Payments\Failed;
use App\Livewire\Payments\Success;

Route::get('auth/login', function () {
    return redirect('login');
})->name('login');

Route::view('/terms', 'terms')->name('terms');

Route::group(['prefix' => 'payments', 'as' => 'payments.'], function () {
    Route::get('success', Success::class)->name('success');
    Route::get('cancel', Cancel::class)->name('cancel');
    Route::get('failed', Failed::class)->name('failed');

    Route::group(['prefix' => 'pipra-pay', 'as' => 'piprapay.'], function () {
        Route::get('callback/{deposit}', [PipraPayController::class, 'callback'])->name('callback');
    });
});

Route::group(['prefix' => 'webhooks', 'as' => 'webhooks.'], function () {
    Route::post('asterisk', AsteriskController::class)->name('asterisk');
    Route::post('pipra-pay/{deposit}', [PipraPayController::class, 'ipn'])->name('piprapay');
});
