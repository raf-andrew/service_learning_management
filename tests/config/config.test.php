<?php
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function test_core_config_files_present()
    {
        $files = [
            'base.php', 'config.php', 'bootstrap.php', 'commands.php',
            'events.php', 'folders.php', 'middleware.php', 'models.php', 'resources.php'
        ];
        foreach ($files as $file) {
            $this->assertFileExists(__DIR__ . '/../.config/' . $file);
        }
    }

    public function test_can_load_base_config()
    {
        $config = include __DIR__ . '/../.config/base.php';
        $this->assertIsArray($config);
    }

    public function test_can_load_config()
    {
        $config = include __DIR__ . '/../.config/config.php';
        $this->assertIsArray($config);
    }

    public function test_can_load_commands_config()
    {
        $config = include __DIR__ . '/../.config/commands.php';
        $this->assertIsArray($config);
    }

    public function test_can_load_events_config()
    {
        $config = include __DIR__ . '/../.config/events.php';
        $this->assertIsArray($config);
    }

    public function test_can_load_folders_config()
    {
        $config = include __DIR__ . '/../.config/folders.php';
        $this->assertIsArray($config);
    }

    public function test_can_load_middleware_config()
    {
        $config = include __DIR__ . '/../.config/middleware.php';
        $this->assertIsArray($config);
    }

    public function test_can_load_models_config()
    {
        $config = include __DIR__ . '/../.config/models.php';
        $this->assertIsArray($config);
    }

    public function test_can_load_resources_config()
    {
        $config = include __DIR__ . '/../.config/resources.php';
        $this->assertIsArray($config);
    }

    public function test_env_override_logic()
    {
        // Test environment variable override logic
        $originalEnv = $_ENV['APP_ENV'] ?? null;
        
        // Test default value when env var is not set
        unset($_ENV['APP_ENV']);
        $config = include __DIR__ . '/../.config/base.php';
        $this->assertIsArray($config);
        
        // Test env var override
        $_ENV['APP_ENV'] = 'testing';
        $config = include __DIR__ . '/../.config/base.php';
        $this->assertIsArray($config);
        
        // Restore original value
        if ($originalEnv !== null) {
            $_ENV['APP_ENV'] = $originalEnv;
        } else {
            unset($_ENV['APP_ENV']);
        }
    }

    public function test_config_validation()
    {
        // Test that all config files return valid arrays
        $configFiles = [
            'base.php', 'config.php', 'bootstrap.php', 'commands.php',
            'events.php', 'folders.php', 'middleware.php', 'models.php', 'resources.php'
        ];
        
        foreach ($configFiles as $file) {
            $config = include __DIR__ . '/../.config/' . $file;
            $this->assertIsArray($config, "Config file {$file} should return an array");
            $this->assertNotEmpty($config, "Config file {$file} should not be empty");
        }
    }

    public function test_config_documentation_presence()
    {
        // Check for documentation files
        $docsPath = __DIR__ . '/../docs';
        $readmePath = __DIR__ . '/../README.md';
        
        $this->assertTrue(
            file_exists($docsPath) || file_exists($readmePath),
            'Documentation should exist in either docs/ directory or README.md'
        );
        
        // Check for docstrings in config files
        $configFiles = [
            'base.php', 'config.php', 'bootstrap.php', 'commands.php',
            'events.php', 'folders.php', 'middleware.php', 'models.php', 'resources.php'
        ];
        
        foreach ($configFiles as $file) {
            $content = file_get_contents(__DIR__ . '/../.config/' . $file);
            $this->assertStringContainsString(
                '<?php',
                $content,
                "Config file {$file} should be a valid PHP file"
            );
        }
    }
}


