# PowerShell script to seed the database with test data
Set-Location -Path $PSScriptRoot\..
Write-Host "=== Database Seeding Tool ===" -ForegroundColor Cyan
Write-Host ""

# Show seeding options
Write-Host "Select a seeding option:" -ForegroundColor Yellow
Write-Host "1. Run all seeders" -ForegroundColor Green
Write-Host "2. Seed users only" -ForegroundColor Blue
Write-Host "3. Seed academic structure (UFRs, Formations, Parcours, etc.)" -ForegroundColor Magenta
Write-Host "4. Seed specific table" -ForegroundColor White
Write-Host "5. Exit" -ForegroundColor Red

$choice = Read-Host "Enter your choice (1-5)"

switch ($choice) {
    "1" { 
        $confirm = Read-Host "This will add test data to your database. Continue? (y/n)"
        if ($confirm -eq "y") {
            Write-Host "Running all seeders..." -ForegroundColor Green
            php artisan db:seed
            Write-Host "Database seeded successfully!" -ForegroundColor Green
        } else {
            Write-Host "Operation cancelled." -ForegroundColor Yellow
        }
    }
    "2" { 
        Write-Host "Seeding users..." -ForegroundColor Blue
        php artisan db:seed --class=UserSeeder
        Write-Host "Users seeded successfully!" -ForegroundColor Green
    }
    "3" { 
        Write-Host "Seeding academic structure..." -ForegroundColor Magenta
        php artisan db:seed --class=UFRSeeder
        php artisan db:seed --class=FormationSeeder
        php artisan db:seed --class=ParcoursSeeder
        php artisan db:seed --class=UniteEnseignementSeeder
        php artisan db:seed --class=ElementConstitutifSeeder
        Write-Host "Academic structure seeded successfully!" -ForegroundColor Green
    }
    "4" { 
        Write-Host "Available seeders:" -ForegroundColor Yellow
        Write-Host "1. UserSeeder" -ForegroundColor White
        Write-Host "2. UFRSeeder" -ForegroundColor White
        Write-Host "3. FormationSeeder" -ForegroundColor White
        Write-Host "4. ParcoursSeeder" -ForegroundColor White
        Write-Host "5. UniteEnseignementSeeder" -ForegroundColor White
        Write-Host "6. ElementConstitutifSeeder" -ForegroundColor White
        Write-Host "7. ClassroomSeeder" -ForegroundColor White
        Write-Host "8. EvaluationSeeder" -ForegroundColor White
        
        $seederChoice = Read-Host "Enter seeder number (1-8)"
        
        $seederClass = switch ($seederChoice) {
            "1" { "UserSeeder" }
            "2" { "UFRSeeder" }
            "3" { "FormationSeeder" }
            "4" { "ParcoursSeeder" }
            "5" { "UniteEnseignementSeeder" }
            "6" { "ElementConstitutifSeeder" }
            "7" { "ClassroomSeeder" }
            "8" { "EvaluationSeeder" }
            default { "" }
        }
        
        if ($seederClass -ne "") {
            Write-Host "Running $seederClass..." -ForegroundColor Green
            php artisan db:seed --class=$seederClass
            Write-Host "$seederClass completed successfully!" -ForegroundColor Green
        } else {
            Write-Host "Invalid seeder choice." -ForegroundColor Red
        }
    }
    "5" { 
        Write-Host "Exiting..." -ForegroundColor Red
        exit 
    }
    default { 
        Write-Host "Invalid choice." -ForegroundColor Red 
    }
}

Write-Host "`n=== Seeding Complete ===" -ForegroundColor Cyan
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 