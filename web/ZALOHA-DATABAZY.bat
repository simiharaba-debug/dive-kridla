@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Vytvaram zalohu databazy (ucty, galeria) do storage\zalohy ...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bin\windows-php.ps1" bin\db-zaloha.php
echo.
echo Subor zo storage\zalohy skopiruj na druhy pocitac do rovnakeho priecinka a tam spusti OBNOVIT-DATABAZU.bat
pause
