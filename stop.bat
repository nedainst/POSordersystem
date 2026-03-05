@echo off
chcp 65001 >nul 2>&1
title Warung Order System - Stop
color 0C

echo ╔══════════════════════════════════════════════════════════╗
echo ║          WARUNG ORDER SYSTEM - STOP                      ║
echo ╚══════════════════════════════════════════════════════════╝
echo.

echo [~] Menghentikan server Laravel...
taskkill /f /fi "WINDOWTITLE eq Warung Order System" >nul 2>&1

echo [~] Menghentikan Reverb WebSocket...
taskkill /f /fi "WINDOWTITLE eq Reverb" >nul 2>&1

:: Kill any php artisan serve processes
taskkill /f /im php.exe /fi "WINDOWTITLE eq Warung*" >nul 2>&1

echo.
echo [✓] Semua server dihentikan.
echo.
timeout /t 3
