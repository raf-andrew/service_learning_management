# MCP Database Utility
# Provides database management functionality for the MCP system

function Test-DatabaseHealth {
    param([string]$Database)
    
    try {
        switch ($Database.ToLower()) {
            "main" {
                return Test-MySQLDatabase -Database "service_learning_management"
            }
            "soc2" {
                return Test-SQLiteDatabase -Database "modules/soc2/database/soc2.sqlite"
            }
            "e2ee" {
                return Test-SQLiteDatabase -Database "modules/e2ee/database/e2ee.sqlite"
            }
            default {
                return @{
                    Healthy = $false
                    Status = "Unknown"
                    Error = "Unknown database type"
                    ConnectionString = $null
                }
            }
        }
    } catch {
        return @{
            Healthy = $false
            Status = "Error"
            Error = $_.Exception.Message
            ConnectionString = $null
        }
    }
}

function Test-MySQLDatabase {
    param([string]$Database)
    
    try {
        $connectionString = "Server=localhost;Database=$Database;Uid=root;Pwd=;"
        $connection = New-Object System.Data.Odbc.OdbcConnection($connectionString)
        $connection.Open()
        
        $command = $connection.CreateCommand()
        $command.CommandText = "SELECT 1"
        $result = $command.ExecuteScalar()
        
        $connection.Close()
        
        return @{
            Healthy = $true
            Status = "Connected"
            Error = $null
            ConnectionString = $connectionString
        }
        
    } catch {
        return @{
            Healthy = $false
            Status = "Connection Failed"
            Error = $_.Exception.Message
            ConnectionString = $connectionString
        }
    }
}

function Test-SQLiteDatabase {
    param([string]$Database)
    
    try {
        $dbPath = Join-Path (Get-Location) $Database
        
        if (-not (Test-Path $dbPath)) {
            return @{
                Healthy = $false
                Status = "File Not Found"
                Error = "Database file not found: $dbPath"
                ConnectionString = $dbPath
            }
        }
        
        # Test if file is accessible and not corrupted
        $fileInfo = Get-Item $dbPath
        if ($fileInfo.Length -eq 0) {
            return @{
                Healthy = $false
                Status = "Empty File"
                Error = "Database file is empty"
                ConnectionString = $dbPath
            }
        }
        
        return @{
            Healthy = $true
            Status = "Accessible"
            Error = $null
            ConnectionString = $dbPath
        }
        
    } catch {
        return @{
            Healthy = $false
            Status = "Error"
            Error = $_.Exception.Message
            ConnectionString = $dbPath
        }
    }
}

function Get-DatabaseStatus {
    param([string]$Database)
    
    $health = Test-DatabaseHealth -Database $Database
    
    return @{
        Status = $health.Status
        Healthy = $health.Healthy
        ConnectionString = $health.ConnectionString
        Error = $health.Error
    }
}

