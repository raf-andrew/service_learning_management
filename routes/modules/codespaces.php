<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'codespaces', 'middleware' => ['auth']], function () {
    Route::get('/', 'CodespaceController@index')->name('codespaces.index');
    Route::get('/create', 'CodespaceController@create')->name('codespaces.create');
    Route::post('/', 'CodespaceController@store')->name('codespaces.store');
    Route::get('/{codespace}', 'CodespaceController@show')->name('codespaces.show');
    Route::delete('/{codespace}', 'CodespaceController@destroy')->name('codespaces.destroy');
    Route::post('/{codespace}/rebuild', 'CodespaceController@rebuild')->name('codespaces.rebuild');
    Route::get('/{codespace}/status', 'CodespaceController@status')->name('codespaces.status');
});

Route::group(['prefix' => 'api/codespaces', 'middleware' => ['auth:sanctum']], function () {
    Route::get('/', 'Api\CodespaceController@index');
    Route::post('/', 'Api\CodespaceController@store');
    Route::get('/{codespace}', 'Api\CodespaceController@show');
    Route::delete('/{codespace}', 'Api\CodespaceController@destroy');
    Route::post('/{codespace}/rebuild', 'Api\CodespaceController@rebuild');
    Route::get('/{codespace}/status', 'Api\CodespaceController@status');
});