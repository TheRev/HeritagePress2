$pluginDir = "."
$results = @()

# Get all files, excluding .md files and those in tng folder
$files = Get-ChildItem -Path $pluginDir -Recurse -File | Where-Object {
    $_.FullName -notlike "*\tng\*" -and
    $_.Extension -ne ".md"
}

Write-Host "Searching through $($files.Count) files for 'TNG' references..."

foreach ($file in $files) {
    $content = Get-Content -Path $file.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -and $content -match "TNG") {
        $lineNumber = 0
        $matchingLines = @()

        # Get line numbers of matches
        Get-Content -Path $file.FullName | ForEach-Object {
            $lineNumber++
            if ($_ -match "TNG") {
                $matchingLines += "Line $lineNumber`: $_"
            }
        }

        $results += [PSCustomObject]@{
            FilePath = $file.FullName
            MatchingLines = $matchingLines
        }
    }
}

# Output results
Write-Host "Found $($results.Count) files containing 'TNG' references:`n"

foreach ($result in $results) {
    Write-Host "FILE: $($result.FilePath)"
    foreach ($line in $result.MatchingLines) {
        Write-Host "  $line"
    }
    Write-Host ""
}

# Create a text file with findings
$outputPath = Join-Path $pluginDir "tng-references.txt"
$outputContent = "TNG References Found - $(Get-Date)`n`n"

foreach ($result in $results) {
    $outputContent += "FILE: $($result.FilePath)`n"
    foreach ($line in $result.MatchingLines) {
        $outputContent += "  $line`n"
    }
    $outputContent += "`n"
}

$outputContent | Out-File -FilePath $outputPath
Write-Host "Results saved to: $outputPath"
