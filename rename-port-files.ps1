# PowerShell script to rename files with -port suffix
$rootPath = "c:\MAMP\htdocs\HeritagePress2\wp-content\plugins\heritagepress"

# Get all files with -port in their names (excluding vendor, tng folder, and our cleanup files)
$files = Get-ChildItem -Path $rootPath -Recurse -File |
    Where-Object {
        $_.FullName -notlike "*\tng\*" -and
        $_.FullName -notlike "*\vendor\*" -and
        $_.Name -like "*-port.*" -and
        $_.Name -notlike "*tng-*" -and
        $_.Name -notlike "*find-tng*" -and
        $_.Name -notlike "*remove-tng*" -and
        $_.Name -notlike "*comprehensive-cleanup*"
    }

$logFile = Join-Path -Path $rootPath -ChildPath "file-rename-log.txt"
"File Rename Log - $(Get-Date)" | Out-File -FilePath $logFile

Write-Host "Found $($files.Count) files with '-port' in their names to rename..."

foreach ($file in $files) {
    $newName = $file.Name -replace "-port", ""
    $newPath = Join-Path -Path $file.Directory.FullName -ChildPath $newName

    # Check if target file already exists
    if (Test-Path $newPath) {
        "SKIPPED: Target file already exists - $($file.FullName) -> $newPath" | Out-File -FilePath $logFile -Append
        Write-Host "Skipped: $($file.Name) (target exists)"
        continue
    }

    try {
        Rename-Item -Path $file.FullName -NewName $newName -ErrorAction Stop
        "RENAMED: $($file.FullName) -> $newPath" | Out-File -FilePath $logFile -Append
        Write-Host "Renamed: $($file.Name) -> $newName"
    }
    catch {
        "ERROR: Failed to rename $($file.FullName) - $($_.Exception.Message)" | Out-File -FilePath $logFile -Append
        Write-Host "Error renaming: $($file.Name)"
    }
}

Write-Host "File renaming completed. See $logFile for details."
