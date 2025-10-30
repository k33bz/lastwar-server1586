@echo off
REM Windows Batch Script for Running Admin Panel Unit Tests
REM Version: 1.0.0
REM Date: 2025-10-16

echo.
echo ===============================================
echo  Last War 1586 Admin Panel Unit Tests
echo  Role-Based Access Control Test Suite
echo ===============================================
echo.

REM Check if PHP is installed
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found in PATH
    echo Please install PHP or add it to your PATH
    pause
    exit /b 1
)

REM Display PHP version
echo Checking PHP version...
php -v
echo.

REM Run tests
echo Running tests...
echo.
php run-tests.php

REM Capture exit code
set TEST_EXIT_CODE=%ERRORLEVEL%

echo.
if %TEST_EXIT_CODE% EQU 0 (
    echo Tests completed successfully!
) else (
    echo Tests failed! Please review errors above.
)

pause
exit /b %TEST_EXIT_CODE%
