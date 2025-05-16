<?php

namespace Tests\Feature\Analysis;

use App\Analysis\CodeQualityAnalyzer;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Analysis/CodeQualityAnalyzerTest.php
 * @api-docs docs/api/analysis.yaml
 * @security-review docs/security/analysis.md
 * @qa-status Complete
 * @job-code ANA-002-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Analysis
 * 
 * Test suite for CodeQualityAnalyzer class.
 * Validates code quality analysis functionality.
 * 
 * @OpenAPI\Tag(name="Analysis", description="Code quality analysis tests")
 * @OpenAPI\Schema(
 *     type="object",
 *     required={"test_class"},
 *     properties={
 *         @OpenAPI\Property(property="test_class", type="string", format="class"),
 *         @OpenAPI\Property(property="test_methods", type="array", items=@OpenAPI\Items(type="string"))
 *     }
 * )
 */
class CodeQualityAnalyzerTest extends TestCase
{
    /**
     * The test class to analyze.
     *
     * @var string
     */
    protected string $testClass = 'Tests\Feature\Analysis\TestClass';

    /**
     * The test directory path.
     *
     * @var string
     */
    protected string $testDir;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = storage_path('framework/testing/analysis');
        $this->createTestClass();
    }

    /**
     * Clean up the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        File::deleteDirectory($this->testDir);
        parent::tearDown();
    }

    /**
     * Test that the analyzer generates valid analysis results.
     *
     * @return void
     */
    public function test_it_generates_valid_analysis_results(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('timestamp', $results);
        $this->assertArrayHasKey('target_path', $results);
        $this->assertArrayHasKey('classes', $results);
        $this->assertArrayHasKey('metrics', $results);
        $this->assertArrayHasKey('suggestions', $results);
    }

    /**
     * Test that the analyzer correctly identifies class metrics.
     *
     * @return void
     */
    public function test_it_identifies_class_metrics(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();
        $class = $results['classes'][0];

        $this->assertEquals($this->testClass, $class['name']);
        $this->assertArrayHasKey('metrics', $class);
        $this->assertArrayHasKey('methods', $class);
        $this->assertArrayHasKey('properties', $class);
        $this->assertArrayHasKey('docblock', $class);
    }

    /**
     * Test that the analyzer calculates correct complexity metrics.
     *
     * @return void
     */
    public function test_it_calculates_complexity_metrics(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();
        $metrics = $results['metrics'];

        $this->assertIsInt($metrics['total_classes']);
        $this->assertIsInt($metrics['total_methods']);
        $this->assertIsInt($metrics['total_properties']);
        $this->assertIsInt($metrics['total_lines_of_code']);
        $this->assertIsFloat($metrics['average_complexity']);
        $this->assertIsFloat($metrics['average_methods_per_class']);
        $this->assertIsFloat($metrics['average_properties_per_class']);
        $this->assertIsFloat($metrics['average_lines_per_class']);
    }

    /**
     * Test that the analyzer generates valid suggestions.
     *
     * @return void
     */
    public function test_it_generates_valid_suggestions(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();
        $suggestions = $results['suggestions'];

        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey($this->testClass, $suggestions);
        
        foreach ($suggestions[$this->testClass] as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('message', $suggestion);
            $this->assertArrayHasKey('severity', $suggestion);
            $this->assertContains($suggestion['severity'], ['info', 'warning', 'error']);
        }
    }

    /**
     * Test that the analyzer handles empty directories.
     *
     * @return void
     */
    public function test_it_handles_empty_directories(): void
    {
        File::deleteDirectory($this->testDir);
        File::makeDirectory($this->testDir);

        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();

        $this->assertEmpty($results['classes']);
        $this->assertEquals(0, $results['metrics']['total_classes']);
    }

    /**
     * Test that the analyzer handles invalid PHP files.
     *
     * @return void
     */
    public function test_it_handles_invalid_php_files(): void
    {
        File::put($this->testDir . '/invalid.php', '<?php invalid code');

        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();

        $this->assertEmpty($results['classes']);
    }

    /**
     * Test that the analyzer correctly identifies method metrics.
     *
     * @return void
     */
    public function test_it_identifies_method_metrics(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();
        $methods = $results['classes'][0]['methods'];

        foreach ($methods as $method) {
            $this->assertArrayHasKey('name', $method);
            $this->assertArrayHasKey('visibility', $method);
            $this->assertArrayHasKey('parameters', $method);
            $this->assertArrayHasKey('return_type', $method);
            $this->assertArrayHasKey('docblock', $method);
            $this->assertArrayHasKey('complexity', $method);
            $this->assertArrayHasKey('lines_of_code', $method);
        }
    }

    /**
     * Test that the analyzer correctly identifies property metrics.
     *
     * @return void
     */
    public function test_it_identifies_property_metrics(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();
        $properties = $results['classes'][0]['properties'];

        foreach ($properties as $property) {
            $this->assertArrayHasKey('name', $property);
            $this->assertArrayHasKey('visibility', $property);
            $this->assertArrayHasKey('type', $property);
            $this->assertArrayHasKey('docblock', $property);
            $this->assertArrayHasKey('has_default', $property);
        }
    }

    /**
     * Test that the analyzer correctly identifies docblock metrics.
     *
     * @return void
     */
    public function test_it_identifies_docblock_metrics(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $results = $analyzer->analyze();
        $docblock = $results['classes'][0]['docblock'];

        $this->assertArrayHasKey('has_docblock', $docblock);
        $this->assertArrayHasKey('has_description', $docblock);
        $this->assertArrayHasKey('has_params', $docblock);
        $this->assertArrayHasKey('has_return', $docblock);
        $this->assertArrayHasKey('has_throws', $docblock);
        $this->assertArrayHasKey('has_see', $docblock);
        $this->assertArrayHasKey('has_since', $docblock);
        $this->assertArrayHasKey('has_author', $docblock);
    }

    /**
     * Create a test class for analysis.
     *
     * @return void
     */
    protected function createTestClass(): void
    {
        File::makeDirectory($this->testDir, 0755, true);

        $content = <<<'PHP'
<?php

namespace Tests\Feature\Analysis;

/**
 * Test class for code quality analysis.
 *
 * @package Tests\Feature\Analysis
 */
class TestClass
{
    /**
     * Test property.
     *
     * @var string
     */
    protected string $testProperty = 'test';

    /**
     * Test method.
     *
     * @param string $param
     * @return string
     * @throws \Exception
     */
    public function testMethod(string $param): string
    {
        if ($param === 'test') {
            return 'test';
        }

        return 'other';
    }

    /**
     * Complex method.
     *
     * @param array $items
     * @return array
     */
    public function complexMethod(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if ($item > 0) {
                $result[] = $item * 2;
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }
}
PHP;

        File::put($this->testDir . '/TestClass.php', $content);
    }
} 