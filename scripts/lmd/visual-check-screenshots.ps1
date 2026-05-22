<#
.SYNOPSIS
    Visual-check via Browsershot des 5 vues critiques × 2 classes (BTS+LMD) × 6 tenants.

.DESCRIPTION
    Génère des screenshots PNG des routes principales du chantier emploi-temps LMD.
    Stocke dans storage/visual-checks/YYYYMMDD-HHMMSS/{tenant}/{view}-{classe}.png

.PARAMETER Baseline
    Si présent, sauvegarde les screenshots comme baseline pour comparaison future.

.PARAMETER Compare
    Si présent, compare les screenshots actuels avec la dernière baseline.

.PARAMETER Tenants
    Liste des tenants à check. Par défaut: presentation.

.EXAMPLE
    .\scripts\lmd\visual-check-screenshots.ps1 -Baseline
    .\scripts\lmd\visual-check-screenshots.ps1 -Compare
#>

[CmdletBinding()]
param(
    [switch]$Baseline,
    [switch]$Compare,
    [string[]]$Tenants = @('presentation'),
    [string]$BaseUrl = "http://presentation.klassci.test"
)

$RepoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$ts = Get-Date -Format "yyyyMMdd-HHmmss"
$OutputDir = Join-Path $RepoRoot "storage" "visual-checks" $ts

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

Write-Host "📸 KLASSCI Visual Check" -ForegroundColor Cyan
Write-Host "Mode    : $(if ($Baseline) { 'BASELINE' } elseif ($Compare) { 'COMPARE' } else { 'SCREENSHOT-ONLY' })" -ForegroundColor Gray
Write-Host "Output  : $OutputDir" -ForegroundColor Gray
Write-Host ""

# Routes critiques à check
$routes = @(
    @{ name = "emploi-temps-index"; url = "/esbtp/emploi-temps" },
    @{ name = "emploi-temps-bulk-edit"; url = "/esbtp/emploi-temps/bulk-edit" },
    @{ name = "seances-cours-create"; url = "/esbtp/seances-cours/create" },
    @{ name = "lmd-planning"; url = "/esbtp/lmd/planning" },
    @{ name = "lmd-jurys-index"; url = "/esbtp/lmd/jurys" }
)

# Note : Spatie Browsershot est dispo via composer (vendor/spatie/browsershot)
# On utilise un mini PHP script qui appelle Browsershot

$phpScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';

use Spatie\Browsershot\Browsershot;

$url = $argv[1] ?? null;
$output = $argv[2] ?? null;

if (!$url || !$output) {
    echo "Usage: php visual-check-screenshot.php <url> <output-path>\n";
    exit(1);
}

try {
    Browsershot::url($url)
        ->windowSize(1920, 1080)
        ->fullPage()
        ->setOption('args', ['--no-sandbox', '--disable-gpu'])
        ->save($output);
    echo "OK\n";
    exit(0);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
'@

$tmpScript = Join-Path $RepoRoot "tmp" "visual-check-screenshot.php"
if (-not (Test-Path (Split-Path $tmpScript -Parent))) {
    New-Item -ItemType Directory -Path (Split-Path $tmpScript -Parent) | Out-Null
}
Set-Content -Path $tmpScript -Value $phpScript -Encoding UTF8

Push-Location $RepoRoot
try {
    foreach ($tenant in $Tenants) {
        $tenantDir = Join-Path $OutputDir $tenant
        if (-not (Test-Path $tenantDir)) {
            New-Item -ItemType Directory -Path $tenantDir | Out-Null
        }

        # Tenant URL — adapter selon ton setup local/staging
        $tenantBaseUrl = if ($tenant -eq 'presentation') { $BaseUrl } else { "http://$tenant.klassci.test" }

        Write-Host "🌐 Tenant: $tenant" -ForegroundColor Cyan

        foreach ($route in $routes) {
            $url = "$tenantBaseUrl$($route.url)"
            $output = Join-Path $tenantDir "$($route.name).png"

            Write-Host "  📸 $($route.name) → $url" -ForegroundColor Gray
            $result = & php $tmpScript $url $output 2>&1
            if ($LASTEXITCODE -eq 0) {
                Write-Host "    ✅ $output" -ForegroundColor Green
            } else {
                Write-Host "    ❌ $result" -ForegroundColor Red
            }
        }
    }

    Write-Host ""
    Write-Host "📁 Screenshots sauvés dans : $OutputDir" -ForegroundColor Green

    if ($Baseline) {
        $baselineDir = Join-Path $RepoRoot "storage" "visual-checks" "baseline"
        if (Test-Path $baselineDir) {
            Remove-Item -Path $baselineDir -Recurse -Force
        }
        Copy-Item -Path $OutputDir -Destination $baselineDir -Recurse
        Write-Host "📌 Baseline sauvegardée: $baselineDir" -ForegroundColor Green
    }

    if ($Compare) {
        $baselineDir = Join-Path $RepoRoot "storage" "visual-checks" "baseline"
        if (-not (Test-Path $baselineDir)) {
            Write-Host "⚠️ Pas de baseline trouvée. Run avec -Baseline d'abord." -ForegroundColor Yellow
            exit 1
        }

        Write-Host ""
        Write-Host "🔍 Diff vs baseline ($baselineDir)..." -ForegroundColor Cyan

        $diffs = @()
        Get-ChildItem -Path $OutputDir -Recurse -Filter "*.png" | ForEach-Object {
            $current = $_.FullName
            $relative = $current.Substring($OutputDir.Length + 1)
            $baseline = Join-Path $baselineDir $relative

            if (-not (Test-Path $baseline)) {
                Write-Host "  ⚠️ Nouveau: $relative" -ForegroundColor Yellow
                $diffs += "NEW: $relative"
            } else {
                $hashCurrent = (Get-FileHash $current -Algorithm MD5).Hash
                $hashBaseline = (Get-FileHash $baseline -Algorithm MD5).Hash
                if ($hashCurrent -ne $hashBaseline) {
                    Write-Host "  🔴 DIFF: $relative" -ForegroundColor Red
                    $diffs += "DIFF: $relative"
                } else {
                    Write-Host "  ✅ OK: $relative" -ForegroundColor Green
                }
            }
        }

        if ($diffs.Count -gt 0) {
            Write-Host ""
            Write-Host "⚠️ $($diffs.Count) différence(s) détectée(s). Investigation requise." -ForegroundColor Yellow
            exit 1
        } else {
            Write-Host ""
            Write-Host "✅ Aucune différence visuelle." -ForegroundColor Green
        }
    }
}
finally {
    Pop-Location
}
