<?php

namespace Modules\Auth;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Services\AuthenticationService;
use Modules\Auth\Services\AuthorizationService;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\UserRole;
use Modules\Auth\Models\RolePermission;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Auth services
        $this->app->singleton(AuthenticationService::class, function ($app) {
            return new AuthenticationService(
                $app['hash'],
                $app['auth'],
                $app['session'],
                $app['cache'],
                $app['log']
            );
        });

        $this->app->singleton(AuthorizationService::class, function ($app) {
            return new AuthorizationService(
                $app['cache'],
                $app['log']
            );
        });

        // Register Auth config
        $this->mergeConfigFrom(
            __DIR__ . '/config/auth.php', 'modules.auth'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load Auth routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // Load Auth views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'auth');

        // Load Auth migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load Auth translations
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'auth');

        // Register Auth policies
        $this->registerPolicies();

        // Register Auth gates
        $this->registerGates();

        // Register Auth macros
        $this->registerMacros();

        // Register Auth event listeners
        $this->registerEventListeners();

        // Register Auth blade directives
        $this->registerBladeDirectives();

        // Register Auth validation rules
        $this->registerValidationRules();

        // Register Auth commands
        $this->registerCommands();

        // Publish Auth assets
        $this->publishes([
            __DIR__ . '/config/auth.php' => config_path('modules/auth.php'),
            __DIR__ . '/resources/views' => resource_path('views/vendor/auth'),
            __DIR__ . '/resources/lang' => resource_path('lang/vendor/auth'),
        ], 'auth-module');

        Log::info('Auth module booted successfully');
    }

    /**
     * Register Auth policies
     */
    protected function registerPolicies(): void
    {
        // Role policies
        Gate::policy(Role::class, \Modules\Auth\Policies\RolePolicy::class);
        Gate::policy(Permission::class, \Modules\Auth\Policies\PermissionPolicy::class);
        Gate::policy(UserRole::class, \Modules\Auth\Policies\UserRolePolicy::class);
        Gate::policy(RolePermission::class, \Modules\Auth\Policies\RolePermissionPolicy::class);
    }

    /**
     * Register Auth gates
     */
    protected function registerGates(): void
    {
        // Super admin gate
        Gate::define('super-admin', function ($user) {
            return $user->hasRole('super-admin');
        });

        // Admin gate
        Gate::define('admin', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        // Manager gate
        Gate::define('manager', function ($user) {
            return $user->hasRole('manager') || $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        // User management gate
        Gate::define('manage-users', function ($user) {
            return $user->hasPermission('users.manage') || $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        // Role management gate
        Gate::define('manage-roles', function ($user) {
            return $user->hasPermission('roles.manage') || $user->hasRole('admin') || $user->hasRole('super-admin');
        });

        // Permission management gate
        Gate::define('manage-permissions', function ($user) {
            return $user->hasPermission('permissions.manage') || $user->hasRole('admin') || $user->hasRole('super-admin');
        });
    }

    /**
     * Register Auth macros
     */
    protected function registerMacros(): void
    {
        // User macro for role checking
        \App\Models\User::macro('hasRole', function ($role) {
            return app(AuthorizationService::class)->userHasRole($this, $role);
        });

        // User macro for permission checking
        \App\Models\User::macro('hasPermission', function ($permission) {
            return app(AuthorizationService::class)->userHasPermission($this, $permission);
        });

        // User macro for getting roles
        \App\Models\User::macro('getRoles', function () {
            return app(AuthorizationService::class)->getUserRoles($this);
        });

        // User macro for getting permissions
        \App\Models\User::macro('getPermissions', function () {
            return app(AuthorizationService::class)->getUserPermissions($this);
        });
    }

    /**
     * Register Auth event listeners
     */
    protected function registerEventListeners(): void
    {
        // User login event
        Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            Log::info('User logged in', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()
            ]);
        });

        // User logout event
        Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            Log::info('User logged out', [
                'user_id' => $event->user->id ?? null,
                'email' => $event->user->email ?? null,
                'ip' => request()->ip(),
                'timestamp' => now()
            ]);
        });

        // Failed login event
        Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            Log::warning('Failed login attempt', [
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()
            ]);
        });
    }

    /**
     * Register Auth blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @role directive
        Blade::if('role', function ($role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        // @permission directive
        Blade::if('permission', function ($permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        // @admin directive
        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->hasRole('admin');
        });

        // @superadmin directive
        Blade::if('superadmin', function () {
            return auth()->check() && auth()->user()->hasRole('super-admin');
        });
    }

    /**
     * Register Auth validation rules
     */
    protected function registerValidationRules(): void
    {
        // Role exists validation rule
        Validator::extend('role_exists', function ($attribute, $value, $parameters, $validator) {
            return Role::where('name', $value)->exists();
        }, 'The selected role does not exist.');

        // Permission exists validation rule
        Validator::extend('permission_exists', function ($attribute, $value, $parameters, $validator) {
            return Permission::where('name', $value)->exists();
        }, 'The selected permission does not exist.');

        // Strong password validation rule
        Validator::extend('strong_password', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value);
        }, 'The password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
    }

    /**
     * Register Auth commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // Commented out missing commands to prevent boot errors
            // $this->commands([
            //     \Modules\Auth\Console\Commands\CreateRoleCommand::class,
            //     \Modules\Auth\Console\Commands\CreatePermissionCommand::class,
            //     \Modules\Auth\Console\Commands\AssignRoleCommand::class,
            //     \Modules\Auth\Console\Commands\AssignPermissionCommand::class,
            //     \Modules\Auth\Console\Commands\SyncRolesCommand::class,
            //     \Modules\Auth\Console\Commands\SyncPermissionsCommand::class,
            // ]);
        }
    }
} 