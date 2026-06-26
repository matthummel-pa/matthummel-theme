# dev-start.ps1 — Start full matthummel-theme dev stack
# Usage: powershell -ExecutionPolicy Bypass -File dev-start.ps1
#
# Starts:
#   1. MySQL 8 on port 3306  (Local's bundled mysqld, no Local app needed)
#   2. PHP built-in server on localhost:8888  (serves WordPress)
#   3. Vite dev server on localhost:3000  (HMR for theme assets)

$MYSQL_BIN   = "C:\Users\mhumm\AppData\Roaming\Local\lightning-services\mysql-8.0.16+6\bin\win64\bin\mysqld.exe"
$MYSQL_CONF  = "C:\Users\mhumm\mysql-dev\my.cnf"
$PHP_BIN     = "C:\tools\php83\php.exe"
$WP_ROOT     = "C:\Users\mhumm\wordpress\app\public"
$THEME_DIR   = $PSScriptRoot

Write-Host "`n=== matthummel-theme dev stack ===" -ForegroundColor Cyan

# --- MySQL ---
$mysqlRunning = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
if ($mysqlRunning) {
    Write-Host "[mysql]  Already running on :3306" -ForegroundColor Green
} else {
    Write-Host "[mysql]  Starting mysqld on :3306..." -ForegroundColor Yellow
    Start-Process -FilePath $MYSQL_BIN -ArgumentList "--defaults-file=`"$MYSQL_CONF`"" -WindowStyle Hidden
    Start-Sleep -Seconds 3
    $check = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
    if ($check) {
        Write-Host "[mysql]  Running on :3306" -ForegroundColor Green
    } else {
        Write-Host "[mysql]  ERROR — failed to start. Check $MYSQL_CONF" -ForegroundColor Red
    }
}

# --- PHP built-in server ---
$phpRunning = Get-NetTCPConnection -LocalPort 8888 -State Listen -ErrorAction SilentlyContinue
if ($phpRunning) {
    Write-Host "[php]    Already running on :8888" -ForegroundColor Green
} else {
    Write-Host "[php]    Starting PHP server on localhost:8888..." -ForegroundColor Yellow
    Start-Process -FilePath $PHP_BIN `
        -ArgumentList "-S", "localhost:8888", "-t", $WP_ROOT, "$WP_ROOT\wp-server-router.php" `
        -WindowStyle Hidden
    Start-Sleep -Seconds 2
    $check = Get-NetTCPConnection -LocalPort 8888 -State Listen -ErrorAction SilentlyContinue
    if ($check) {
        Write-Host "[php]    WordPress at http://localhost:8888" -ForegroundColor Green
    } else {
        Write-Host "[php]    ERROR — failed to start PHP server" -ForegroundColor Red
    }
}

# --- Vite ---
$viteRunning = Get-NetTCPConnection -LocalPort 3000 -State Listen -ErrorAction SilentlyContinue
if ($viteRunning) {
    Write-Host "[vite]   Already running on :3000" -ForegroundColor Green
} else {
    Write-Host "[vite]   Starting Vite dev server on localhost:3000..." -ForegroundColor Yellow
    Start-Process -FilePath "cmd.exe" `
        -ArgumentList "/c", "cd /d `"$THEME_DIR`" && npm run dev" `
        -WindowStyle Normal
    Start-Sleep -Seconds 3
    Write-Host "[vite]   Vite at http://localhost:3000" -ForegroundColor Green
}

Write-Host "`n=== Dev stack ready ===" -ForegroundColor Cyan
Write-Host "  WordPress  : http://localhost:8888"
Write-Host "  WP Admin   : http://localhost:8888/wp-admin"
Write-Host "  Vite HMR   : http://localhost:3000"
Write-Host "  Theme dir  : $THEME_DIR`n"
