@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Spustam web Biologicka ochrana ...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\windows-start.ps1"
if errorlevel 1 (
  echo.
  echo Nieco sa nepodarilo - pozri spravy vyssie ^(alebo subor storage\server.log^).
  pause
  exit /b 1
)
timeout /t 4 >nul
