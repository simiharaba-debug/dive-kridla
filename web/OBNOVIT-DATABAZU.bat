@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Obnova databazy zo zalohy v storage\zalohy (najnovsi subor). Sucasna databaza sa najprv odlozi.
echo Zastavujem web ...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\windows-stop.ps1"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\windows-php.ps1" bin\db-obnova.php %1
echo.
pause
