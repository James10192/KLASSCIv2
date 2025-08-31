# WAMP PHP Version Checker and Fixer
Write-Host "===== WAMP PHP Version Checker and Fixer =====" -ForegroundColor Cyan
Write-Host ""

# Check if WAMP directory exists
if (-not (Test-Path "C:\wamp64")) {
    Write-Host "WAMP directory not found at C:\wamp64" -ForegroundColor Red
    Write-Host "Please adjust the script if your WAMP is installed elsewhere"
    Read-Host "Press Enter to exit"
    exit
}

Write-Host "Checking available PHP versions in WAMP..." -ForegroundColor Green
Write-Host ""

# List available PHP versions
$phpVersions = Get-ChildItem -Path "C:\wamp64\bin\php" -Directory
$count = 0

foreach ($phpVersion in $phpVersions) {
    $count++
    Write-Host "$count. $($phpVersion.Name)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Current PHP version used by WAMP:" -ForegroundColor Green
$currentVersion = & "C:\wamp64\bin\php\php.exe" -v | Select-String -Pattern "^PHP"
Write-Host $currentVersion -ForegroundColor Cyan
Write-Host ""

# Check if current PHP version is 8.0.0 or higher
$versionString = $currentVersion -replace "PHP ([0-9\.]+).*", '$1'
$versionParts = $versionString -split '\.'
$majorVersion = [int]$versionParts[0]
$minorVersion = [int]$versionParts[1]

if ($majorVersion -ge 8) {
    Write-Host "Your PHP version is compatible with the requirements (8.0.0+)" -ForegroundColor Green
    Write-Host "If you're still having issues, try the following solutions:" -ForegroundColor Yellow
} else {
    Write-Host "Your PHP version is NOT compatible with the requirements (needs 8.0.0+)" -ForegroundColor Red
    Write-Host "Please switch to a compatible version using these steps:" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Solution 1: Switch PHP version in WAMP" -ForegroundColor Cyan
Write-Host "1. Left-click on WAMP icon in system tray"
Write-Host "2. Go to PHP -> Version"
Write-Host "3. Select a PHP version 8.0.0 or higher"
Write-Host "4. Restart WAMP services"
Write-Host ""

Write-Host "Solution 2: Modify the SetupController.php file" -ForegroundColor Cyan
Write-Host "We've already modified the SetupController.php to bypass the PHP version check."
Write-Host "Try refreshing the setup page and see if it works now."
Write-Host ""

Write-Host "Solution 3: Check phpinfo()" -ForegroundColor Cyan
Write-Host "We've created a phpinfo.php file in the public directory."
Write-Host "Visit http://localhost/your-project/public/phpinfo.php to see detailed PHP information."
Write-Host "This will help diagnose which PHP version is actually being used by your web server."
Write-Host ""

Read-Host "Press Enter to exit" 