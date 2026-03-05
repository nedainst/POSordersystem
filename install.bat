@echo off
chcp 65001 >nul 2>&1
title Warung Order System - Installer
color 0A

echo ╔══════════════════════════════════════════════════════════╗
echo ║      WARUNG ORDER SYSTEM - PORTABLE INSTALLER           ║
echo ║      Instalasi Pertama Kali                             ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

cd /d "%~dp0"

:: ======================================================
:: STEP 1: Cek / Download PHP Portable
:: ======================================================
echo [1/5] Mengecek PHP...

if exist "php\php.exe" (
    echo       PHP portable sudah ada.
    set "PHP_BIN=%~dp0php\php.exe"
    goto :check_php_extensions
)

where php >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo       PHP ditemukan di sistem.
    for /f "delims=" %%i in ('where php') do set "PHP_BIN=%%i"
    goto :check_php_extensions
)

echo       PHP tidak ditemukan! Mengunduh PHP portable...
echo.

:: Download PHP portable
if not exist "php" mkdir php

echo       Mengunduh PHP 8.3 (ini mungkin memakan waktu beberapa menit)...
powershell -Command "& { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; try { Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/php-8.3.16-nts-Win32-vs16-x64.zip' -OutFile 'php-portable.zip' -UseBasicParsing } catch { Write-Host 'Gagal mengunduh. Coba URL alternatif...'; Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/latest/php-8.3-nts-Win32-vs16-x64-latest.zip' -OutFile 'php-portable.zip' -UseBasicParsing } }"

if not exist "php-portable.zip" (
    echo.
    echo       ╔═══════════════════════════════════════════════════╗
    echo       ║  GAGAL mengunduh PHP!                             ║
    echo       ║                                                   ║
    echo       ║  Silakan download manual dari:                    ║
    echo       ║  https://windows.php.net/download/                ║
    echo       ║                                                   ║
    echo       ║  Download versi: VS16 x64 Non Thread Safe (ZIP)  ║
    echo       ║  Extract ke folder "php" di dalam folder ini.     ║
    echo       ║  Lalu jalankan install.bat lagi.                  ║
    echo       ╚═══════════════════════════════════════════════════╝
    echo.
    pause
    exit /b 1
)

echo       Mengekstrak PHP...
powershell -Command "Expand-Archive -Path 'php-portable.zip' -DestinationPath 'php' -Force"
del php-portable.zip >nul 2>&1

if exist "php\php.exe" (
    echo       PHP berhasil diinstall!
    set "PHP_BIN=%~dp0php\php.exe"
) else (
    echo       GAGAL mengekstrak PHP!
    pause
    exit /b 1
)

:check_php_extensions
echo.

:: ======================================================
:: STEP 2: Setup php.ini untuk PHP portable
:: ======================================================
echo [2/5] Mengonfigurasi PHP...

if exist "php\php.exe" (
    if not exist "php\php.ini" (
        if exist "php\php.ini-production" (
            copy "php\php.ini-production" "php\php.ini" >nul
        ) else (
            echo ; PHP Configuration for Warung Order System > "php\php.ini"
        )
        
        :: Enable necessary extensions
        powershell -Command "& { $ini = Get-Content 'php\php.ini' -Raw; $ini = $ini -replace ';extension_dir = \"ext\"', 'extension_dir = \"ext\"'; $ini = $ini -replace ';extension=curl', 'extension=curl'; $ini = $ini -replace ';extension=fileinfo', 'extension=fileinfo'; $ini = $ini -replace ';extension=gd', 'extension=gd'; $ini = $ini -replace ';extension=mbstring', 'extension=mbstring'; $ini = $ini -replace ';extension=openssl', 'extension=openssl'; $ini = $ini -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite'; $ini = $ini -replace ';extension=sqlite3', 'extension=sqlite3'; $ini = $ini -replace ';extension=zip', 'extension=zip'; $ini = $ini -replace ';extension=pdo_mysql', 'extension=pdo_mysql'; Set-Content 'php\php.ini' $ini }"
        echo       php.ini dikonfigurasi.
    ) else (
        echo       php.ini sudah ada.
    )
) else (
    echo       Menggunakan PHP sistem, pastikan ekstensi sqlite3 aktif.
)

echo.

:: ======================================================
:: STEP 3: Setup .env
:: ======================================================
echo [3/5] Mengatur konfigurasi aplikasi...

if not exist ".env.backup" (
    if exist ".env" (
        copy ".env" ".env.backup" >nul
        echo       Backup .env lama ke .env.backup
    )
)

copy ".env.portable" ".env" >nul
echo       Konfigurasi portable diterapkan.

:: Generate APP_KEY baru jika perlu
"%PHP_BIN%" artisan key:generate --force >nul 2>&1
echo       APP_KEY di-generate.

echo.

:: ======================================================
:: STEP 4: Setup Database SQLite
:: ======================================================
echo [4/5] Menyiapkan database...

if not exist "database\database.sqlite" (
    echo. > "database\database.sqlite"
    echo       File database SQLite dibuat.
    
    echo       Menjalankan migrasi database...
    "%PHP_BIN%" artisan migrate --force
    
    if %ERRORLEVEL% neq 0 (
        echo       GAGAL menjalankan migrasi!
        pause
        exit /b 1
    )
    
    echo       Mengisi data awal (seeder)...
    "%PHP_BIN%" artisan db:seed --force
    
    echo       Database siap!
) else (
    echo       Database sudah ada. Menjalankan migrasi baru jika ada...
    "%PHP_BIN%" artisan migrate --force
)

echo.

:: ======================================================
:: STEP 5: Optimasi Laravel
:: ======================================================
echo [5/5] Mengoptimasi aplikasi...

"%PHP_BIN%" artisan config:cache >nul 2>&1
"%PHP_BIN%" artisan route:cache >nul 2>&1
"%PHP_BIN%" artisan view:cache >nul 2>&1

:: Pastikan storage link ada
"%PHP_BIN%" artisan storage:link >nul 2>&1

echo       Optimasi selesai!

echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║                                                          ║
echo ║   ✓ INSTALASI SELESAI!                                  ║
echo ║                                                          ║
echo ║   Jalankan  start.bat  untuk memulai aplikasi.           ║
echo ║                                                          ║
echo ║   Login Admin:                                           ║
echo ║     Email    : admin@warung.com                          ║
echo ║     Password : password                                  ║
echo ║                                                          ║
echo ╚══════════════════════════════════════════════════════════╝
echo.
pause
