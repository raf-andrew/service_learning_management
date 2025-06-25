<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CodespaceConfigurationManager
{
    protected $config;
    protected $dockerConfig;
    protected $networkConfig;

    public function __construct()
    {
        $this->loadConfigurations();
    }

    protected function loadConfigurations()
    {
        $this->config = Config::get('codespaces');
        $this->dockerConfig = $this->loadDockerConfig();
        $this->networkConfig = $this->loadNetworkConfig();
    }

    protected function loadDockerConfig()
    {
        return [
            'services' => [
                'app' => [
                    'image' => 'mcr.microsoft.com/devcontainers/php:8.2',
                    'ports' => ['8000:8000'],
                    'volumes' => [
                        '.:/var/www/html',
                        './storage:/var/www/html/storage',
                    ],
                    'environment' => [
                        'APP_ENV' => 'local',
                        'APP_DEBUG' => 'true',
                    ],
                ],
                'mysql' => [
                    'image' => 'mysql:8.0',
                    'ports' => ['3306:3306'],
                    'environment' => [
                        'MYSQL_DATABASE' => env('DB_DATABASE', 'service_learning'),
                        'MYSQL_ROOT_PASSWORD' => env('DB_PASSWORD', ''),
                    ],
                    'volumes' => [
                        'mysql_data:/var/lib/mysql',
                    ],
                ],
                'redis' => [
                    'image' => 'redis:alpine',
                    'ports' => ['6379:6379'],
                ],
            ],
            'volumes' => [
                'mysql_data' => [
                    'driver' => 'local',
                ],
            ],
            'networks' => [
                'codespace' => [
                    'driver' => 'bridge',
                ],
            ],
        ];
    }

    protected function loadNetworkConfig()
    {
        return [
            'default' => [
                'driver' => 'bridge',
                'ipam' => [
                    'driver' => 'default',
                    'config' => [
                        [
                            'subnet' => '172.20.0.0/16',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function generateDockerCompose()
    {
        $compose = [
            'version' => '3.8',
            'services' => $this->dockerConfig['services'],
            'volumes' => $this->dockerConfig['volumes'],
            'networks' => $this->dockerConfig['networks'],
        ];

        return yaml_emit($compose);
    }

    public function generateDevContainerConfig()
    {
        return [
            'name' => 'Service Learning Management',
            'dockerComposeFile' => 'docker-compose.yml',
            'service' => 'app',
            'workspaceFolder' => '/var/www/html',
            'customizations' => [
                'vscode' => [
                    'extensions' => [
                        'bmewburn.vscode-intelephense-client',
                        'onecentlin.laravel-blade',
                        'shufo.vscode-blade-formatter',
                        'amiralizadeh9480.laravel-extra-intellisense',
                        'ryannaddy.laravel-artisan',
                        'mikestead.dotenv',
                        'esbenp.prettier-vscode',
                        'dbaeumer.vscode-eslint',
                    ],
                    'settings' => [
                        'editor.formatOnSave' => true,
                        'editor.defaultFormatter' => 'esbenp.prettier-vscode',
                        'editor.codeActionsOnSave' => [
                            'source.fixAll.eslint' => true,
                        ],
                        'php.validate.enable' => true,
                        'php.suggest.basic' => true,
                    ],
                ],
            ],
            'forwardPorts' => [8000],
            'postCreateCommand' => 'composer install && php artisan key:generate && php artisan migrate',
            'remoteUser' => 'vscode',
        ];
    }

    public function generateCodespaceConfig()
    {
        return [
            'name' => 'Service Learning Management',
            'image' => 'mcr.microsoft.com/devcontainers/php:8.2',
            'features' => [
                'ghcr.io/devcontainers/features/github-cli:1' => [],
                'ghcr.io/devcontainers/features/docker-in-docker:2' => [],
                'ghcr.io/devcontainers/features/node:1' => [],
            ],
            'customizations' => $this->generateDevContainerConfig()['customizations'],
            'forwardPorts' => [8000],
            'postCreateCommand' => 'composer install && php artisan key:generate && php artisan migrate',
            'remoteUser' => 'vscode',
            'mounts' => [
                'source=${localWorkspaceFolder}/.devcontainer/docker/data,target=/var/lib/mysql,type=bind,consistency=cached',
            ],
            'runArgs' => [
                '--network-alias=app',
                '--network=codespace',
            ],
            'workspaceFolder' => '/workspaces/${localWorkspaceFolderBasename}',
            'workspaceMount' => 'source=${localWorkspaceFolder},target=/workspaces/${localWorkspaceFolderBasename},type=bind,consistency=cached',
            'updateContentCommand' => 'composer update',
            'postStartCommand' => 'php artisan serve --host=0.0.0.0 --port=8000',
        ];
    }

    public function updateConfiguration(array $config)
    {
        $this->config = array_merge($this->config, $config);
        $this->saveConfiguration();
    }

    protected function saveConfiguration()
    {
        File::put(
            config_path('codespaces.php'),
            '<?php return ' . var_export($this->config, true) . ';'
        );
    }

    public function getRequiredServices()
    {
        return array_keys($this->dockerConfig['services']);
    }

    public function getNetworkConfig()
    {
        return $this->networkConfig;
    }

    public function validateConfiguration()
    {
        $required = ['app', 'mysql'];
        $services = $this->getRequiredServices();

        foreach ($required as $service) {
            if (!in_array($service, $services)) {
                throw new \RuntimeException("Required service {$service} is missing from configuration");
            }
        }

        return true;
    }
} 