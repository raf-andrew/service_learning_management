<?php

use Illuminate\Support\Facades\Route;

Route::prefix('e2ee')->group(function () {
    Route::get('/status', function () {
        return response()->json(['status' => 'ok']);
    });
}); 