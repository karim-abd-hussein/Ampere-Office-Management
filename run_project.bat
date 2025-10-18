@echo off
title Laravel Project Launcher
color 0A

echo ===============================================
echo   Laravel Project Auto Setup
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
REM :check_mysql
REM sc query mysql | find "RUNNING" >nul
REM if errorlevel 1 (
    echo MySQL not ready yet... waiting 5 seconds.
    timeout /t 5 >nul
   REM goto check_mysql
REM )
echo MySQL service is now running!
echo.

REM === Go to project folder ===
cd /d "%PROJECT_PATH%"

REM === Pull latest changes from GitHub ===
echo Pulling latest updates from GitHub...
git pull origin %BRANCH%

REM === Install Composer dependencies ===
echo Installing Composer dependencies...
call composer install

REM === Run database migrations ===
echo Running migrations...
call php artisan migrate --force
echo Migrations completed!

REM === Clear and cache Laravel config/views/routes ===
echo Clearing and caching config/views/routes...
call php artisan optimize:clear
call php artisan optimize
echo Config/views/routes cleared and optimized!

REM === Run Filament Upgrade ===
REM echo Running Filament upgrade...
REM call php artisan filament:upgrade
REM echo Filament upgrade completed!

REM === Start Laravel development server ===
echo Starting Laravel server...
start "" cmd /c "php artisan serve --port=8000"

REM === Wait a few seconds to ensure the server starts ===
timeout /t 5 >nul

REM === Open the browser ===
echo Opening browser...
start http://127.0.0.1:8000

echo.
echo All done! The project should now be running.
pause
