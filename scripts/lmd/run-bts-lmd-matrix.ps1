<#
.SYNOPSIS
    Exécute la matrice complète de tests BTS/LMD (4 combos × Unit + Feature + Browser).

.DESCRIPTION
    Run Pest avec filters BTS/LMD pour les 3 couches de tests.
    Parse les résultats et génère tableau croisé pass/fail.

.PARAMETER Layer
    'Unit', 'Feature', 'Browser', ou 'All' (default)

.PARAMETER OutputFile
    Chemin du fichier de sortie rapport.

.EXAMPLE
    .\scripts\lmd\run-bts-lmd-matrix.ps1
    .\scripts\lmd\run-bts-lmd-matrix.ps1 -Layer Feature
#>

[CmdletBinding()]
param(
    [ValidateSet('Unit', 'Feature', 'Browser', 'All')]
    [string]$Layer = 'All',
    [string]$OutputFile = ""
)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)

if (-not $OutputFile) {
    $tmpDir = Join-Path $RepoRoot "tmp"
    if (-not (Test-Path $tmpDir)) { New-Item -ItemType Directory -Path $tmpDir | Out-Null }
    $OutputFile = Join-Path $tmpDir ("test-matrix-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".md")
}

Write-Host "🧪 KLASSCI BTS/LMD Test Matrix" -ForegroundColor Cyan
Write-Host "Layer : $Layer" -ForegroundColor Gray
Write-Host ""

Push-Location $RepoRoot

# Helper: run pest filter and return JSON-ish result
function Invoke-PestFilter {
    param(
        [string]$Filter,
        [string]$Suite = $null
    )

    $args = @("test")
    if ($Suite) {
        $args += "--testsuite=$Suite"
    }
    if ($Filter) {
        $args += "--filter=$Filter"
    }
    $args += "--no-coverage"

    Write-Host "→ Running: php artisan $($args -join ' ')" -ForegroundColor Gray

    try {
        $output = & php artisan @args 2>&1
        $exitCode = $LASTEXITCODE
        return @{
            ExitCode = $exitCode
            Output = ($output -join "`n")
            Pass = ($exitCode -eq 0)
        }
    } catch {
        return @{
            ExitCode = 1
            Output = $_.Exception.Message
            Pass = $false
        }
    }
}

try {
    $results = @{}

    if ($Layer -in @('Unit', 'All')) {
        Write-Host "▶️ Layer: Unit" -ForegroundColor Cyan
        $results['Unit'] = Invoke-PestFilter -Filter "BtsLmd" -Suite "Unit"
    }

    if ($Layer -in @('Feature', 'All')) {
        Write-Host "▶️ Layer: Feature" -ForegroundColor Cyan
        $results['Feature'] = Invoke-PestFilter -Filter "BtsLmd" -Suite "Feature"
    }

    if ($Layer -in @('Browser', 'All')) {
        Write-Host "▶️ Layer: Browser" -ForegroundColor Cyan
        # Check if Pest Browser plugin is installed
        $hasBrowser = Test-Path "vendor/pestphp/pest-plugin-browser"
        if (-not $hasBrowser) {
            Write-Host "⚠️ Pest Browser plugin pas installé — skip" -ForegroundColor Yellow
            $results['Browser'] = @{ Pass = $null; Output = "Skipped (plugin not installed)" }
        } else {
            $results['Browser'] = Invoke-PestFilter -Filter "BtsLmd" -Suite "Browser"
        }
    }

    # Generate report
    $report = @"
# Test Matrix Result — $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

## Résumé

| Couche | Status | Filter |
|---|---|---|
"@

    foreach ($layerName in @('Unit', 'Feature', 'Browser')) {
        if ($results.ContainsKey($layerName)) {
            $r = $results[$layerName]
            $status = if ($r.Pass -eq $true) { "✅ PASS" }
                      elseif ($r.Pass -eq $false) { "❌ FAIL" }
                      else { "⏭️ SKIPPED" }
            $report += "`n| $layerName | $status | --filter=BtsLmd |"
        }
    }

    $report += "`n`n---`n`n## Détails par couche`n"

    foreach ($layerName in @('Unit', 'Feature', 'Browser')) {
        if ($results.ContainsKey($layerName)) {
            $r = $results[$layerName]
            $report += "`n### $layerName`n`n``````n"
            $report += $r.Output
            $report += "`n```n"
        }
    }

    $report += @"

---

## Matrice 4 combos × 3 couches (cible)

| Combo | Unit | Feature | Browser |
|---|---|---|---|
| BTS pivot peuplé | TBD | TBD | TBD |
| BTS pivot vide   | TBD | TBD | TBD |
| LMD parcours     | TBD | TBD | TBD |
| LMD tronc commun | TBD | TBD | TBD |

> Pour faire fonctionner ce parsing, les tests doivent être nommés avec convention :
> ``test('BtsLmd_<combo>_<scenario>', ...)`` par exemple.

## Voir aussi

- Skill : ``klassci-test-bts-lmd-matrix``
- Master plan : ``docs/MASTER-PLAN-emploi-temps-lmd-unification.md``
- Rule : ``pre-merge-checklist.md``
"@

    Set-Content -Path $OutputFile -Value $report -Encoding UTF8

    Write-Host ""
    Write-Host "📊 Résultats :" -ForegroundColor Cyan
    foreach ($layerName in @('Unit', 'Feature', 'Browser')) {
        if ($results.ContainsKey($layerName)) {
            $r = $results[$layerName]
            $status = if ($r.Pass -eq $true) { "✅ PASS" }
                      elseif ($r.Pass -eq $false) { "❌ FAIL" }
                      else { "⏭️ SKIPPED" }
            $color = if ($r.Pass -eq $true) { "Green" }
                     elseif ($r.Pass -eq $false) { "Red" }
                     else { "Yellow" }
            Write-Host "  $layerName : $status" -ForegroundColor $color
        }
    }
    Write-Host ""
    Write-Host "📄 Rapport : $OutputFile" -ForegroundColor Green

    # Exit code = 1 si au moins 1 fail
    $hasFail = ($results.Values | Where-Object { $_.Pass -eq $false }).Count -gt 0
    exit $(if ($hasFail) { 1 } else { 0 })
}
finally {
    Pop-Location
}
