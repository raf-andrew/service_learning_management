<?php

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestReporter;

abstract class CommandTestCase extends BaseTestCase
{
    use WithoutMiddleware;

    protected $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new TestReporter();
        $this->withoutExceptionHandling();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->reporter->addCodeQualityMetric('memory_usage', memory_get_peak_usage(true));
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    /**
     * Assert that a command can be executed successfully
     *
     * @param string $command
     * @param array $arguments
     * @param array $options
     * @return \Illuminate\Testing\PendingCommand
     */
    protected function assertCommandSucceeds(string $command, array $arguments = [], array $options = [])
    {
        return $this->artisan($command, $arguments, $options)
            ->assertExitCode(0);
    }

    /**
     * Assert that a command fails with expected exit code
     *
     * @param string $command
     * @param int $expectedExitCode
     * @param array $arguments
     * @param array $options
     * @return \Illuminate\Testing\PendingCommand
     */
    protected function assertCommandFails(string $command, int $expectedExitCode = 1, array $arguments = [], array $options = [])
    {
        return $this->artisan($command, $arguments, $options)
            ->assertExitCode($expectedExitCode);
    }

    /**
     * Assert that a command produces expected output
     *
     * @param string $command
     * @param string $expectedOutput
     * @param array $arguments
     * @param array $options
     * @return \Illuminate\Testing\PendingCommand
     */
    protected function assertCommandOutput(string $command, string $expectedOutput, array $arguments = [], array $options = [])
    {
        return $this->artisan($command, $arguments, $options)
            ->expectsOutput($expectedOutput)
            ->assertExitCode(0);
    }

    /**
     * Assert that a command doesn't produce error output
     *
     * @param string $command
     * @param array $arguments
     * @param array $options
     * @return \Illuminate\Testing\PendingCommand
     */
    protected function assertCommandNoErrors(string $command, array $arguments = [], array $options = [])
    {
        return $this->artisan($command, $arguments, $options)
            ->doesntExpectOutput('error')
            ->doesntExpectOutput('Error')
            ->doesntExpectOutput('ERROR')
            ->assertExitCode(0);
    }
} 