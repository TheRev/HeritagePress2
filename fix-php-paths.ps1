$settingsFile = "C:\MAMP\htdocs\HeritagePress2\wp-content\plugins\heritagepress\.vscode\settings.json"
$settings = Get-Content -Path $settingsFile -Raw | ConvertFrom-Json

# Update PHP paths
$phpPath = "C:\MAMP\bin\php\php8.2.14\php.exe"

# Set different PHP validation settings
$settings.'php.validate.executablePath' = $phpPath
$settings.'phpmd.phpPath' = $phpPath
$settings.'phpcs.executablePath' = $phpPath

# Convert back to JSON and save
$settings | ConvertTo-Json -Depth 10 | Set-Content -Path $settingsFile

Write-Host "Settings updated with correct PHP paths!"
Write-Host "Please restart VS Code for changes to take effect."

# Pause to keep the window open
Write-Host "Press any key to continue..."
$null = $host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
