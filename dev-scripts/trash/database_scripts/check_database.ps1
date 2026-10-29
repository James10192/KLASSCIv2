# Database Status Check PowerShell Script
# 
# This script helps you check the status of your database tables and records.
# It's like a health check-up for your database - imagine a doctor checking
# if all your organs are working properly!

# Display a welcome message
Write-Host "==========================================="
Write-Host "      DATABASE STATUS CHECK UTILITY       "
Write-Host "==========================================="

# Get the path to PHP executable
$phpPath = Read-Host "Enter the full path to your PHP executable (e.g., C:\wamp64\bin\php\php8.1.31\php.exe)"

if (-not (Test-Path $phpPath)) {
    Write-Host "Error: PHP executable not found at the specified path." -ForegroundColor Red
    exit
}

Write-Host "`nChecking database status..." -ForegroundColor Cyan

# Step 1: Check database connection
Write-Host "`n1. Checking database connection..." -ForegroundColor Cyan
$command = "$phpPath artisan db:monitor"
Write-Host "Running: $command"
try {
    $result = Invoke-Expression $command
    Write-Host $result
} catch {
    Write-Host "Warning: The db:monitor command might not be available in your Laravel version." -ForegroundColor Yellow
    Write-Host "Trying alternative method..." -ForegroundColor Yellow
    
    $command = "$phpPath artisan tinker --execute=`"try { DB::connection()->getPdo(); echo 'Connection successful!'; } catch (\Exception \`$e) { echo 'Connection failed: ' . \`$e->getMessage(); }`""
    Write-Host "Running: $command"
    $result = Invoke-Expression $command
    Write-Host $result
}

# Step 2: List migrations status
Write-Host "`n2. Checking migrations status..." -ForegroundColor Cyan
$command = "$phpPath artisan migrate:status"
Write-Host "Running: $command"
$result = Invoke-Expression $command
Write-Host $result

# Step 3: Count records in key tables
Write-Host "`n3. Counting records in key tables..." -ForegroundColor Cyan
$tables = @{
    'users' = 'Users'
    'roles' = 'Roles'
    'permissions' = 'Permissions'
    'students' = 'Students'
    'teachers' = 'Teachers'
    'departments' = 'Departments'
    'ufrs' = 'UFRs'
    'formations' = 'Formations'
    'parcours' = 'Parcours'
    'unite_enseignements' = 'Teaching Units'
    'element_constitutifs' = 'Teaching Elements'
}

foreach ($table in $tables.Keys) {
    $label = $tables[$table]
    $command = "$phpPath artisan tinker --execute=`"try { echo '$label: ' . DB::table('$table')->count(); } catch (\Exception \`$e) { echo '$label: Table not found or error'; }`""
    $result = Invoke-Expression $command
    Write-Host $result
}

Write-Host "`n==========================================="
Write-Host "Database status check completed!" -ForegroundColor Green
Write-Host "==========================================="

# Wait for user input before closing
Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 