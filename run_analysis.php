<?php

// Set up environment variables
require 'setup_env.php';

// Bootstrap Laravel
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

// Run the analysis
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->call('infrastructure:analyze', ['--detailed' => true]);

echo "Analysis completed with status: $status\n"; 