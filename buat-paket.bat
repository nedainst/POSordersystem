@echo off
chcp 65001 >nul 2>&1
title Warung Order System - Buat Paket Distribusi
color 0E

echo ╔══════════════════════════════════════════════════════════╗
echo ║      WARUNG ORDER SYSTEM - BUAT PAKET DISTRIBUSI        ║
echo ║      Membuat file ZIP siap pakai                        ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

cd /d "%~dp0"

:: Nama output
set "OUTPUT=WarungOrderSystem-Portable.zip"

echo [1/3] Membersihkan cache sementara...

:: Cari PHP
set "PHP_BIN="
if exist "php\php.exe" (
    set "PHP_BIN=%~dp0php\php.exe"
) else (
    where php >nul 2>&1
    if %ERRORLEVEL% equ 0 (
        for /f "delims=" %%i in ('where php') do (
            if not defined PHP_BIN set "PHP_BIN=%%i"
        )
    )
)

if defined PHP_BIN (
    "%PHP_BIN%" artisan config:clear >nul 2>&1
    "%PHP_BIN%" artisan route:clear >nul 2>&1
    "%PHP_BIN%" artisan view:clear >nul 2>&1
    "%PHP_BIN%" artisan cache:clear >nul 2>&1
)

echo [2/3] Membuat paket %OUTPUT%...
echo        (ini mungkin memakan waktu beberapa menit)
echo.

:: Buat ZIP menggunakan PowerShell (exclude file-file yang tidak perlu)
powershell -Command "& { $source = '%~dp0'; $dest = Join-Path $source '%OUTPUT%'; if (Test-Path $dest) { Remove-Item $dest -Force }; $tempDir = Join-Path $env:TEMP 'warung-package'; if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }; New-Item -ItemType Directory -Path $tempDir -Force | Out-Null; $excludeDirs = @('.git', 'node_modules', '.idea', '.vscode', 'tests', 'storage\logs', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views'); $excludeFiles = @('.env', '.env.backup', '.gitignore', '.gitattributes', '%OUTPUT%', 'php-portable.zip'); Write-Host '       Copiando archivos...'; $items = Get-ChildItem -Path $source -Force; foreach ($item in $items) { $relPath = $item.Name; $skip = $false; foreach ($exc in $excludeDirs) { if ($relPath -eq $exc) { $skip = $true; break } }; foreach ($exc in $excludeFiles) { if ($relPath -eq $exc) { $skip = $true; break } }; if (-not $skip) { if ($item.PSIsContainer) { Copy-Item $item.FullName (Join-Path $tempDir $relPath) -Recurse -Force } else { Copy-Item $item.FullName (Join-Path $tempDir $relPath) -Force } } }; $logsDir = Join-Path $tempDir 'storage\logs'; New-Item -ItemType Directory -Path $logsDir -Force | Out-Null; New-Item -ItemType File -Path (Join-Path $logsDir '.gitkeep') -Force | Out-Null; $cacheDir = Join-Path $tempDir 'storage\framework\cache\data'; New-Item -ItemType Directory -Path $cacheDir -Force | Out-Null; $sessDir = Join-Path $tempDir 'storage\framework\sessions'; New-Item -ItemType Directory -Path $sessDir -Force | Out-Null; $viewsDir = Join-Path $tempDir 'storage\framework\views'; New-Item -ItemType Directory -Path $viewsDir -Force | Out-Null; $dbFile = Join-Path $tempDir 'database\database.sqlite'; if (Test-Path $dbFile) { Remove-Item $dbFile -Force }; Write-Host '       Compressing...'; Compress-Archive -Path (Join-Path $tempDir '*') -DestinationPath $dest -CompressionLevel Optimal; Remove-Item $tempDir -Recurse -Force; Write-Host '       Done!' }"

echo.
echo [3/3] Selesai!
echo.

if exist "%OUTPUT%" (
    echo ╔══════════════════════════════════════════════════════════╗
    echo ║                                                          ║
    echo ║   ✓ Paket distribusi berhasil dibuat!                   ║
    echo ║                                                          ║
    echo ║   File: %OUTPUT%                         ║
    echo ║                                                          ║
    echo ║   Cara distribusi:                                       ║
    echo ║   1. Kirim file ZIP ke komputer tujuan                   ║
    echo ║   2. Extract ZIP nya                                     ║
    echo ║   3. Jalankan install.bat                                ║
    echo ║   4. Jalankan start.bat                                  ║
    echo ║                                                          ║
    echo ╚══════════════════════════════════════════════════════════╝
) else (
    echo [✗] Gagal membuat paket!
)

echo.
pause
