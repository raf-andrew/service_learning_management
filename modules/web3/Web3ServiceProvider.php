<?php

namespace App\Modules\Web3;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class Web3ServiceProvider extends ServiceProvider
{
    /**
     * The module name
     */
    protected string $moduleName = 'web3';

    /**
     * The module namespace
     */
    protected string $moduleNamespace = 'App\\Modules\\Web3';

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register web3 services
        $this->registerWeb3Services();
        
        // Register contracts
        $this->registerContracts();
        
        Log::info('Web3ServiceProvider registered successfully');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register web3 middleware
        $this->registerMiddleware();
        
        // Register web3 commands
        $this->registerCommands();
        
        // Register web3 views
        $this->registerViews();
        
        Log::info('Web3ServiceProvider booted successfully');
    }

    /**
     * Register web3 services
     */
    protected function registerWeb3Services(): void
    {
        $services = [
            'web3.ethereum' => \App\Modules\Web3\Services\EthereumService::class,
            'web3.blockchain' => \App\Modules\Web3\Services\BlockchainService::class,
            'web3.wallet' => \App\Modules\Web3\Services\WalletService::class,
            'web3.smart-contract' => \App\Modules\Web3\Services\SmartContractService::class,
        ];

        foreach ($services as $abstract => $concrete) {
            if (class_exists($concrete)) {
                $this->app->singleton($abstract, $concrete);
                Log::info("Web3 service registered: {$abstract}");
            }
        }
    }

    /**
     * Register contracts
     */
    protected function registerContracts(): void
    {
        $contractsPath = __DIR__ . '/contracts';
        if (is_dir($contractsPath)) {
            $contractFiles = glob($contractsPath . '/*.php');
            foreach ($contractFiles as $file) {
                $className = $this->getClassNameFromFile($file);
                if ($className && class_exists($className)) {
                    $this->app->singleton($className, $className);
                    Log::info("Web3 contract registered: {$className}");
                }
            }
        }
    }

    /**
     * Register middleware
     */
    protected function registerMiddleware(): void
    {
        $middlewarePath = __DIR__ . '/Middleware';
        if (is_dir($middlewarePath)) {
            $middlewareFiles = glob($middlewarePath . '/*.php');
            foreach ($middlewareFiles as $file) {
                $className = $this->getClassNameFromFile($file);
                if ($className) {
                    $this->app['router']->pushMiddlewareToGroup('web', $className);
                    Log::info("Web3 middleware registered: {$className}");
                }
            }
        }
    }

    /**
     * Register commands
     */
    protected function registerCommands(): void
    {
        $commandsPath = __DIR__ . '/Commands';
        if (is_dir($commandsPath)) {
            $commandFiles = glob($commandsPath . '/*.php');
            foreach ($commandFiles as $file) {
                $className = $this->getClassNameFromFile($file);
                if ($className && class_exists($className)) {
                    $this->commands[] = $className;
                    Log::info("Web3 command registered: {$className}");
                }
            }
        }
    }

    /**
     * Register views
     */
    protected function registerViews(): void
    {
        $viewsPath = __DIR__ . '/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'web3');
            Log::info('Web3 views registered');
        }
    }

    /**
     * Get class name from file
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        try {
            $content = file_get_contents($filePath);
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];
                
                // Try to determine namespace
                if (preg_match('/namespace\s+([^;]+)/', $content, $namespaceMatches)) {
                    $namespace = trim($namespaceMatches[1]);
                    return $namespace . '\\' . $className;
                }
                
                return $className;
            }
        } catch (\Exception $e) {
            Log::error("Error reading class from file {$filePath}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'web3.ethereum',
            'web3.blockchain',
            'web3.wallet',
            'web3.smart-contract',
        ];
    }
} 