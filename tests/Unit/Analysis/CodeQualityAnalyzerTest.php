<?php

namespace Tests\Unit\Analysis;

use Tests\Unit\TestCase;
use App\Analysis\CodeQualityAnalyzer;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class CodeQualityAnalyzerTest extends TestCase
{
    protected string $testDir;
    protected string $testClass = 'TestClass';
    protected string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test directory
        $this->testDir = storage_path('app/test/analysis');
        if (!File::exists($this->testDir)) {
            File::makeDirectory($this->testDir, 0755, true);
        }

        // Create test class file
        $this->testFile = $this->testDir . '/TestClass.php';
        File::put($this->testFile, $this->getTestClassContent());
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (File::exists($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }
        
        parent::tearDown();
    }

    /**
     * Test that the analyzer can be instantiated.
     *
     * @return void
     */
    public function test_it_can_be_instantiated(): void
    {
        $analyzer = new CodeQualityAnalyzer($this->testDir);
        $this->assertInstanceOf(CodeQualityAnalyzer::class, $analyzer);
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
     * Get test class content.
     *
     * @return string
     */
    protected function getTestClassContent(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Unit\Analysis;

/**
 * Test class for code quality analysis.
 *
 * @package Tests\Unit\Analysis
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
     * @return bool
     */
    public function testMethod(string $param): bool
    {
        if ($param === 'test') {
            return true;
        }
        return false;
    }

    /**
     * Complex method for testing complexity metrics.
     *
     * @param array $items
     * @return array
     */
    public function complexMethod(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                foreach ($item as $subItem) {
                    if ($subItem > 0) {
                        $result[] = $subItem;
                    }
                }
            }
        }
        return $result;
    }
}
PHP;
    }
} 