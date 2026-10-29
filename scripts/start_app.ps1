# PowerShell script to start the Smart School application
Set-Location -Path $PSScriptRoot\..

function Show-Menu {
    Clear-Host
    Write-Host "===== Smart School Application =====" -ForegroundColor Cyan
    Write-Host "1. Start Development Server" -ForegroundColor Green
    Write-Host "2. Run Database Migrations" -ForegroundColor Yellow
    Write-Host "3. Check Database Status" -ForegroundColor Blue
    Write-Host "4. Clear Application Cache" -ForegroundColor Magenta
    Write-Host "5. Create Storage Link" -ForegroundColor White
    Write-Host "6. Check Environment" -ForegroundColor Gray
    Write-Host "7. Exit" -ForegroundColor Red
    Write-Host "=====================================" -ForegroundColor Cyan
}

function Start-DevServer {
    Write-Host "Starting PHP development server at http://localhost:8000" -ForegroundColor Green
    Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
    Write-Host ""
    php -S localhost:8000 -t public
}

function Run-Migrations {
    & "$PSScriptRoot\migrate.ps1"
}

function Check-Database {
    & "$PSScriptRoot\check_database.ps1"
}

function Clear-Cache {
    Write-Host "Clearing application cache..." -ForegroundColor Magenta
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    Write-Host "Cache cleared successfully!" -ForegroundColor Green
    Write-Host "Press any key to continue..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
}

function Create-StorageLink {
    Write-Host "Creating storage link..." -ForegroundColor White
    php artisan storage:link
    Write-Host "Storage link created!" -ForegroundColor Green
    Write-Host "Press any key to continue..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
}

function Check-Environment {
    & "$PSScriptRoot\check_env.ps1"
}

# Main program loop
$continue = $true
while ($continue) {
    Show-Menu
    $choice = Read-Host "Enter your choice (1-7)"
    
    switch ($choice) {
        "1" { Start-DevServer }
        "2" { Run-Migrations }
        "3" { Check-Database }
        "4" { Clear-Cache }
        "5" { Create-StorageLink }
        "6" { Check-Environment }
        "7" { $continue = $false }
        default { 
            Write-Host "Invalid choice. Press any key to continue..." -ForegroundColor Red
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
        }
    }
}

Write-Host "Goodbye!" -ForegroundColor Cyan 