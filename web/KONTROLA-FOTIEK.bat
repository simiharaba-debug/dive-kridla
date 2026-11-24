@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Kontrolujem fotografie na webe a vo fotogalerii ...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\windows-php.ps1" bin\kontrola-fotiek.php
echo.
echo Vysledok je ulozeny aj v subore storage\kontrola-fotiek.txt
pause
