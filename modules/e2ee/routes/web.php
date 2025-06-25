<?php

use Illuminate\Support\Facades\Route;

Route::get('/e2ee', function () {
    return view('e2ee::welcome');
}); 