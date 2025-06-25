<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\SniffingResult;

class SniffTest extends TestCase
{
    public function test_sniff_command()
    {
        // Run the sniff command
        $exitCode = $this->artisan('sniff:run', [
            '--file' => 'app/Http/Controllers/HomeController.php',
            '--report' => 'xml',
        ]);

        $this->assertEquals(0, $exitCode);

        // Check if results were stored
        $result = SniffingResult::latest()->first();
        $this->assertNotNull($result);
        $this->assertNotEmpty($result->result_data);
        $this->assertEquals('xml', $result->report_format);
        $this->assertGreaterThanOrEqual(0, $result->error_count);
        $this->assertGreaterThanOrEqual(0, $result->warning_count);
    }

    public function test_generate_report()
    {
        // Run the sniff command first to generate some data
        $this->artisan('sniff:run', [
            '--file' => 'app/Http/Controllers/HomeController.php',
            '--report' => 'xml',
        ]);

        // Generate report
        $exitCode = $this->artisan('sniff:generate-report', [
            '--format' => 'html',
            '--output' => 'storage/sniffing/report.html',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertFileExists('storage/sniffing/report.html');
    }

    public function test_clear_data()
    {
        // Run the sniff command first to generate some data
        $this->artisan('sniff:run', [
            '--file' => 'app/Http/Controllers/HomeController.php',
            '--report' => 'xml',
        ]);

        // Verify data exists
        $this->assertGreaterThan(0, SniffingResult::count());

        // Clear all data
        $exitCode = $this->artisan('sniff:clear-data', [
            '--all' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals(0, SniffingResult::count());
    }
}
