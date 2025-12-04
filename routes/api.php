<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AudioController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\PhonebookController;
use App\Http\Controllers\Api\SenderIdController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::prefix('account')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::put('/', [AccountController::class, 'update']);
        Route::put('/password', [AccountController::class, 'changePassword']);

        Route::get('/balance', [BalanceController::class, 'show']);
    });

    Route::apiResource('audio', AudioController::class);
    Route::apiResource('phonebooks', PhonebookController::class);
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('templates', TemplateController::class);
    Route::apiResource('sender-ids', SenderIdController::class)->only('index');
    Route::apiResource('campaigns', CampaignController::class);

    Route::post('deposits/{deposit}/verify', [DepositController::class, 'verify'])->name('deposits.verify');
    Route::apiResource('deposits', DepositController::class)->only(['index', 'store', 'show']);
});
