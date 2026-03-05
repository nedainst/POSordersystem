@echo off
chcp 65001 >nul 2>&1
title Warung Order System
color 0B

echo ╔══════════════════════════════════════════════════════════╗
echo ║          WARUNG ORDER SYSTEM                             ║
echo ║          Memulai server...                               ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

cd /d "%~dp0"

:: ======================================================
:: Cari PHP executable
:: ======================================================
set "PHP_BIN="

if exist "php\php.exe" (
    set "PHP_BIN=%~dp0php\php.exe"
    echo [✓] Menggunakan PHP portable
) else (
    where php >nul 2>&1
    if %ERRORLEVEL% equ 0 (
        for /f "delims=" %%i in ('where php') do (
            if not defined PHP_BIN set "PHP_BIN=%%i"
        )
        echo [✓] Menggunakan PHP sistem
    )
)

if not defined PHP_BIN (
    echo [✗] PHP tidak ditemukan!
    echo     Jalankan install.bat terlebih dahulu.
    pause
    exit /b 1
)

:: ======================================================
:: Cek apakah sudah di-install
:: ======================================================
if not exist ".env" (
    echo [!] Aplikasi belum di-setup.
    echo     Menjalankan install.bat...
    echo.
    call install.bat
    if %ERRORLEVEL% neq 0 exit /b 1
)

if not exist "database\database.sqlite" (
    echo [!] Database belum dibuat.
    echo     Menjalankan install.bat...
    echo.
    call install.bat
    if %ERRORLEVEL% neq 0 exit /b 1
)

:: ======================================================
:: Cek port yang tersedia
:: ======================================================
set "APP_PORT=8080"
set "REVERB_PORT=8090"

netstat -an | findstr ":8080 " | findstr "LISTENING" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo [!] Port 8080 sedang digunakan, mencoba port 8888...
    set "APP_PORT=8888"
)

netstat -an | findstr ":8090 " | findstr "LISTENING" >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo [!] Port 8090 sedang digunakan, mencoba port 8091...
    set "REVERB_PORT=8091"
)

:: ======================================================
:: Start Laravel Reverb (WebSocket) di background
:: ======================================================
echo.
echo [~] Memulai WebSocket server (Reverb) di port %REVERB_PORT%...
start /b "Reverb" cmd /c ""%PHP_BIN%" artisan reverb:start --port=%REVERB_PORT% >nul 2>&1"

:: Tunggu sebentar
timeout /t 2 /nobreak >nul

:: ======================================================
:: Buka browser
:: ======================================================
echo [~] Membuka browser...
timeout /t 1 /nobreak >nul
start http://localhost:%APP_PORT%

:: ======================================================
:: Start Laravel Server (foreground)
:: ======================================================
echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║                                                          ║
echo ║   Warung Order System BERJALAN!                          ║
echo ║                                                          ║
echo ║   Buka browser: http://localhost:%APP_PORT%                    ║
echo ║   Admin panel : http://localhost:%APP_PORT%/admin              ║
echo ║                                                          ║
echo ║   Login: admin@warung.com / password                     ║
echo ║                                                          ║
echo ║   Tekan Ctrl+C untuk menghentikan server                 ║
echo ║                                                          ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

"%PHP_BIN%" artisan serve --port=%APP_PORT% --host=0.0.0.0

:: Ketika server berhenti, kill Reverb juga
echo.
echo [~] Menghentikan semua proses...
taskkill /f /fi "WINDOWTITLE eq Reverb" >nul 2>&1
echo [✓] Server dihentikan.
pause
