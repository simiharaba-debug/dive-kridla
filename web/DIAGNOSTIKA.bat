@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Kontrolujem databazu a prihlasovanie ...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\windows-php.ps1" bin\diagnostika.php
echo.
pause
