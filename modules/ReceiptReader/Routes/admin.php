<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'receipt-reader' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('receipt-reader', function () {
    Route::get('/', 'Main@index');
});
