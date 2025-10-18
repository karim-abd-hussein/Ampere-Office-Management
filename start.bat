@echo off
title Laravel Project Launcher
color 0A

echo ===============================================
echo   Laravel Project Auto Setup
echo ===============================================
echo.

REM === Change this to your project folder path ===
set "PROJECT_PATH=C:\xampp\htdocs\Ampere-Office-Management"

REM === Start MySQL Service Automatically ===
echo Starting MySQL service...
cd /d "C:\xampp"

REM Option 1: Use XAMPP's internal service control
xampp_start.exe >nul 2>&1

REM Option 2: Alternatively use Windows Service directly (uncomment if needed)
REM net start mysql >nul 2>&1

REM Wait until MySQL is actually running
echo Waiting for MySQL to start...
:check_mysql
sc query mysql | find "RUNNING" >nul
if errorlevel 1 (
    echo MySQL not ready yet... waiting 3 seconds.
    timeout /t 3 >nul
   REM goto check_mysql
)
echo MySQL service is now running!
echo.

REM === Go to project folder ===
cd /d "%PROJECT_PATH%"

REM === Start Laravel development server ===
echo Starting Laravel server...
start "" php artisan serve

REM === Wait a few seconds to ensure the server starts ===
timeout /t 5 >nul

REM === Open the browser ===
echo Opening browser...
start http://127.0.0.1:8000

echo.
echo All done! The project should now be running.
pause