@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul
cd /d "%~dp0"

rem ========== Paths ==========
set "ROOT=%CD%"
set "PHP=%ROOT%\php\php.exe"
set "MYSQL_BASE=%ROOT%\mysql"
set "MYSQL_BIN=%MYSQL_BASE%\bin"
set "MYSQL_INI=%MYSQL_BASE%\my.ini"
set "MYSQL_DATA=%MYSQL_BASE%\data"
set "PORT=8000"

echo [*] Project root: %ROOT%

rem ========== Checks ==========
if not exist "%PHP%" (
  echo [ERROR] PHP not found: "%PHP%"
  pause & exit /b 1
)
if not exist "%MYSQL_BIN%\mysqld.exe" (
  echo [ERROR] mysqld.exe not found: "%MYSQL_BIN%\mysqld.exe"
  pause & exit /b 1
)
if not exist "%MYSQL_INI%" (
  echo [ERROR] my.ini missing: "%MYSQL_INI%"
  pause & exit /b 1
)

rem ========== Writable dirs ==========
for %%D in (
  "bootstrap\cache"
  "storage"
  "storage\framework"
  "storage\framework\cache"
  "storage\framework\sessions"
  "storage\framework\views"
  "storage\logs"
) do if not exist "%%~D" mkdir "%%~D" >nul 2>&1
attrib -R /S /D "bootstrap\cache" >nul 2>&1
attrib -R /S /D "storage" >nul 2>&1
if not exist "storage\logs\laravel.log" type nul > "storage\logs\laravel.log"

rem ========== Start MySQL if needed ==========
tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if errorlevel 1 (
  echo [*] Starting MySQL...
  start "MySQL" /MIN "%MYSQL_BIN%\mysqld.exe" --defaults-file="%MYSQL_INI%" --basedir="%MYSQL_BASE%" --datadir="%MYSQL_DATA%" --console
  timeout /t 5 /nobreak >nul
) else (
  echo [i] MySQL already running.
)

rem ========== Pick free port (8000 -> 8001 -> 8010) ==========
netstat -ano | findstr ":8000 " >nul && set "PORT=8001"
netstat -ano | findstr ":!PORT! " >nul && set "PORT=8010"

rem ========== Start Laravel (VISIBLE console) ==========
echo [*] Starting Laravel on http://127.0.0.1:!PORT! ...
start "Laravel" cmd /k "%PHP%" artisan serve --host=127.0.0.1 --port=!PORT!

start "" http://127.0.0.1:!PORT!/admin/login
echo.
echo [OK] شغّال. لا تسكّر نافذة "Laravel". لإيقاف كلشي استعمل stop.bat.
echo.
pause
