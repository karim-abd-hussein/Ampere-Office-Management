@echo off
title Laravel Project Launcher
color 0A

echo ===============================================
echo   Laravel Project Auto Setup & Runner
echo ===============================================
echo.

REM === Change this to your project folder path ===
set PROJECT_PATH=C:\xampp\htdocs\Ampere-Office-Management

REM === Change this to your GitHub repo branch name ===
set BRANCH=main

REM === Start XAMPP MySQL service ===
echo Starting MySQL service...
cd /d "C:\xampp"
start "" xampp-control.exe
timeout /t 5 >nul

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
