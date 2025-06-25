<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    DB::table('users')->insert([
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "Test user created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 