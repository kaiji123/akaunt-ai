<?php

use Modules\AiReceiptReader\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '{company}', 'middleware' => ['auth', 'company']], function () {
    Route::prefix('ai-receipts')->group(function () {
        Route::get('/upload', [ReceiptController::class, 'index']);
        Route::post('/process', [ReceiptController::class, 'process']);
    });
});