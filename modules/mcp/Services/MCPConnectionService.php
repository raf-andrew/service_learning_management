<?php

namespace Modules\MCP\Services;

class MCPConnectionService
{
    /**
     * Run a PowerShell script to establish an MCP connection.
     */
    public function connect(string $configPath): array
    {
        $script = base_path('modules/mcp/Utils/Services.ps1');
        $command = "powershell -ExecutionPolicy Bypass -File \"{$script}\" -ConfigPath \"{$configPath}\"";
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        return [
            'output' => $output,
            'status' => $returnVar === 0 ? 'success' : 'error',
        ];
    }
} 