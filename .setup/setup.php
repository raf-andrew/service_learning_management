#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Setup\Setup;

// Parse command line options
$options = getopt('', [
    'config:',
    'log:',
    'log-level:',
    'no-console',
    'help'
]);

// Show help if requested
if (isset($options['help'])) {
    echo "Usage: php setup.php [options]\n\n";
    echo "Options:\n";
    echo "  --config=<file>     Path to configuration file\n";
    echo "  --log=<file>        Path to log file\n";
    echo "  --log-level=<level> Log level (debug, info, warning, error, critical)\n";
    echo "  --no-console        Disable console output\n";
    echo "  --help              Show this help message\n";
    exit(0);
}

// Prepare setup options
$setupOptions = [
    'config_file' => $options['config'] ?? null,
    'log_file' => $options['log'] ?? null,
    'log_level' => $options['log-level'] ?? 'info',
    'console_output' => !isset($options['no-console'])
];

// Create and run setup
try {
    $setup = new Setup($setupOptions);
    $setup->run();
    exit(0);
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    exit(1);
} 