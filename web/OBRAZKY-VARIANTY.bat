@echo off
cd /d "%~dp0"
tools\php\php.exe -c tools\php\php.ini bin\obrazky-varianty.php
pause
