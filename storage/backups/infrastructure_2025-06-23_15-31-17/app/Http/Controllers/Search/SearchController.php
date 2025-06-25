<?php

namespace App\Http\Controllers\Search;

use Illuminate\Http\Request;

class SearchController
{
    // Stub controller to unblock php artisan route:list
    public function index(Request $request)
    {
        return response()->json(['message' => 'Stub SearchController']);
    }
}
