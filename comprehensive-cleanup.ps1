# Enhanced PowerShell script to remove TNG, tng, and port references
# Define the root path of the plugin
$rootPath = "c:\MAMP\htdocs\HeritagePress2\wp-content\plugins\heritagepress"

# Define comprehensive text replacements
$replacements = @(
    # TNG specific replacements (case sensitive)
    @{
        Pattern = "TNG database"
        Replacement = "genealogy database"
    },
    @{
        Pattern = "TNG import"
        Replacement = "genealogy import"
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
        Pattern = "TNG system"
        Replacement = "genealogy system"
    },
    @{
        Pattern = "TNG functionality"
        Replacement = "genealogy functionality"
    },
    @{
        Pattern = "TNG structure"
        Replacement = "genealogy structure"
    },
    @{
        Pattern = "TNG format"
        Replacement = "genealogy format"
    },
    @{
        Pattern = "TNG style"
        Replacement = "genealogy style"
    },
    @{
        Pattern = "TNG field"
        Replacement = "genealogy field"
    },
    @{
        Pattern = "TNG admin"
        Replacement = "genealogy admin"
    },
    @{
        Pattern = "TNG reference"
        Replacement = "genealogy reference"
    },
    @{
        Pattern = "based on TNG"
        Replacement = "genealogy-based"
    },
    @{
        Pattern = "from TNG"
        Replacement = "from genealogy system"
    },
    @{
        Pattern = "TNG-style"
        Replacement = "genealogy-style"
    },
    @{
        Pattern = "TNG file"
        Replacement = "genealogy file"
    },
    @{
        Pattern = "TNG tree"
        Replacement = "genealogy tree"
    },
    @{
        Pattern = "TNG genealogy"
        Replacement = "genealogy"
    },

    # Filename patterns
    @{
        Pattern = "-tng-"
        Replacement = "-"
    },
    @{
        Pattern = "_tng_"
        Replacement = "_"
    },
    @{
        Pattern = "Enhanced_TNG_"
        Replacement = "Enhanced_"
    },
    @{
        Pattern = "class-hp-enhanced-tng-"
        Replacement = "class-hp-enhanced-"
    },
      # Port-related replacements
    @{
        Pattern = "-port\.php"
        Replacement = ".php"
    },
    @{
        Pattern = "_port\.php"
        Replacement = ".php"
    },
    @{
        Pattern = "port\.php"
        Replacement = ".php"
    },
    @{
        Pattern = "ported from HeritagePress.*?\.php"
        Replacement = ""
    },
    @{
        Pattern = ", ported from HeritagePress.*?\.php"
        Replacement = ""
    },
    @{
        Pattern = "Ported from HeritagePress.*?\.php"
        Replacement = ""
    },
    @{
        Pattern = "ported from HeritagePress"
        Replacement = ""
    },
    @{
        Pattern = "Ported from HeritagePress"
        Replacement = ""
    },
    @{
        Pattern = "Ported from"
        Replacement = "Based on"
    },
    @{
        Pattern = "port of"
        Replacement = "adaptation of"
    },
    @{
        Pattern = "Port of"
        Replacement = "Adaptation of"
    },
    @{
        Pattern = "porting"
        Replacement = "adapting"
    },
    @{
        Pattern = "Porting"
        Replacement = "Adapting"
    },
    @{
        Pattern = "ported"
        Replacement = "adapted"
    },
    @{
        Pattern = "Ported"
        Replacement = "Adapted"
    },

    # Generic TNG removal (should be last to catch remaining instances)
    @{
        Pattern = "\bTNG\b"
        Replacement = "HeritagePress"
    },
    @{
        Pattern = "\btng\b"
        Replacement = "heritagepress"
    }
)

# File extensions to process
$fileExtensions = @("*.php", "*.js", "*.css", "*.html", "*.xml", "*.json", "*.txt")

# Get all relevant files excluding those in the tng folder and markdown files
$files = Get-ChildItem -Path $rootPath -Recurse -File -Include $fileExtensions |
    Where-Object {
        $_.FullName -notlike "*\tng\*" -and
        $_.Extension -ne ".md" -and
        $_.Name -notlike "*.phar" -and
        $_.Name -notlike "*find-tng*" -and
        $_.Name -notlike "*remove-tng*" -and
        $_.Name -notlike "*tng-replacement-log*"
    }

$logFile = Join-Path -Path $rootPath -ChildPath "comprehensive-cleanup-log.txt"
"Comprehensive TNG/Port Reference Cleanup Log - $(Get-Date)" | Out-File -FilePath $logFile

Write-Host "Processing $($files.Count) files for TNG/port reference cleanup..."

$filesModified = 0
$totalReplacements = 0

foreach ($file in $files) {
    $content = Get-Content -Path $file.FullName -Raw -ErrorAction SilentlyContinue
    $originalContent = $content
    $fileModified = $false
    $fileReplacements = 0

    # Skip if file can't be read
    if ($null -eq $content) {
        "SKIPPED: Could not read $($file.FullName)" | Out-File -FilePath $logFile -Append
        continue
    }

    # Apply each replacement pattern
    foreach ($replacement in $replacements) {
        $beforeCount = ($content | Select-String -Pattern $replacement.Pattern -AllMatches).Matches.Count
        if ($beforeCount -gt 0) {
            $content = $content -replace $replacement.Pattern, $replacement.Replacement
            $fileReplacements += $beforeCount
            $fileModified = $true

            "  - Replaced '$($replacement.Pattern)' with '$($replacement.Replacement)' ($beforeCount times)" | Out-File -FilePath $logFile -Append
        }
    }

    # Save the file if modified
    if ($fileModified) {
        try {
            $content | Out-File -FilePath $file.FullName -Encoding utf8 -NoNewline -ErrorAction Stop
            "UPDATED: $($file.FullName) - $fileReplacements replacements made" | Out-File -FilePath $logFile -Append
            $filesModified++
            $totalReplacements += $fileReplacements
            Write-Host "Updated: $($file.Name) ($fileReplacements replacements)"
        }
        catch {
            "ERROR: Failed to update $($file.FullName) - $($_.Exception.Message)" | Out-File -FilePath $logFile -Append
            Write-Host "ERROR: Failed to update $($file.Name)"
        }
    }
}

# Summary
$summary = @"

CLEANUP SUMMARY:
- Files processed: $($files.Count)
- Files modified: $filesModified
- Total replacements made: $totalReplacements
- Completion time: $(Get-Date)

"@

$summary | Out-File -FilePath $logFile -Append
Write-Host $summary

# Check for any remaining TNG/port references
Write-Host "`nChecking for remaining references..."
$remainingFiles = @()

foreach ($file in $files) {
    $content = Get-Content -Path $file.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -and ($content -match "\bTNG\b" -or $content -match "\btng\b" -or $content -match "port\.php" -or $content -match "-port\b")) {
        $remainingFiles += $file.FullName
    }
}

if ($remainingFiles.Count -gt 0) {
    "`nFILES WITH REMAINING REFERENCES:" | Out-File -FilePath $logFile -Append
    foreach ($file in $remainingFiles) {
        $file | Out-File -FilePath $logFile -Append
        Write-Host "Remaining references in: $file"
    }
} else {
    "`nSUCCESS: No remaining TNG/port references found!" | Out-File -FilePath $logFile -Append
    Write-Host "SUCCESS: No remaining TNG/port references found!"
}

Write-Host "`nCleanup completed. See $logFile for detailed results."
