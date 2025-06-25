<?php

namespace Tests\Unit\Sniffing;

use Tests\TestCase;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;
use ServiceLearning\Sniffs\ServiceLearningStandard;

class ServiceLearningStandardTest extends TestCase
{
    private $config;
    private $ruleset;
    private $standard;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = new Config();
        $this->ruleset = new Ruleset($this->config);
        $this->standard = new ServiceLearningStandard();
    }

    public function test_validates_class_naming_convention()
    {
        $file = $this->createTestFile('
            <?php
            class invalidClassName {
            }
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasError('Class name "invalidClassName" does not follow PSR-4 naming convention'));
    }

    public function test_validates_interface_naming_convention()
    {
        $file = $this->createTestFile('
            <?php
            interface InvalidInterface {
            }
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasError('Interface name "InvalidInterface" must end with "Interface"'));
    }

    public function test_validates_trait_naming_convention()
    {
        $file = $this->createTestFile('
            <?php
            trait InvalidTrait {
            }
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasError('Trait name "InvalidTrait" must end with "Trait"'));
    }

    public function test_validates_function_naming_convention()
    {
        $file = $this->createTestFile('
            <?php
            function InvalidFunction() {
            }
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasError('Function name "InvalidFunction" must be in camelCase'));
    }

    public function test_validates_variable_naming_convention()
    {
        $file = $this->createTestFile('
            <?php
            $InvalidVariable = 1;
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasError('Variable name "InvalidVariable" must be in camelCase'));
    }

    public function test_validates_constant_naming_convention()
    {
        $file = $this->createTestFile('
            <?php
            const invalid_constant = 1;
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasError('Constant name "invalid_constant" must be in UPPER_CASE'));
    }

    public function test_validates_service_class_requirements()
    {
        $file = $this->createTestFile('
            <?php
            class UserService {
            }
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasError('Service class "UserService" must implement a service interface'));
    }

    public function test_validates_service_function_requirements()
    {
        $file = $this->createTestFile('
            <?php
            function processService() {
                // No try-catch block
            }
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasWarning('Service function "processService" must include try-catch block for error handling'));
    }

    public function test_validates_documentation_requirements()
    {
        $file = $this->createTestFile('
            <?php
            /**
             * Missing @param and @return tags
             */
            function testFunction() {
            }
        ');

        $this->standard->process($file, 0);
        $this->assertTrue($file->hasWarning('Documentation block must include "@param" tag'));
        $this->assertTrue($file->hasWarning('Documentation block must include "@return" tag'));
    }

    private function createTestFile(string $content): LocalFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, $content);
        
        return new LocalFile($tempFile, $this->ruleset, $this->config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
} 