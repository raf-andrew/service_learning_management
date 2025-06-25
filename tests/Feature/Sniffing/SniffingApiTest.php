<?php

namespace Tests\Feature\Sniffing;

use Tests\TestCase;
use App\Models\User;
use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;

class SniffingApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and generate token
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Create test files
        $this->createTestFiles();
    }

    /**
     * Test running sniffing analysis
     */
    public function test_can_run_sniffing_analysis(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sniffing/run', [
            'files' => ['test1.php', 'test2.php'],
            'report_format' => 'json',
            'severity' => 'error',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'output',
            ]);
    }

    /**
     * Test getting sniffing results
     */
    public function test_can_get_sniffing_results(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/sniffing/results', [
            'file' => 'test1.php',
            'days' => 7,
            'type' => 'error',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'results',
                'total',
            ]);
    }

    /**
     * Test generating analysis report
     */
    public function test_can_generate_analysis_report(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sniffing/analyze', [
            'days' => 7,
            'format' => 'html',
            'output' => 'storage/app/sniffing/reports/test-report.html',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'report_url',
            ]);

        $this->assertTrue(Storage::exists('sniffing/reports/test-report.html'));
    }

    /**
     * Test managing sniffing rules
     */
    public function test_can_manage_sniffing_rules(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sniffing/rules', [
            'action' => 'add',
            'type' => 'security',
            'name' => 'Test Rule',
            'description' => 'Test rule description',
            'code' => 'test_rule',
            'severity' => 'error',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'output',
            ]);
    }

    /**
     * Test clearing sniffing data
     */
    public function test_can_clear_sniffing_data(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sniffing/clear', [
            'file' => 'test1.php',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'output',
            ]);
    }

    /**
     * Test input validation
     */
    public function test_validates_input(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/sniffing/run', [
            'files' => ['nonexistent.php'],
            'report_format' => 'invalid',
            'severity' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'messages',
            ]);
    }

    /**
     * Test rate limiting
     */
    public function test_rate_limiting(): void
    {
        for ($i = 0; $i < 61; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson('/api/sniffing/run', [
                'files' => ['test1.php'],
                'report_format' => 'json',
                'severity' => 'error',
            ]);
        }

        $response->assertStatus(429);
    }

    /**
     * Create test files
     */
    protected function createTestFiles(): void
    {
        $files = [
            'test1.php' => '<?php echo "test1";',
            'test2.php' => '<?php echo "test2";',
        ];

        foreach ($files as $path => $content) {
            File::create([
                'path' => $path,
                'content' => $content,
            ]);
        }
    }
} 