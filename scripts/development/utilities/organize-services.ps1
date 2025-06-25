#!/usr/bin/env pwsh

# Service Organization and Namespace Refactoring Script
# This script organizes loose services into proper directories and updates namespaces

param(
    [switch]$DryRun,
    [switch]$Verbose
)

$ErrorActionPreference = "Stop"

# Configuration
$BasePath = "modules/shared"
$ServicesPath = "$BasePath/Services"

# Service organization mapping
$ServiceMapping = @{
    # Core services
    "BaseService.php" = "Core"
    "BaseRepository.php" = "Core"
    "AuditService.php" = "Core"
    
    # Caching services
    "CacheService.php" = "Caching"
    
    # Monitoring services
    "MonitoringService.php" = "Monitoring"
    "PerformanceOptimizationService.php" = "Monitoring"
    
    # Configuration services
    "ConfigurationService.php" = "Configuration"
    "ModuleDiscoveryService.php" = "Configuration"
}

# Function to write colored output
function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    
    if ($Verbose -or $Color -ne "White") {
        Write-Host $Message -ForegroundColor $Color
    }
}

# Function to backup file
function Backup-File {
    param([string]$FilePath)
    
    if (Test-Path $FilePath) {
        $backupPath = "$FilePath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        Copy-Item $FilePath $backupPath
        Write-ColorOutput "Backed up: $FilePath -> $backupPath" "Yellow"
        return $backupPath
    }
    return $null
}

# Function to update namespace in file
function Update-Namespace {
    param(
        [string]$FilePath,
        [string]$NewNamespace
    )
    
    if (-not (Test-Path $FilePath)) {
        Write-ColorOutput "File not found: $FilePath" "Red"
        return $false
    }
    
    $content = Get-Content $FilePath -Raw
    $originalContent = $content
    
    # Update namespace declaration
    $content = $content -replace 'namespace App\\Modules\\Shared;', "namespace App\Modules\Shared\Services\$NewNamespace;"
    
    # Update use statements for moved services
    $content = $content -replace 'use App\\Modules\\Shared\\([^;]+);', "use App\Modules\Shared\Services\$NewNamespace`$1;"
    
    if ($content -ne $originalContent) {
        if (-not $DryRun) {
            Set-Content $FilePath $content -NoNewline
            Write-ColorOutput "Updated namespace in: $FilePath" "Green"
        } else {
            Write-ColorOutput "Would update namespace in: $FilePath" "Cyan"
        }
        return $true
    }
    
    return $false
}

# Function to update references in other files
function Update-References {
    param([string]$ServiceName, [string]$NewPath)
    
    $files = Get-ChildItem -Path "modules" -Recurse -Include "*.php" | Where-Object { $_.FullName -notlike "*$ServiceName*" }
    
    foreach ($file in $files) {
        $content = Get-Content $file.FullName -Raw
        $originalContent = $content
        
        # Update use statements
        $oldNamespace = "App\Modules\Shared\$ServiceName"
        $newNamespace = "App\Modules\Shared\Services\$NewPath\$ServiceName"
        
        $content = $content -replace [regex]::Escape($oldNamespace), $newNamespace
        
        if ($content -ne $originalContent) {
            if (-not $DryRun) {
                Set-Content $file.FullName $content -NoNewline
                Write-ColorOutput "Updated references in: $($file.FullName)" "Green"
            } else {
                Write-ColorOutput "Would update references in: $($file.FullName)" "Cyan"
            }
        }
    }
}

# Main execution
Write-ColorOutput "=== Service Organization and Namespace Refactoring Script ===" "Magenta"
Write-ColorOutput "Base Path: $BasePath" "White"
Write-ColorOutput "Services Path: $ServicesPath" "White"

if ($DryRun) {
    Write-ColorOutput "DRY RUN MODE - No files will be modified" "Yellow"
}

# Create backup directory
$backupDir = "backups/services-$(Get-Date -Format 'yyyyMMdd_HHmmss')"
if (-not $DryRun) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Write-ColorOutput "Created backup directory: $backupDir" "Yellow"
}

