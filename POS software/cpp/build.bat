@echo off
echo ============================================
echo   POS System - C++ Qt Build Script
echo ============================================
echo.

REM ── Check for Qt installation ──────────────────
set QT_PATHS=C:\Qt\6.8.0\msvc2022_64;C:\Qt\6.7.3\msvc2022_64;C:\Qt\6.7.2\msvc2022_64;C:\Qt\6.7.1\msvc2022_64;C:\Qt\6.7.0\msvc2022_64;C:\Qt\6.6.3\msvc2019_64;C:\Qt\6.5.3\msvc2019_64;C:\Qt\6.8.0\mingw_64;C:\Qt\6.7.3\mingw_64;C:\Qt\6.7.0\mingw_64;C:\Qt\6.6.3\mingw_64

set QT_DIR=
for %%P in (%QT_PATHS%) do (
    if exist "%%P\bin\qmake.exe" (
        set QT_DIR=%%P
        goto :found_qt
    )
)

echo [ERROR] Qt6 not found! 
echo Please install Qt6 from https://www.qt.io/download
echo.
echo After installing, set the CMAKE_PREFIX_PATH:
echo   set CMAKE_PREFIX_PATH=C:\Qt\6.x.x\msvc2022_64
echo   build.bat
echo.
pause
exit /b 1

:found_qt
echo [OK] Found Qt at: %QT_DIR%
set CMAKE_PREFIX_PATH=%QT_DIR%

REM ── Create build directory ─────────────────────
if not exist "build" mkdir build
cd build

REM ── Configure with CMake ───────────────────────
echo.
echo [1/2] Configuring with CMake...
cmake .. -DCMAKE_PREFIX_PATH="%QT_DIR%" -DCMAKE_BUILD_TYPE=Release
if errorlevel 1 (
    echo [ERROR] CMake configuration failed!
    cd ..
    pause
    exit /b 1
)

REM ── Build ──────────────────────────────────────
echo.
echo [2/2] Building...
cmake --build . --config Release
if errorlevel 1 (
    echo [ERROR] Build failed!
    cd ..
    pause
    exit /b 1
)

echo.
echo ============================================
echo   Build successful!
echo   Executable: build\Release\POSSystem.exe
echo ============================================
echo.

REM ── Deploy Qt DLLs ────────────────────────────
echo Deploying Qt dependencies...
if exist "Release\POSSystem.exe" (
    "%QT_DIR%\bin\windeployqt.exe" Release\POSSystem.exe
    echo [OK] Qt dependencies deployed.
) else if exist "POSSystem.exe" (
    "%QT_DIR%\bin\windeployqt.exe" POSSystem.exe
    echo [OK] Qt dependencies deployed.
)

cd ..
echo.
echo Done! You can now run the application.
pause
