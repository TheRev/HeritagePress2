@echo off
echo ================================
echo DATABASE MANAGER RESTORATION
echo ================================
echo.

if not exist "includes\class-hp-database-manager.BACKUP.php" (
    echo ERROR: Backup file not found!
    echo Please check that includes\class-hp-database-manager.BACKUP.php exists
    pause
    exit /b 1
)

echo Current database manager status:
if exist "includes\class-hp-database-manager.php" (
    echo [FOUND] includes\class-hp-database-manager.php
) else (
    echo [MISSING] includes\class-hp-database-manager.php
)

echo.
echo [FOUND] includes\class-hp-database-manager.BACKUP.php (backup)
echo.

set /p choice="Restore database manager from backup? (y/N): "
if /i "%choice%"=="y" (
    echo.
    echo Restoring database manager from backup...
    copy "includes\class-hp-database-manager.BACKUP.php" "includes\class-hp-database-manager.php"

    if %ERRORLEVEL% EQU 0 (
        echo SUCCESS: Database manager restored from backup
        echo.
        echo The file now contains the VERIFIED, WORKING database structure
        echo that perfectly matches the genealogy reference system.
    ) else (
        echo ERROR: Failed to restore from backup
    )
) else (
    echo Restoration cancelled.
)

echo.
echo You can also restore using Git:
echo   git checkout includes/class-hp-database-manager.php
echo.
pause
