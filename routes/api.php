<?php

use App\Http\Controllers\Api\AudioRecordController;
use App\Http\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::prefix('account')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::get('/balance', [AccountController::class, 'balance']);
        Route::put('/', [AccountController::class, 'update']);
        Route::put('/password', [AccountController::class, 'changePassword']);
    });

    Route::apiResource('audio-records', AudioRecordController::class);
});
