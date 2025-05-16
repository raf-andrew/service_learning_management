<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\TextUI\Command;

// Set up error and failure logging
$errorLog = __DIR__ . '/../../.errors';
$failureLog = __DIR__ . '/../../.failures';

// Configure PHPUnit
$command = new Command();
$command->run([
    '--configuration' => __DIR__ . '/../../phpunit.xml',
    '--test-suffix' => 'Test.php',
    '--testdox',
    '--colors=always',
    '--log-junit' => __DIR__ . '/../../build/logs/junit.xml',
    '--coverage-clover' => __DIR__ . '/../../build/logs/clover.xml',
    '--coverage-html' => __DIR__ . '/../../build/coverage',
    '--coverage-text',
    '--bootstrap' => __DIR__ . '/../../vendor/autoload.php',
    '--testsuite' => 'MCP\\Infrastructure\\DeploymentPipelineTest',
], false);

// Handle test results
if ($command->getExitCode() !== 0) {
    $output = ob_get_clean();
    file_put_contents($failureLog, date('Y-m-d H:i:s') . "\n" . $output . "\n", FILE_APPEND);
    exit(1);
}

// Log any PHP errors
$errors = error_get_last();
if ($errors !== null) {
    file_put_contents($errorLog, date('Y-m-d H:i:s') . "\n" . print_r($errors, true) . "\n", FILE_APPEND);
    exit(1);
}

exit(0); 