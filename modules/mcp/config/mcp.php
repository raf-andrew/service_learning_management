<?php

return [
    'enabled' => env('MCP_ENABLED', true),
    'default_config_path' => env('MCP_DEFAULT_CONFIG_PATH', base_path('modules/mcp/Utils/Configuration.ps1')),
    'connection_timeout' => env('MCP_CONNECTION_TIMEOUT', 30),
    'log_level' => env('MCP_LOG_LEVEL', 'info'),
]; 