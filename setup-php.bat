@echo off
echo Setting up PHP for VS Code Extensions...

REM Create bin directory in project if it doesn't exist
if not exist "%~dp0bin" (
  mkdir "%~dp0bin"
)

REM Copy PHP executable from MAMP to the project bin directory
copy "C:\MAMP\bin\php\php8.2.14\php.exe" "%~dp0bin\php.exe"
copy "C:\MAMP\bin\php\php8.2.14\php-cgi.exe" "%~dp0bin\php-cgi.exe"

REM Copy required DLLs
copy "C:\MAMP\bin\php\php8.2.14\*.dll" "%~dp0bin\"

echo PHP setup complete! The PHP executable is now available at:
echo %~dp0bin\php.exe
echo.
echo Please restart VS Code for the changes to take effect.
pause
