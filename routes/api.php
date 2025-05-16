// Codespace Management Routes
Route::prefix('codespaces')->middleware(['auth:api', 'developer.credentials'])->group(function () {
    Route::get('/', [CodespaceController::class, 'index']);
    Route::post('/', [CodespaceController::class, 'create']);
    Route::delete('/{name}', [CodespaceController::class, 'delete']);
    Route::post('/{name}/rebuild', [CodespaceController::class, 'rebuild']);
    Route::get('/{name}/status', [CodespaceController::class, 'status']);
    Route::post('/{name}/connect', [CodespaceController::class, 'connect']);
});

// Developer Credentials Routes
Route::prefix('developer-credentials')->middleware(['auth:api'])->group(function () {
    Route::get('/', [DeveloperCredentialController::class, 'index']);
    Route::post('/', [DeveloperCredentialController::class, 'store']);
    Route::put('/{id}', [DeveloperCredentialController::class, 'update']);
    Route::delete('/{id}', [DeveloperCredentialController::class, 'destroy']);
    Route::post('/{id}/activate', [DeveloperCredentialController::class, 'activate']);
    Route::post('/{id}/deactivate', [DeveloperCredentialController::class, 'deactivate']);
}); 