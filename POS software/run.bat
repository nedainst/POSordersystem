@echo off
echo ============================================
echo   POS System - Point of Sale
echo ============================================
echo.

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python tidak ditemukan! Install Python terlebih dahulu.
    pause
    exit /b 1
)

REM Install dependencies
echo [INFO] Menginstall dependencies...
pip install -r requirements.txt >nul 2>&1
if errorlevel 1 (
    echo [WARN] Gagal install dari requirements.txt, mencoba install manual...
    pip install customtkinter >nul 2>&1
)

echo [INFO] Dependencies terinstall.
echo.

REM Run application
echo [INFO] Menjalankan POS System...
echo.
python main.py

pause
