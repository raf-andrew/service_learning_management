# Deploy Health Monitoring System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")
. (Join-Path $scriptPath "utils" "environment.ps1")
. (Join-Path $scriptPath "utils" "github.ps1")

# Parse command line arguments
param (
    [string]$Environment = "production",
    [string]$Branch = "main",
    [switch]$Force,
    [switch]$Verify,
    [switch]$AutoHeal
)

# Deploy to GitHub
function Deploy-ToGitHub {
    param (
        [string]$Environment,
        [string]$Branch
    )
    
    Write-Log "Deploying to GitHub..." -Level "INFO" -Category "Deploy"
    
    try {
        # Check if we're in a git repository
        if (-not (Test-Path ".git")) {
            Write-Log "Not in a git repository" -Level "ERROR" -Category "Deploy"
            return $false
        }
        
        # Check for uncommitted changes
        $status = git status --porcelain
        if ($status) {
            Write-Log "Uncommitted changes found. Please commit or stash changes before deploying." -Level "WARNING" -Category "Deploy"
            return $false
        }
        
        # Push changes
        git push origin $Branch
        if ($LASTEXITCODE -ne 0) {
            Write-Log "Failed to push changes" -Level "ERROR" -Category "Deploy"
            return $false
        }
        
        # Create deployment
        $deployment = @{
            ref = $Branch
            environment = $Environment
            auto_merge = $false
            required_contexts = @()
        }
        
        $token = gh auth token
        $headers = @{
            "Authorization" = "token $token"
            "Accept" = "application/vnd.github.v3+json"
        }
        
        $repo = git config --get remote.origin.url | ForEach-Object { $_ -replace ".*[:/]([^/]+/[^/]+)\.git$", '$1' }
        $response = Invoke-RestMethod -Uri "$($githubConfig.API)/repos/$repo/deployments" -Method Post -Headers $headers -Body ($deployment | ConvertTo-Json)
        
        Write-Log "Deployment created: $($response.url)" -Level "SUCCESS" -Category "Deploy"
        return $true
    }
    catch {
        Write-Log "Error during deployment: $_" -Level "ERROR" -Category "Deploy"
        return $false
    }
}

# Main deployment
try {
    Write-Host "Deploying Health Monitoring System..."
    Write-Host "================================="
    
    # Verify GitHub authentication
    if (-not (Test-GitHubAuth)) {
        if ($AutoHeal) {
            Write-Host "`nAttempting to heal GitHub authentication..."
            $issue = @{
                Check = "GitHub"
                Category = "GitHub"
                Message = "GitHub authentication failed"
                Details = @{ Environment = $Environment }
            }
            
            if (Start-Healing -Issue $issue) {
                Write-Host "GitHub authentication healed successfully" -ForegroundColor "Green"
            } else {
                Write-Host "Failed to heal GitHub authentication" -ForegroundColor "Red"
                if (-not $Force) {
                    exit 1
                }
            }
        } elseif (-not $Force) {
            exit 1
        }
    }
    
    # Deploy to GitHub
    if (-not (Deploy-ToGitHub -Environment $Environment -Branch $Branch)) {
        if ($AutoHeal) {
            Write-Host "`nAttempting to heal deployment..."
            $issue = @{
                Check = "Deployment"
                Category = "Deployment"
                Message = "Deployment failed"
                Details = @{ 
                    Environment = $Environment
                    Branch = $Branch
                }
            }
            
            if (Start-Healing -Issue $issue) {
                Write-Host "Deployment healed successfully" -ForegroundColor "Green"
            } else {
                Write-Host "Failed to heal deployment" -ForegroundColor "Red"
                if (-not $Force) {
                    exit 1
                }
            }
        } elseif (-not $Force) {
            exit 1
        }
    }
    
    # Verify deployment if requested
    if ($Verify) {
        Write-Host "`nVerifying deployment..."
        $deploymentStatus = Test-Deployment -Environment $Environment
        if (-not $deploymentStatus) {
            Write-Host "Deployment verification failed" -ForegroundColor "Red"
            if (-not $Force) {
                exit 1
            }
        } else {
            Write-Host "Deployment verified successfully" -ForegroundColor "Green"
            Write-Host "State: $($deploymentStatus.State)"
            Write-Host "Environment: $($deploymentStatus.Environment)"
            Write-Host "Created: $($deploymentStatus.CreatedAt)"
            Write-Host "Updated: $($deploymentStatus.UpdatedAt)"
            Write-Host "Description: $($deploymentStatus.Description)"
        }
    }
    
    Write-Host "`nHealth Monitoring System deployed successfully!" -ForegroundColor "Green"
}
catch {
    Write-Log "Error during deployment: $_" -Level "ERROR" -Category "Deploy"
    exit 1
} 