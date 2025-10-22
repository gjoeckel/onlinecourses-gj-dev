# Enhanced PHP Server Startup Script
# Provides better error logging and status monitoring

param(
    [int]$Port = 8000,
    [string]$ServerHost = "localhost",
    [switch]$Verbose
)

Write-Host "Starting PHP Development Server..." -ForegroundColor Green

# Check if port is already in use
$portCheck = netstat -an | findstr ":$Port"
if ($portCheck) {
    Write-Host "Port $Port is already in use. Stopping existing processes..." -ForegroundColor Yellow
    taskkill /F /IM php.exe 2>$null
    Start-Sleep -Seconds 2
}

# Create error log file if it doesn't exist
if (!(Test-Path "php_errors.log")) {
    New-Item -ItemType File -Name "php_errors.log" | Out-Null
}

# Enhanced PHP server startup with better error reporting
$phpArgs = @(
    "-S", "$ServerHost`:$Port",
    "-d", "error_reporting=E_ALL",
    "-d", "log_errors=1", 
    "-d", "error_log=php_errors.log",
    "-d", "display_errors=1",
    "-d", "display_startup_errors=1"
)

if ($Verbose) {
    $phpArgs += "-d", "display_errors=1"
    Write-Host "Starting with verbose logging..." -ForegroundColor Cyan
}

Write-Host "Server will be available at: http://$ServerHost`:$Port" -ForegroundColor Green
Write-Host "Health check available at: http://$ServerHost`:$Port/health_check.php" -ForegroundColor Cyan
Write-Host "Error log: php_errors.log" -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

# Start the server
php $phpArgs 