@echo off
title Stop Laravel Project + XAMPP Services
color 0C

echo ===============================================
echo   Stopping Laravel Project & XAMPP Services
echo ===============================================
echo.

REM === Kill Laravel development server (php artisan serve) ===
echo Stopping Laravel development server...
taskkill /f /im php.exe >nul 2>&1
if %errorlevel%==0 (
    echo Laravel server stopped successfully.
) else (
    echo Laravel server was not running or already stopped.
)

REM === Stop Apache service started by XAMPP ===
echo Stopping Apache server...
taskkill /f /im httpd.exe >nul 2>&1
if %errorlevel%==0 (
    echo Apache server stopped successfully.
) else (
    echo Apache server was not running or already stopped.
)

REM === Stop MySQL service started by XAMPP ===
echo Stopping MySQL server...
taskkill /f /im mysqld.exe >nul 2>&1
if %errorlevel%==0 (
    echo MySQL server stopped successfully.
) else (
    echo MySQL server was not running or already stopped.
)

REM === Stop xampp_start.exe if still running ===
echo Stopping xampp_start.exe (if running)...
taskkill /f /im xampp_start.exe >nul 2>&1
if %errorlevel%==0 (
    echo xampp_start.exe stopped.
) else (
    echo xampp_start.exe was not running.
)
echo.
echo All services have been stopped.
pause