# Process each service
foreach ($service in $ServiceMapping.GetEnumerator()) {
    $serviceFile = $service.Key
    $targetDir = $service.Value
    $sourcePath = "$BasePath/$serviceFile"
    $targetPath = "$ServicesPath/$targetDir/$serviceFile"
    
    Write-ColorOutput "Processing: $serviceFile -> $targetDir" "White"
    
    if (Test-Path $sourcePath) {
        # Backup original file
        if (-not $DryRun) {
            $backupPath = Backup-File $sourcePath
        }
        
        # Create target directory if it doesn't exist
        $targetDirPath = "$ServicesPath/$targetDir"
        if (-not (Test-Path $targetDirPath)) {
            if (-not $DryRun) {
                New-Item -ItemType Directory -Path $targetDirPath -Force | Out-Null
                Write-ColorOutput "Created directory: $targetDirPath" "Green"
            } else {
                Write-ColorOutput "Would create directory: $targetDirPath" "Cyan"
            }
        }
        
        # Move file
        if (-not $DryRun) {
            Move-Item $sourcePath $targetPath
            Write-ColorOutput "Moved: $sourcePath -> $targetPath" "Green"
        } else {
            Write-ColorOutput "Would move: $sourcePath -> $targetPath" "Cyan"
        }
        
        # Update namespace
        $serviceName = [System.IO.Path]::GetFileNameWithoutExtension($serviceFile)
        Update-Namespace -FilePath $targetPath -NewNamespace $targetDir
        
        # Update references in other files
        Update-References -ServiceName $serviceName -NewPath $targetDir
        
    } else {
        Write-ColorOutput "Service file not found: $sourcePath" "Red"
    }
}

# Update service provider registrations
Write-ColorOutput "Updating service provider registrations..." "White"

$serviceProviderFiles = @(
    "modules/shared/SharedServiceProvider.php",
    "modules/e2ee/Providers/E2eeServiceProvider.php",
    "modules/soc2/providers/Soc2ServiceProvider.php",
    "modules/web3/Web3ServiceProvider.php",
    "modules/mcp/MCPServiceProvider.php"
)

foreach ($providerFile in $serviceProviderFiles) {
    if (Test-Path $providerFile) {
        $content = Get-Content $providerFile -Raw
        $originalContent = $content
        
        # Update service bindings to new namespaces
        foreach ($service in $ServiceMapping.GetEnumerator()) {
            $serviceName = [System.IO.Path]::GetFileNameWithoutExtension($service.Key)
            $oldNamespace = "App\Modules\Shared\$serviceName"
            $newNamespace = "App\Modules\Shared\Services\$($service.Value)\$serviceName"
            
            $content = $content -replace [regex]::Escape($oldNamespace), $newNamespace
        }
        
        if ($content -ne $originalContent) {
            if (-not $DryRun) {
                Set-Content $providerFile $content -NoNewline
                Write-ColorOutput "Updated service provider: $providerFile" "Green"
            } else {
                Write-ColorOutput "Would update service provider: $providerFile" "Cyan"
            }
        }
    }
}

# Generate summary report
Write-ColorOutput "`n=== Summary ===" "Magenta"
Write-ColorOutput "Services processed: $($ServiceMapping.Count)" "White"
Write-ColorOutput "Target directories created: $($ServiceMapping.Values | Sort-Object -Unique | Measure-Object | Select-Object -ExpandProperty Count)" "White"

if (-not $DryRun) {
    Write-ColorOutput "Backup location: $backupDir" "Yellow"
    Write-ColorOutput "All services have been organized and namespaces updated!" "Green"
} else {
    Write-ColorOutput "Dry run completed. Review the changes above." "Yellow"
}

Write-ColorOutput "`n=== Next Steps ===" "Magenta"
Write-ColorOutput "1. Run tests to ensure everything works correctly" "White"
Write-ColorOutput "2. Update any remaining hardcoded references" "White"
Write-ColorOutput "3. Update documentation to reflect new structure" "White"
Write-ColorOutput "4. Commit changes to version control" "White" 