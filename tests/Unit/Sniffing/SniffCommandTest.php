<?php

namespace Tests\Unit\Sniffing;

use Tests\TestCase;
use App\Console\Commands\SniffCommand;
use App\Repositories\Sniffing\SniffResultRepository;
use Illuminate\Support\Facades\File;
use Mockery;

class SniffCommandTest extends TestCase
{
    private $repository;
    private $command;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = Mockery::mock(SniffResultRepository::class);
        $this->command = new SniffCommand($this->repository);
    }

    public function test_command_validates_report_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid report format: invalid');

        $this->artisan('sniff:run', ['--report' => 'invalid']);
    }

    public function test_command_validates_severity_level()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid severity level: invalid');

        $this->artisan('sniff:run', ['--severity' => 'invalid']);
    }

    public function test_command_validates_file_exists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found: nonexistent.php');

        $this->artisan('sniff:run', ['--file' => 'nonexistent.php']);
    }

    public function test_command_stores_results()
    {
        $this->repository->shouldReceive('store')
            ->once()
            ->with(Mockery::on(function ($data) {
                return is_array($data) &&
                    isset($data['report_format']) &&
                    isset($data['file_path']) &&
                    isset($data['sniff_date']);
            }))
            ->andReturn(new \App\Models\Sniffing\SniffResult());

        $this->artisan('sniff:run', [
            '--report' => 'xml',
            '--file' => 'app/Console/Commands/SniffCommand.php'
        ]);
    }

    public function test_command_handles_errors()
    {
        $this->repository->shouldReceive('store')
            ->andThrow(new \Exception('Test error'));

        $this->artisan('sniff:run', ['--report' => 'xml'])
            ->expectsOutput('Error running code sniffer: Test error');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 