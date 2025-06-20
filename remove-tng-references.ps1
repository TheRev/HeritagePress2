# PowerShell script to remove TNG references in all code files
# Define the root path of the plugin
$rootPath = "c:\MAMP\htdocs\HeritagePress2\wp-content\plugins\heritagepress"

# Define specific text replacements
$replacements = @(
    @{
        Pattern = "TNG database"
        Replacement = "database"
    },
    @{
        Pattern = "TNG import"
        Replacement = "data import"
    },
    @{
        Pattern = "TNG data"
        Replacement = "genealogy data"
    },
    @{
        Pattern = "TNG users"
        Replacement = "genealogy users"
    },
    @{
        Pattern = "TNG tables"
        Replacement = "genealogy tables"
    },
    @{
        Pattern = "TNG form"
        Replacement = "genealogy form"
    },
    @{
        Pattern = "TNG content"
        Replacement = "genealogy content"
    },
    @{
        Pattern = "TNG records"
        Replacement = "genealogy records"
    },
    @{
        Pattern = "TNG API"
        Replacement = "HeritagePress API"
    },
    @{
        Pattern = "TNG integration"
        Replacement = "genealogy integration"
    },
    @{
        Pattern = "TNG configuration"
        Replacement = "genealogy configuration"
    },
    @{
        Pattern = "TNG support"
        Replacement = "genealogy support"
    },
    @{
        Pattern = "TNG"
        Replacement = "HeritagePress"
    }
)

# Get all code files excluding those in the tng folder and markdown files
$files = Get-ChildItem -Path $rootPath -Recurse -File -Include "*.php","*.js","*.css","*.json","*.html","*.xml" |
    Where-Object { $_.FullName -notlike "*\tng\*" -and $_.Extension -ne ".md" }

$logFile = Join-Path -Path $rootPath -ChildPath "tng-replacement-log.txt"
"TNG Reference Replacement Log - $(Get-Date)" | Out-File -FilePath $logFile

foreach ($file in $files) {
    $content = Get-Content -Path $file.FullName -Raw -ErrorAction SilentlyContinue
    $originalContent = $content
    $modified = $false

    # Skip if file can't be read
    if ($null -eq $content) {
        "SKIPPED: Could not read $($file.FullName)" | Out-File -FilePath $logFile -Append
        continue
    }

    # Apply each replacement pattern
    foreach ($replacement in $replacements) {
        if ($content -match $replacement.Pattern) {
            $content = $content -replace $replacement.Pattern, $replacement.Replacement
            $modified = $true
        }
    }

    # Save the file if modified
    if ($modified) {
        try {
            $content | Out-File -FilePath $file.FullName -Encoding utf8 -NoNewline -ErrorAction Stop
            "UPDATED: $($file.FullName)" | Out-File -FilePath $logFile -Append
        }
        catch {
            "ERROR: Failed to update $($file.FullName) - $($_.Exception.Message)" | Out-File -FilePath $logFile -Append
        }
    }
    else {
        "SKIPPED: No TNG references found in $($file.FullName)" | Out-File -FilePath $logFile -Append
    }
}

Write-Host "TNG reference removal completed. See $logFile for details."
