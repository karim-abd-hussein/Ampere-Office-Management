@echo off
setlocal
cd /d "%~dp0"

REM إيقاف Laravel dev server (php.exe)
for /f "tokens=2 delims=," %%P in ('tasklist /FI "IMAGENAME eq php.exe" /FO CSV ^| find /i "php.exe"') do taskkill /PID %%~P /F >nul 2>&1

REM إيقاف MySQL بلطف
mysql\bin\mysqladmin.exe --defaults-file="%CD%\mysql\my.ini" -u root -pportable123! shutdown

REM لو ما انطفى لأي سبب، قفّله بالقوة (اختياري)
taskkill /F /IM mysqld.exe >nul 2>&1
