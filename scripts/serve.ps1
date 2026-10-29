# PowerShell script to start the PHP development server
Set-Location -Path $PSScriptRoot\..
Write-Host "Starting PHP development server at http://localhost:8000" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""

# Start the PHP development server
try {
    php -S localhost:8000 -t public
}
catch {
    Write-Host "Error starting the server: $_" -ForegroundColor Red
    Write-Host "Press any key to exit..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
} 