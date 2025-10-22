# Server Diagnostic Script
# Provides comprehensive server health and configuration analysis

param(
    [string]$ServerUrl = "http://localhost:8000",
    [switch]$Detailed
)

Write-Host "=== Server Diagnostic Report ===" -ForegroundColor Cyan
Write-Host "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor White
Write-Host ""

# 1. Check if server is running
Write-Host "1. Server Status Check..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$ServerUrl/health_check.php" -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "OK Server is running and responding" -ForegroundColor Green
        $healthData = $response.Content | ConvertFrom-Json
        
        Write-Host "   PHP Version: $($healthData.php_version)" -ForegroundColor White
        Write-Host "   Server Software: $($healthData.server_software)" -ForegroundColor White
        Write-Host "   Memory Limit: $($healthData.memory_limit)" -ForegroundColor White
        Write-Host "   Max Execution Time: $($healthData.max_execution_time)s" -ForegroundColor White
        
        # Check extensions
        Write-Host "   Required Extensions:" -ForegroundColor White
        foreach ($ext in $healthData.loaded_extensions.PSObject.Properties) {
            $status = if ($ext.Value) { "OK" } else { "FAIL" }
            $color = if ($ext.Value) { "Green" } else { "Red" }
            Write-Host "     $status $($ext.Name)" -ForegroundColor $color
        }
        
        # Check file permissions
        Write-Host "   File Permissions:" -ForegroundColor White
        foreach ($perm in $healthData.file_permissions.PSObject.Properties) {
            $status = if ($perm.Value) { "OK" } else { "FAIL" }
            $color = if ($perm.Value) { "Green" } else { "Red" }
            Write-Host "     $status $($perm.Name)" -ForegroundColor $color
        }
        
        # Check enterprise configs
        if ($healthData.enterprise_configs.Count -gt 0) {
            Write-Host "   Enterprise Configs Found: $($healthData.enterprise_configs.Count)" -ForegroundColor Green
            foreach ($config in $healthData.enterprise_configs) {
                Write-Host "     - $config" -ForegroundColor White
            }
        } else {
            Write-Host "   ⚠ No enterprise configs found" -ForegroundColor Yellow
        }
        
    } else {
        Write-Host "FAIL Server responded with status: $($response.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "FAIL Server is not responding: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# 2. Check port status
Write-Host "2. Port Status Check..." -ForegroundColor Yellow
$portStatus = netstat -an | findstr ":8000"
if ($portStatus) {
    Write-Host "OK Port 8000 is in use" -ForegroundColor Green
    Write-Host "   $portStatus" -ForegroundColor White
} else {
    Write-Host "FAIL Port 8000 is not in use" -ForegroundColor Red
}

Write-Host ""

# 3. Check PHP processes
Write-Host "3. PHP Process Check..." -ForegroundColor Yellow
$phpProcesses = Get-Process php -ErrorAction SilentlyContinue
if ($phpProcesses) {
    Write-Host "OK PHP processes running: $($phpProcesses.Count)" -ForegroundColor Green
    foreach ($proc in $phpProcesses) {
        Write-Host "   PID: $($proc.Id), CPU: $([math]::Round($proc.CPU, 2))s, Memory: $([math]::Round($proc.WorkingSet64/1MB, 2))MB" -ForegroundColor White
    }
} else {
    Write-Host "FAIL No PHP processes found" -ForegroundColor Red
}

Write-Host ""

# 4. Check error logs
Write-Host "4. Error Log Check..." -ForegroundColor Yellow
if (Test-Path "php_errors.log") {
    $errorCount = (Get-Content "php_errors.log" | Measure-Object -Line).Lines
    Write-Host "OK Error log exists with $errorCount lines" -ForegroundColor Green
    
    if ($Detailed -and $errorCount -gt 0) {
        Write-Host "   Recent errors:" -ForegroundColor White
        Get-Content "php_errors.log" -Tail 5 | ForEach-Object {
            Write-Host "     $_" -ForegroundColor White
        }
    }
} else {
    Write-Host "⚠ No error log file found" -ForegroundColor Yellow
}

Write-Host ""

# 5. Check critical files
Write-Host "5. Critical Files Check..." -ForegroundColor Yellow
$criticalFiles = @(
    "login.php",
    "dashboard.php", 

    "config/dashboards.json",
    "config/passwords.json"
)

foreach ($file in $criticalFiles) {
    if (Test-Path $file) {
        Write-Host "   OK $file" -ForegroundColor Green
    } else {
        Write-Host "   FAIL $file (missing)" -ForegroundColor Red
    }
}

Write-Host ""

# 6. Test main endpoints
Write-Host "6. Endpoint Testing..." -ForegroundColor Yellow
$endpoints = @(
    "login.php",
    "dashboard.php",
    "reports/index.php"
)

foreach ($endpoint in $endpoints) {
    try {
        $response = Invoke-WebRequest -Uri "$ServerUrl/$endpoint" -UseBasicParsing -TimeoutSec 5
        Write-Host "   OK $endpoint ($($response.StatusCode))" -ForegroundColor Green
    } catch {
        Write-Host "   FAIL $endpoint (error: $($_.Exception.Message))" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== Diagnostic Complete ===" -ForegroundColor Cyan 