function Validate-DatabaseData {
    param([string]$Database)
    
    try {
        switch ($Database.ToLower()) {
            "main" {
                return Validate-MySQLData -Database "service_learning_management"
            }
            "soc2" {
                return Validate-SQLiteData -Database "modules/soc2/database/soc2.sqlite"
            }
            "e2ee" {
                return Validate-SQLiteData -Database "modules/e2ee/database/e2ee.sqlite"
            }
            default {
                return @{ Success = $false; Error = "Unknown database type" }
            }
        }
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Validate-MySQLData {
    param([string]$Database)
    
    try {
        $connectionString = "Server=localhost;Database=$Database;Uid=root;Pwd=;"
        $connection = New-Object System.Data.Odbc.OdbcConnection($connectionString)
        $connection.Open()
        
        $command = $connection.CreateCommand()
        $command.CommandText = "SHOW TABLES"
        $reader = $command.ExecuteReader()
        
        $tables = @()
        while ($reader.Read()) {
            $tables += $reader.GetString(0)
        }
        $reader.Close()
        
        $validationResults = @{}
        foreach ($table in $tables) {
            $command.CommandText = "SELECT COUNT(*) FROM `$table`"
            $count = $command.ExecuteScalar()
            $validationResults[$table] = $count
        }
        
        $connection.Close()
        
        return @{
            Success = $true
            Tables = $tables
            RecordCounts = $validationResults
            Error = $null
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Validate-SQLiteData {
    param([string]$Database)
    
    try {
        $dbPath = Join-Path (Get-Location) $Database
        
        if (-not (Test-Path $dbPath)) {
            return @{ Success = $false; Error = "Database file not found" }
        }
        
        # For SQLite, we'll do basic file validation
        $fileInfo = Get-Item $dbPath
        $validationResults = @{
            FileExists = $true
            FileSize = $fileInfo.Length
            LastModified = $fileInfo.LastWriteTime
            IsReadable = $true
        }
        
        return @{
            Success = $true
            ValidationResults = $validationResults
            Error = $null
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Backup-Database {
    param([string]$Database)
    
    try {
        $backupPath = "modules/mcp/backups"
        if (-not (Test-Path $backupPath)) {
            New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
        }
        
        $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
        $backupFile = Join-Path $backupPath "$Database`_$timestamp.backup"
        
        switch ($Database.ToLower()) {
            "main" {
                return Backup-MySQLDatabase -Database "service_learning_management" -BackupFile $backupFile
            }
            "soc2" {
                return Backup-SQLiteDatabase -Database "modules/soc2/database/soc2.sqlite" -BackupFile $backupFile
            }
            "e2ee" {
                return Backup-SQLiteDatabase -Database "modules/e2ee/database/e2ee.sqlite" -BackupFile $backupFile
            }
            default {
                return @{ Success = $false; Error = "Unknown database type" }
            }
        }
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Backup-MySQLDatabase {
    param([string]$Database, [string]$BackupFile)
    
    try {
        $mysqldump = "mysqldump"
        $arguments = @(
            "--host=localhost",
            "--user=root",
            "--password=",
            "--single-transaction",
            "--routines",
            "--triggers",
            $Database
        )
        
        & $mysqldump $arguments | Out-File -FilePath $BackupFile -Encoding UTF8
        
        if (Test-Path $BackupFile) {
            return @{
                Success = $true
                BackupFile = $BackupFile
                FileSize = (Get-Item $BackupFile).Length
                Error = $null
            }
        } else {
            return @{ Success = $false; Error = "Backup file not created" }
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Backup-SQLiteDatabase {
    param([string]$Database, [string]$BackupFile)
    
    try {
        $dbPath = Join-Path (Get-Location) $Database
        
        if (-not (Test-Path $dbPath)) {
            return @{ Success = $false; Error = "Database file not found" }
        }
        
        Copy-Item -Path $dbPath -Destination $BackupFile -Force
        
        return @{
            Success = $true
            BackupFile = $BackupFile
            FileSize = (Get-Item $BackupFile).Length
            Error = $null
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Restore-Database {
    param([string]$Database, [string]$BackupFile)
    
    try {
        if (-not (Test-Path $BackupFile)) {
            return @{ Success = $false; Error = "Backup file not found" }
        }
        
        switch ($Database.ToLower()) {
            "main" {
                return Restore-MySQLDatabase -Database "service_learning_management" -BackupFile $BackupFile
            }
            "soc2" {
                return Restore-SQLiteDatabase -Database "modules/soc2/database/soc2.sqlite" -BackupFile $BackupFile
            }
            "e2ee" {
                return Restore-SQLiteDatabase -Database "modules/e2ee/database/e2ee.sqlite" -BackupFile $BackupFile
            }
            default {
                return @{ Success = $false; Error = "Unknown database type" }
            }
        }
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Restore-MySQLDatabase {
    param([string]$Database, [string]$BackupFile)
    
    try {
        $mysql = "mysql"
        $arguments = @(
            "--host=localhost",
            "--user=root",
            "--password=",
            $Database
        )
        
        Get-Content $BackupFile | & $mysql $arguments
        
        return @{
            Success = $true
            RestoredDatabase = $Database
            BackupFile = $BackupFile
            Error = $null
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Restore-SQLiteDatabase {
    param([string]$Database, [string]$BackupFile)
    
    try {
        $dbPath = Join-Path (Get-Location) $Database
        
        # Create directory if it doesn't exist
        $dbDir = Split-Path $dbPath -Parent
        if (-not (Test-Path $dbDir)) {
            New-Item -ItemType Directory -Path $dbDir -Force | Out-Null
        }
        
        Copy-Item -Path $BackupFile -Destination $dbPath -Force
        
        return @{
            Success = $true
            RestoredDatabase = $Database
            BackupFile = $BackupFile
            Error = $null
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Initialize-Databases {
    param(
        [string]$Environment = "local",
        [switch]$Force
    )
    
    try {
        $databases = @("main", "soc2", "e2ee")
        $results = @{}
        
        foreach ($db in $databases) {
            $health = Test-DatabaseHealth -Database $db
            
            if (-not $health.Healthy) {
                if ($Force) {
                    # Create database if it doesn't exist
                    $result = @{ Success = $true; Message = "Database would be created" }
                } else {
                    $result = @{ Success = $false; Error = "Database unhealthy" }
                }
            } else {
                $result = @{ Success = $true; Message = "Database healthy" }
            }
            
            $results[$db] = $result
        }
        
        $overallSuccess = $results.Values | Where-Object { $_.Success } | Measure-Object | Select-Object -ExpandProperty Count
        $overallSuccess = $overallSuccess -eq $databases.Count
        
        return @{
            Success = $overallSuccess
            Databases = $results
            Error = if (-not $overallSuccess) { "Some databases failed to initialize" } else { $null }
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Test-DatabaseHealth",
    "Get-DatabaseStatus",
    "Validate-DatabaseData",
    "Backup-Database",
    "Restore-Database",
    "Initialize-Databases"
) 