@echo off
echo ============================================
echo   POS System - Go + Fyne Build Script
echo ============================================
echo.

REM Check if Go is installed
where go >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Go is not installed or not in PATH!
    echo Download Go from: https://go.dev/dl/
    pause
    exit /b 1
)

echo [1/4] Go version:
go version
echo.

REM Check for GCC (required for go-sqlite3 CGO)
where gcc >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [WARNING] GCC not found! Required for go-sqlite3 (CGO).
    echo Install MinGW-w64 or TDM-GCC from:
    echo   https://jmeubank.github.io/tdm-gcc/
    echo   or: https://www.mingw-w64.org/
    echo.
    echo After installing, ensure gcc is in your PATH.
    pause
    exit /b 1
)

echo [2/4] Downloading dependencies...
set CGO_ENABLED=1
go mod tidy
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Failed to download dependencies!
    pause
    exit /b 1
)
echo.

echo [3/4] Building POS System...
go build -ldflags="-s -w -H windowsgui" -o pos-system.exe .
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Build failed!
    pause
    exit /b 1
)
echo.

echo [4/4] Build successful!
echo.
echo ============================================
echo   Output: pos-system.exe
echo   Size:
for %%I in (pos-system.exe) do echo     %%~zI bytes
echo.
echo   To run: pos-system.exe
echo   Default login: admin / admin123
echo ============================================
echo.

set /p run="Run now? (y/n): "
if /i "%run%"=="y" (
    start "" pos-system.exe
)
