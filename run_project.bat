@echo off
title Laravel Project Launcher
color 0A

echo ===============================================
echo   Laravel Project Auto Setup & Runner
echo ===============================================
echo.

REM === Change this to your project folder path ===
set "PROJECT_PATH=C:\xampp\htdocs\Ampere-Office-Management"

REM === Change this to your GitHub repo branch name ===
set "BRANCH=main"

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
    goto check_mysql
)
echo MySQL service is now running!
echo.

REM === Go to project folder ===
cd /d "%PROJECT_PATH%"

REM === Pull latest changes from GitHub ===
echo Pulling latest updates from GitHub...
git pull origin %BRANCH%

REM === Install Composer dependencies ===
echo Installing Composer dependencies...
composer install

REM === Run database migrations ===
echo Running migrations...
php artisan migrate --force

REM === Clear and cache Laravel config/views/routes ===
php artisan optimize:clear
php artisan optimize

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
