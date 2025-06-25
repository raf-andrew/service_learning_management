<?php

namespace App\Http\Controllers\GitHub;

use Illuminate\Http\Request;

class RepositoryController
{
    // Stub controller to unblock php artisan route:list
    public function index(Request $request)
    {
        return response()->json(['message' => 'Stub RepositoryController']);
    }
}
