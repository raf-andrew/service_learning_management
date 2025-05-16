# GitHub Integration Module
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "logger.ps1")
. (Join-Path $scriptPath "environment.ps1")

# GitHub configuration
$githubConfig = @{
    API = "https://api.github.com"
    Web = "https://github.com"
    RequiredScopes = @("repo", "workflow", "admin:org", "admin:public_key")
}

# Check GitHub authentication
function Test-GitHubAuth {
    Write-Log "Checking GitHub authentication..." -Level "INFO" -Category "GitHub"
    
    try {
        $authStatus = gh auth status 2>&1
        if ($LASTEXITCODE -eq 0) {
            # Verify required scopes
            $token = gh auth token
            $headers = @{
                "Authorization" = "token $token"
                "Accept" = "application/vnd.github.v3+json"
            }
            
            $response = Invoke-RestMethod -Uri "$($githubConfig.API)/user" -Headers $headers
            $scopes = $response.headers["X-OAuth-Scopes"]
            
            $missingScopes = $githubConfig.RequiredScopes | Where-Object { $scopes -notmatch $_ }
            if ($missingScopes) {
                Write-Log "Missing required GitHub scopes: $($missingScopes -join ', ')" -Level "WARNING" -Category "GitHub"
                return $false
            }
            
            Write-Log "GitHub authentication valid with required scopes" -Level "SUCCESS" -Category "GitHub"
            return $true
        }
        
        Write-Log "GitHub authentication failed" -Level "ERROR" -Category "GitHub"
        return $false
    }
    catch {
        Write-Log "Error checking GitHub authentication: $_" -Level "ERROR" -Category "GitHub"
        return $false
    }
}

# Deploy to GitHub
function Start-GitHubDeploy {
    param (
        [string]$Branch = "main",
        [string]$Environment = "production",
        [hashtable]$Variables = @{}
    )
    
    Write-Log "Starting GitHub deployment..." -Level "INFO" -Category "GitHub"
    
    try {
        # Check if we're in a git repository
        if (-not (Test-Path ".git")) {
            Write-Log "Not in a git repository" -Level "ERROR" -Category "GitHub"
            return $false
        }
        
        # Check for uncommitted changes
        $status = git status --porcelain
        if ($status) {
            Write-Log "Uncommitted changes found. Please commit or stash changes before deploying." -Level "WARNING" -Category "GitHub"
            return $false
        }
        
        # Push changes
        git push origin $Branch
        if ($LASTEXITCODE -ne 0) {
            Write-Log "Failed to push changes" -Level "ERROR" -Category "GitHub"
            return $false
        }
        
        # Create deployment
        $deployment = @{
            ref = $Branch
            environment = $Environment
            auto_merge = $false
            required_contexts = @()
        }
        
        if ($Variables.Count -gt 0) {
            $deployment.payload = $Variables
        }
        
        $token = gh auth token
        $headers = @{
            "Authorization" = "token $token"
            "Accept" = "application/vnd.github.v3+json"
        }
        
        $repo = git config --get remote.origin.url | ForEach-Object { $_ -replace ".*[:/]([^/]+/[^/]+)\.git$", '$1' }
        $response = Invoke-RestMethod -Uri "$($githubConfig.API)/repos/$repo/deployments" -Method Post -Headers $headers -Body ($deployment | ConvertTo-Json)
        
        Write-Log "Deployment created: $($response.url)" -Level "SUCCESS" -Category "GitHub"
        return $true
    }
    catch {
        Write-Log "Error during deployment: $_" -Level "ERROR" -Category "GitHub"
        return $false
    }
}

# Check GitHub Actions status
function Get-GitHubActionsStatus {
    param (
        [string]$Branch = "main",
        [int]$Limit = 5
    )
    
    Write-Log "Checking GitHub Actions status..." -Level "INFO" -Category "GitHub"
    
    try {
        $token = gh auth token
        $headers = @{
            "Authorization" = "token $token"
            "Accept" = "application/vnd.github.v3+json"
        }
        
        $repo = git config --get remote.origin.url | ForEach-Object { $_ -replace ".*[:/]([^/]+/[^/]+)\.git$", '$1' }
        $response = Invoke-RestMethod -Uri "$($githubConfig.API)/repos/$repo/actions/runs?branch=$Branch&per_page=$Limit" -Headers $headers
        
        $status = @{
            Total = $response.total_count
            Successful = ($response.workflow_runs | Where-Object { $_.conclusion -eq "success" }).Count
            Failed = ($response.workflow_runs | Where-Object { $_.conclusion -eq "failure" }).Count
            InProgress = ($response.workflow_runs | Where-Object { $_.status -eq "in_progress" }).Count
            Latest = $response.workflow_runs[0]
        }
        
        Write-Log "GitHub Actions status retrieved" -Level "SUCCESS" -Category "GitHub"
        return $status
    }
    catch {
        Write-Log "Error checking GitHub Actions status: $_" -Level "ERROR" -Category "GitHub"
        return $null
    }
}

# Export functions
Export-ModuleMember -Function Test-GitHubAuth, Start-GitHubDeploy, Get-GitHubActionsStatus 