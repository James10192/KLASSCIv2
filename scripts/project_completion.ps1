# PowerShell script for project completion tasks
Set-Location -Path $PSScriptRoot\..
Write-Host "=== Project Completion Tasks ===" -ForegroundColor Cyan
Write-Host ""

function Move-FilesToTrash {
    param (
        [string[]]$Files
    )
    
    # Create trash directory if it doesn't exist
    if (!(Test-Path -Path "$PSScriptRoot\trash")) {
        New-Item -Path "$PSScriptRoot\trash" -ItemType Directory | Out-Null
        Write-Host "Created trash directory" -ForegroundColor Yellow
    }
    
    foreach ($file in $Files) {
        if (Test-Path -Path $file) {
            $fileName = Split-Path -Path $file -Leaf
            $destination = "$PSScriptRoot\trash\$fileName"
            
            # If file already exists in trash, add timestamp to filename
            if (Test-Path -Path $destination) {
                $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
                $destination = "$PSScriptRoot\trash\${timestamp}_$fileName"
            }
            
            # Move file to trash
            Move-Item -Path $file -Destination $destination
            Write-Host "Moved $file to trash" -ForegroundColor Green
        } else {
            Write-Host "File not found: $file" -ForegroundColor Red
        }
    }
}

function Optimize-Project {
    # Clear all caches
    Write-Host "Clearing caches..." -ForegroundColor Yellow
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
    
    # Optimize for production
    Write-Host "Optimizing for production..." -ForegroundColor Yellow
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize
    
    # Create storage link if it doesn't exist
    if (!(Test-Path -Path "public/storage")) {
        Write-Host "Creating storage link..." -ForegroundColor Yellow
        php artisan storage:link
    }
    
    Write-Host "Project optimized successfully!" -ForegroundColor Green
}

function Backup-Project {
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupDir = "C:\wamp64\backups"
    $backupFile = "$backupDir\smart_school_backup_$timestamp.zip"
    
    # Create backup directory if it doesn't exist
    if (!(Test-Path -Path $backupDir)) {
        New-Item -Path $backupDir -ItemType Directory | Out-Null
        Write-Host "Created backup directory: $backupDir" -ForegroundColor Yellow
    }
    
    # Create backup
    Write-Host "Creating backup..." -ForegroundColor Yellow
    Compress-Archive -Path "." -DestinationPath $backupFile -Force
    
    Write-Host "Backup created: $backupFile" -ForegroundColor Green
}

# Show menu
Write-Host "Select a task:" -ForegroundColor Yellow
Write-Host "1. Clean up temporary files" -ForegroundColor Green
Write-Host "2. Optimize project for production" -ForegroundColor Blue
Write-Host "3. Create project backup" -ForegroundColor Magenta
Write-Host "4. Complete all tasks" -ForegroundColor Cyan
Write-Host "5. Exit" -ForegroundColor Red

$choice = Read-Host "Enter your choice (1-5)"

switch ($choice) {
    "1" { 
        Write-Host "Cleaning up temporary files..." -ForegroundColor Green
        $filesToTrash = @(
            ".\storage\logs\*.log",
            ".\bootstrap\cache\*.php",
            ".\public\hot"
        )
        Move-FilesToTrash -Files $filesToTrash
    }
    "2" { 
        Optimize-Project
    }
    "3" { 
        Backup-Project
    }
    "4" { 
        Write-Host "Completing all tasks..." -ForegroundColor Cyan
        
        # Clean up temporary files
        Write-Host "Cleaning up temporary files..." -ForegroundColor Green
        $filesToTrash = @(
            ".\storage\logs\*.log",
            ".\bootstrap\cache\*.php",
            ".\public\hot"
        )
        Move-FilesToTrash -Files $filesToTrash
        
        # Optimize project
        Optimize-Project
        
        # Create backup
        Backup-Project
        
        Write-Host "All tasks completed successfully!" -ForegroundColor Cyan
    }
    "5" { 
        Write-Host "Exiting..." -ForegroundColor Red
        exit 
    }
    default { 
        Write-Host "Invalid choice." -ForegroundColor Red 
    }
}

Write-Host "`n=== Project Completion Tasks Complete ===" -ForegroundColor Cyan
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 