<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AudioRecordController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PhonebookController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::prefix('account')->group(function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::get('/balance', [AccountController::class, 'balance']);
        Route::put('/', [AccountController::class, 'update']);
        Route::put('/password', [AccountController::class, 'changePassword']);
    });

    Route::apiResource('audio-records', AudioRecordController::class);
    Route::apiResource('phonebooks', PhonebookController::class);
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('templates', TemplateController::class);
});
