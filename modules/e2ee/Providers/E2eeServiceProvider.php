<?php

namespace App\Modules\E2ee\Providers;

use App\Providers\BaseModuleServiceProvider;
use App\Modules\E2ee\Services\EncryptionService;
use App\Modules\E2ee\Services\KeyManagementService;
use App\Modules\E2ee\Services\AuditService;
use App\Modules\E2ee\Middleware\EncryptionMiddleware;
use App\Modules\E2ee\Commands\GenerateKeysCommand;
use App\Modules\E2ee\Commands\RotateKeysCommand;
use App\Modules\E2ee\Commands\AuditKeysCommand;

class E2EEServiceProvider extends BaseModuleServiceProvider
{
    /**
     * The module name
     */
    protected string $moduleName = 'e2ee';

    /**
     * The module namespace
     */
    protected string $moduleNamespace = 'App\\Modules\\E2ee';

    /**
     * Configuration files to publish
     */
    protected array $configFiles = [
        'encryption',
        'keys',
        'audit',
    ];

    /**
     * Route files to load
     */
    protected array $routes = [
        'routes/api.php',
        'routes/web.php',
    ];

    /**
     * View paths to register
     */
    protected array $viewPaths = [
        'e2ee' => 'views',
    ];

    /**
     * Asset paths to publish
     */
    public array $assetPaths = [
        'assets' => null,
    ];

    public function __construct($app)
    {
        parent::__construct($app);
        $this->assetPaths['assets'] = public_path('modules/e2ee');
    }

    /**
     * Middleware to register
     */
    public array $middleware = [
        'e2ee.encrypt' => EncryptionMiddleware::class,
    ];

    /**
     * Commands to register
     */
    public array $commands = [
        \App\Modules\E2ee\Commands\E2eeStatusCommand::class,
        \App\Modules\E2ee\Commands\E2eeAuditCommand::class,
        \App\Modules\E2ee\Commands\E2eeKeyRotationCommand::class,
    ];

    /**
     * Services to bind
     */
    public array $bindings = [
        'e2ee.encryption' => EncryptionService::class,
        'e2ee.keys' => KeyManagementService::class,
        'e2ee.audit' => AuditService::class,
    ];

    /**
     * Singletons to register
     */
    public array $singletons = [
        EncryptionService::class => EncryptionService::class,
        KeyManagementService::class => KeyManagementService::class,
        AuditService::class => AuditService::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        if (!$this->isModuleEnabled()) {
            $this->logModuleActivity('Module disabled, skipping registration');
            return;
        }

        parent::register();
        $this->registerE2EEServices();
        $this->registerEventListeners();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!$this->isModuleEnabled()) {
            return;
        }

        parent::boot();
        $this->setupE2EEMiddleware();
        $this->setupE2EERoutes();
        $this->setupE2EEViews();
    }

    /**
     * Register E2EE specific services
     */
    protected function registerE2EEServices(): void
    {
        // Register encryption service with configuration
        $this->app->when(EncryptionService::class)
            ->needs('$algorithm')
            ->give($this->getModuleConfig('encryption.algorithm', 'AES-256-GCM'));

        $this->app->when(EncryptionService::class)
            ->needs('$keyLength')
            ->give($this->getModuleConfig('encryption.key_length', 32));

        // Register key management service
        $this->app->when(KeyManagementService::class)
            ->needs('$rotationDays')
            ->give($this->getModuleConfig('keys.rotation_days', 30));

        $this->app->when(KeyManagementService::class)
            ->needs('$keyPath')
            ->give($this->getModulePath('keys'));

        // Register audit service
        $this->app->when(AuditService::class)
            ->needs('$enabled')
            ->give($this->getModuleConfig('audit.enabled', true));

        $this->app->when(AuditService::class)
            ->needs('$logPath')
            ->give($this->getModulePath('audit/logs'));

        $this->logModuleActivity('E2EE services registered successfully');
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // Register encryption events
        $this->app['events']->listen(
            'e2ee.encryption.encrypted',
            'App\Modules\E2ee\Listeners\LogEncryptionEvent'
        );

        $this->app['events']->listen(
            'e2ee.encryption.decrypted',
            'App\Modules\E2ee\Listeners\LogDecryptionEvent'
        );

        // Register key management events
        $this->app['events']->listen(
            'e2ee.keys.rotated',
            'App\Modules\E2ee\Listeners\LogKeyRotationEvent'
        );

        $this->app['events']->listen(
            'e2ee.keys.generated',
            'App\Modules\E2ee\Listeners\LogKeyGenerationEvent'
        );

        $this->logModuleActivity('E2EE event listeners registered');
    }

    /**
     * Setup E2EE middleware
     */
    protected function setupE2EEMiddleware(): void
    {
        // Register middleware groups
        $this->app['router']->middlewareGroup('e2ee', [
            'e2ee.encrypt',
        ]);

        // Register route middleware
        $this->app['router']->aliasMiddleware('e2ee.encrypt', EncryptionMiddleware::class);

        $this->logModuleActivity('E2EE middleware configured');
    }

    /**
     * Setup E2EE routes
     */
    protected function setupE2EERoutes(): void
    {
        // Load API routes
        $apiRoutesPath = $this->getModulePath('routes/api.php');
        if (file_exists($apiRoutesPath)) {
            $this->app['router']->group([
                'prefix' => 'api/e2ee',
                'middleware' => ['api', 'auth:sanctum'],
                'namespace' => 'App\Modules\E2ee\Controllers\Api',
            ], function () use ($apiRoutesPath) {
                require $apiRoutesPath;
            });
        }

        // Load web routes
        $webRoutesPath = $this->getModulePath('routes/web.php');
        if (file_exists($webRoutesPath)) {
            $this->app['router']->group([
                'prefix' => 'e2ee',
                'middleware' => ['web', 'auth'],
                'namespace' => 'App\Modules\E2ee\Controllers\Web',
            ], function () use ($webRoutesPath) {
                require $webRoutesPath;
            });
        }

        $this->logModuleActivity('E2EE routes loaded');
    }

    /**
     * Setup E2EE views
     */
    protected function setupE2EEViews(): void
    {
        $viewsPath = $this->getModulePath('views');
        
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'e2ee');
            $this->logModuleActivity('E2EE views loaded');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return array_merge(parent::provides(), [
            'e2ee.encryption',
            'e2ee.keys',
            'e2ee.audit',
            EncryptionService::class,
            KeyManagementService::class,
            AuditService::class,
        ]);
    }
}

