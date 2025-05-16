# detect-environment.ps1
# Utility to detect Docker, Codespaces, and set container name

# Detect if running in Codespaces
$IsCodespaces = $false
if ($env:CODESPACES -eq "true" -or $env:CODESPACE_NAME) {
    $IsCodespaces = $true
}

# Detect if Docker is available
$IsDockerAvailable = $false
try {
    docker --version | Out-Null
    $IsDockerAvailable = $true
} catch {
    $IsDockerAvailable = $false
}

# Set Docker container name if available
$DockerContainerName = $null
if ($IsDockerAvailable) {
    # Try to find the main app container (by convention or label)
    $containers = docker ps --format "{{.Names}}"
    # Prefer service_learning_management-app-1, fallback to first container
    $DockerContainerName = $containers | Where-Object { $_ -like '*app*' } | Select-Object -First 1
    if (-not $DockerContainerName) {
        $DockerContainerName = $containers | Select-Object -First 1
    }
}

# Export variables for use in other scripts
Set-Variable -Name IsCodespaces -Value $IsCodespaces -Scope Global
Set-Variable -Name IsDockerAvailable -Value $IsDockerAvailable -Scope Global
Set-Variable -Name DockerContainerName -Value $DockerContainerName -Scope Global 