# PowerShell script to check database status
Set-Location -Path $PSScriptRoot\..
Write-Host "=== Database Status Check ===" -ForegroundColor Cyan
Write-Host ""

# Check database connection
Write-Host "Checking database connection..." -ForegroundColor Yellow
$dbResult = php artisan db:show 2>&1
if ($dbResult -match "Connected successfully") {
    Write-Host "Database: Connected successfully" -ForegroundColor Green
} else {
    Write-Host "Database: Connection failed" -ForegroundColor Red
    Write-Host $dbResult
    exit
}

# Show migration status
Write-Host "`nMigration Status:" -ForegroundColor Yellow
php artisan migrate:status | Out-Host

# Count tables in database
Write-Host "`nDatabase Tables:" -ForegroundColor Yellow
$tables = php artisan db:table --counts 2>&1
Write-Host $tables

# Show models and their table counts
Write-Host "`nModel Counts:" -ForegroundColor Yellow
$models = @(
    "User",
    "Student",
    "Teacher",
    "SuperAdmin",
    "Secretary",
    "UniteEnseignement",
    "ElementConstitutif",
    "Evaluation",
    "Grade"
)

foreach ($model in $models) {
    $count = php artisan tinker --execute="echo App\\Models\\$model::count();" 2>&1
    if ($count -match "\d+") {
        $countNum = [int]($count -replace "[^0-9]", "")
        if ($countNum -gt 0) {
            Write-Host "$model : $countNum records" -ForegroundColor Green
        } else {
            Write-Host "$model : $countNum records" -ForegroundColor Yellow
        }
    } else {
        Write-Host "$model : Error getting count" -ForegroundColor Red
    }
}

Write-Host "`n=== Database Check Complete ===" -ForegroundColor Cyan
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 