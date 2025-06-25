<?php

namespace Modules\Api;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Api\Services\ApiService;
use Modules\Api\Services\ResponseFormatterService;
use Modules\Api\Services\RateLimitService;
use Modules\Api\Middleware\ApiAuthenticationMiddleware;
use Modules\Api\Middleware\ApiRateLimitMiddleware;
use Modules\Api\Middleware\ApiVersioningMiddleware;
use Modules\Api\Exceptions\ApiException;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register API services
        $this->app->singleton(ApiService::class, function ($app) {
            return new ApiService(
                $app['cache'],
                $app['log']
            );
        });

        $this->app->singleton(ResponseFormatterService::class, function ($app) {
            return new ResponseFormatterService(
                $app['config']
            );
        });

        $this->app->singleton(RateLimitService::class, function ($app) {
            return new RateLimitService(
                $app['cache'],
                $app['config']
            );
        });

        // Register API middleware
        $this->app->singleton(ApiAuthenticationMiddleware::class, function ($app) {
            return new ApiAuthenticationMiddleware(
                $app['auth'],
                $app['config']
            );
        });

        $this->app->singleton(ApiRateLimitMiddleware::class, function ($app) {
            return new ApiRateLimitMiddleware(
                $app->make(RateLimitService::class)
            );
        });

        $this->app->singleton(ApiVersioningMiddleware::class, function ($app) {
            return new ApiVersioningMiddleware(
                $app['config']
            );
        });

        // Register API config
        $this->mergeConfigFrom(
            __DIR__ . '/config/api.php', 'modules.api'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $this->loadRoutesFrom(__DIR__ . '/routes/api.php'); // Removed to prevent duplicate route registration
        // $this->loadRoutesFrom(__DIR__ . '/routes/web.php'); // Removed to prevent duplicate route registration

        // Load API views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'api');

        // Load API migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load API translations
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'api');

        // Register API middleware
        $this->registerMiddleware();

        // Register API policies
        $this->registerPolicies();

        // Register API macros
        $this->registerMacros();

        // Register API event listeners
        $this->registerEventListeners();

        // Register API blade directives
        $this->registerBladeDirectives();

        // Register API validation rules
        $this->registerValidationRules();

        // Register API commands
        $this->registerCommands();

        // Publish API assets
        $this->publishes([
            __DIR__ . '/config/api.php' => config_path('modules/api.php'),
            __DIR__ . '/resources/views' => resource_path('views/vendor/api'),
            __DIR__ . '/resources/lang' => resource_path('lang/vendor/api'),
        ], 'api-module');

        Log::info('API module booted successfully');
    }

    /**
     * Register API middleware
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('api.auth', ApiAuthenticationMiddleware::class);
        $this->app['router']->aliasMiddleware('api.rate.limit', ApiRateLimitMiddleware::class);
        $this->app['router']->aliasMiddleware('api.version', ApiVersioningMiddleware::class);
    }

    /**
     * Register API policies
     */
    protected function registerPolicies(): void
    {
        // API access policies
        Gate::define('api.access', function ($user) {
            return $user->hasPermission('api.access');
        });

        Gate::define('api.admin', function ($user) {
            return $user->hasPermission('api.admin') || $user->hasRole('admin');
        });

        Gate::define('api.read', function ($user) {
            return $user->hasPermission('api.read');
        });

        Gate::define('api.write', function ($user) {
            return $user->hasPermission('api.write');
        });

        Gate::define('api.delete', function ($user) {
            return $user->hasPermission('api.delete');
        });
    }

    /**
     * Register API macros
     */
    protected function registerMacros(): void
    {
        // Response macro for API success
        \Illuminate\Http\Response::macro('apiSuccess', function ($data = null, $message = 'Success', $code = 200) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
                'timestamp' => now()->toISOString(),
                'code' => $code
            ], $code);
        });

        // Response macro for API error
        \Illuminate\Http\Response::macro('apiError', function ($message = 'Error', $code = 400, $errors = null) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
                'timestamp' => now()->toISOString(),
                'code' => $code
            ], $code);
        });

        // Response macro for API pagination
        \Illuminate\Http\Response::macro('apiPaginated', function ($data, $message = 'Success') {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ],
                'timestamp' => now()->toISOString()
            ]);
        });
    }

    /**
     * Register API event listeners
     */
    protected function registerEventListeners(): void
    {
        // API request event
        Event::listen('api.request', function ($request) {
            Log::info('API Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);
        });

        // API response event
        Event::listen('api.response', function ($response) {
            Log::info('API Response', [
                'status_code' => $response->getStatusCode(),
                'response_time' => microtime(true) - LARAVEL_START,
                'timestamp' => now()
            ]);
        });

        // API error event
        Event::listen('api.error', function ($exception) {
            Log::error('API Error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);
        });
    }

    /**
     * Register API blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @apiVersion directive
        Blade::directive('apiVersion', function ($version) {
            return "<?php echo config('modules.api.versioning.current') == {$version} ? 'true' : 'false'; ?>";
        });

        // @apiEnabled directive
        Blade::directive('apiEnabled', function () {
            return "<?php echo config('modules.api.enabled') ? 'true' : 'false'; ?>";
        });

        // @apiRateLimit directive
        Blade::directive('apiRateLimit', function ($limit) {
            return "<?php echo config('modules.api.rate_limiting.default_limit') <= {$limit} ? 'true' : 'false'; ?>";
        });
    }

    /**
     * Register API validation rules
     */
    protected function registerValidationRules(): void
    {
        // API version validation rule
        Validator::extend('api_version', function ($attribute, $value, $parameters, $validator) {
            $supportedVersions = config('modules.api.versioning.supported_versions', []);
            return in_array($value, $supportedVersions);
        }, 'The :attribute must be a supported API version.');

        // API rate limit validation rule
        Validator::extend('api_rate_limit', function ($attribute, $value, $parameters, $validator) {
            $rateLimitService = app(RateLimitService::class);
            return $rateLimitService->checkLimit($value);
        }, 'The :attribute exceeds the API rate limit.');

        // API key validation rule
        Validator::extend('api_key', function ($attribute, $value, $parameters, $validator) {
            $apiService = app(ApiService::class);
            return $apiService->validateApiKey($value);
        }, 'The :attribute must be a valid API key.');
    }

    /**
     * Register API commands
     */
    protected function registerCommands(): void
    {
        if (
            $this->app->runningInConsole()
        ) {
            // Commented out missing commands to prevent boot errors
            // $this->commands([
            //     \Modules\Api\Console\Commands\GenerateApiKeyCommand::class,
            //     \Modules\Api\Console\Commands\RevokeApiKeyCommand::class,
            //     \Modules\Api\Console\Commands\ListApiKeysCommand::class,
            //     \Modules\Api\Console\Commands\ApiStatsCommand::class,
            //     \Modules\Api\Console\Commands\ApiDocsCommand::class,
            // ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ApiService::class,
            ResponseFormatterService::class,
            RateLimitService::class,
            ApiAuthenticationMiddleware::class,
            ApiRateLimitMiddleware::class,
            ApiVersioningMiddleware::class,
        ];
    }
} 