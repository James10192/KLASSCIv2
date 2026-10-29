# PowerShell script to run database migrations
Set-Location -Path $PSScriptRoot\..
Write-Host "Running database migrations..." -ForegroundColor Cyan

# Ask user which migration command to run
Write-Host "Select a migration command:" -ForegroundColor Yellow
Write-Host "1. migrate (Run pending migrations)" -ForegroundColor Green
Write-Host "2. migrate:fresh (Drop all tables and re-run all migrations)" -ForegroundColor Red
Write-Host "3. migrate:refresh (Reset and re-run all migrations)" -ForegroundColor Magenta
Write-Host "4. migrate:status (Show migration status)" -ForegroundColor Blue

$choice = Read-Host "Enter your choice (1-4)"

switch ($choice) {
    "1" { 
        Write-Host "Running pending migrations..." -ForegroundColor Cyan
        php artisan migrate 
    }
    "2" { 
        $confirm = Read-Host "This will DELETE ALL DATA. Are you sure? (y/n)"
        if ($confirm -eq "y") {
            Write-Host "Running fresh migrations..." -ForegroundColor Red
            php artisan migrate:fresh
        } else {
            Write-Host "Operation cancelled." -ForegroundColor Yellow
        }
    }
    "3" { 
        $confirm = Read-Host "This will reset all migrations. Are you sure? (y/n)"
        if ($confirm -eq "y") {
            Write-Host "Refreshing migrations..." -ForegroundColor Magenta
            php artisan migrate:refresh
        } else {
            Write-Host "Operation cancelled." -ForegroundColor Yellow
        }
    }
    "4" { 
        Write-Host "Showing migration status..." -ForegroundColor Blue
        php artisan migrate:status 
    }
    default { 
        Write-Host "Invalid choice. Exiting." -ForegroundColor Red 
    }
}

Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 