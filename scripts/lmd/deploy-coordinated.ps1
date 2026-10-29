<#
.SYNOPSIS
    Déploiement coordonné cross-branch + pull + cache:clear sur les 6 tenants KLASSCI.

.DESCRIPTION
    Workflow complet post-merge :
    1. Cross-branch push (presentation → 5 tenants)
    2. klassci pull sur les 6 tenants (avec gestion fail partiel)
    3. klassci cache:clear (TOUJOURS — view:cache + opcache reset)
    4. klassci migrate (si --WithMigrate)
    5. klassci permissions:fix
    6. Logs détaillés + résumé final

.PARAMETER DryRun
    Si présent, affiche les commandes sans les exécuter.

.PARAMETER Tenants
    Liste des tenants à déployer. Par défaut : les 6 tenants standard.

.PARAMETER WithMigrate
    Si présent, exécute aussi les migrations.

.PARAMETER WithPermissions
    Si présent, exécute aussi permissions:fix.

.EXAMPLE
    .\scripts\lmd\deploy-coordinated.ps1 -DryRun
    .\scripts\lmd\deploy-coordinated.ps1 -WithMigrate -WithPermissions
    .\scripts\lmd\deploy-coordinated.ps1 -Tenants @('presentation', 'esbtp-abidjan')
#>

[CmdletBinding()]
param(
    [switch]$DryRun,
    [string[]]$Tenants = @('presentation', 'esbtp-abidjan', 'esbtp-yakro', 'ephrata', 'hetec', 'rostan'),
    [switch]$WithMigrate,
    [switch]$WithPermissions,
    [switch]$SkipPush
)

$ErrorActionPreference = "Continue"
$RepoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$LogFile = Join-Path $RepoRoot "tmp" ("deploy-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".log")

if (-not (Test-Path (Split-Path $LogFile -Parent))) {
    New-Item -ItemType Directory -Path (Split-Path $LogFile -Parent) | Out-Null
}

function Write-Log {
    param([string]$Message, [string]$Level = "INFO", [string]$Color = "White")
    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$ts] [$Level] $Message"
    Write-Host $line -ForegroundColor $Color
    Add-Content -Path $LogFile -Value $line
}

function Invoke-Cmd {
    param([string]$Cmd, [string]$Description)
    Write-Log "→ $Description" -Color Gray
    Write-Log "  $ $Cmd" -Color DarkGray
    if ($DryRun) {
        Write-Log "  (DRY-RUN — pas exécuté)" -Color Yellow
        return @{ ExitCode = 0; Output = "" }
    }
    try {
        $output = Invoke-Expression $Cmd 2>&1
        $exitCode = $LASTEXITCODE
        if ($exitCode -ne 0) {
            Write-Log "  ❌ FAIL (exit $exitCode)" -Color Red
            Write-Log "  $output" -Color Red
        } else {
            Write-Log "  ✅ OK" -Color Green
        }
        return @{ ExitCode = $exitCode; Output = $output }
    } catch {
        Write-Log "  ❌ EXCEPTION: $_" -Color Red
        return @{ ExitCode = 1; Output = $_.Exception.Message }
    }
}

Write-Log "🚀 KLASSCI Deploy Coordinated" -Color Cyan
Write-Log "Mode      : $(if ($DryRun) { 'DRY-RUN' } else { 'LIVE' })" -Color $(if ($DryRun) { 'Yellow' } else { 'Cyan' })
Write-Log "Tenants   : $($Tenants -join ', ')"
Write-Log "Migrate   : $WithMigrate"
Write-Log "Permissions: $WithPermissions"
Write-Log "Log file  : $LogFile"
Write-Log ""

Push-Location $RepoRoot
$results = @{}

try {
    # Phase 1 — Cross-branch push (skip si on n'est pas sur presentation)
    if (-not $SkipPush) {
        Write-Log "═══ PHASE 1 — Cross-branch push ═══" -Color Cyan
        $currentBranch = git branch --show-current 2>$null
        Write-Log "Current branch: $currentBranch"

        if ($currentBranch -ne "presentation") {
            Write-Log "⚠️ Not on presentation branch — skip cross-branch push" -Color Yellow
        } else {
            # Push presentation first
            $result = Invoke-Cmd "git push origin presentation" "Push presentation"
            if ($result.ExitCode -ne 0) {
                Write-Log "❌ Push presentation failed — abort" -Color Red
                exit 1
            }

            # Cross-branch push aux tenants (sauf presentation lui-même)
            foreach ($t in $Tenants | Where-Object { $_ -ne 'presentation' }) {
                Invoke-Cmd "git push origin presentation:$t" "Cross-branch push to $t"
            }
        }
    } else {
        Write-Log "⏭️ Skip push (--SkipPush)" -Color Yellow
    }

    Write-Log ""
    Write-Log "═══ PHASE 2 — Pull + cache:clear par tenant ═══" -Color Cyan

    foreach ($t in $Tenants) {
        Write-Log ""
        Write-Log "──── Tenant: $t ────" -Color Cyan

        $tenantResult = @{}

        # Pull
        $r = Invoke-Cmd "klassci pull $t" "Pull $t"
        $tenantResult['pull'] = $r.ExitCode

        # cache:clear (TOUJOURS, même si pull "Already up to date")
        $r = Invoke-Cmd "klassci cache:clear $t" "cache:clear $t"
        $tenantResult['cache_clear'] = $r.ExitCode

        # Migrate (optionnel)
        if ($WithMigrate) {
            $r = Invoke-Cmd "klassci migrate $t --dry-run" "Migrate $t (dry-run)"
            if ($r.ExitCode -eq 0) {
                $r = Invoke-Cmd "klassci migrate $t" "Migrate $t"
                $tenantResult['migrate'] = $r.ExitCode
            } else {
                Write-Log "⚠️ Migrate dry-run failed for $t — skip apply" -Color Yellow
                $tenantResult['migrate'] = -1
            }
        }

        # Permissions:fix (optionnel)
        if ($WithPermissions) {
            $r = Invoke-Cmd "klassci permissions:fix $t" "Permissions:fix $t"
            $tenantResult['permissions'] = $r.ExitCode
        }

        $results[$t] = $tenantResult
    }

    Write-Log ""
    Write-Log "═══ RÉSUMÉ FINAL ═══" -Color Cyan

    $summary = "| Tenant | Pull | Cache | $(if ($WithMigrate) { 'Migrate |' }) $(if ($WithPermissions) { 'Permissions |' }) |"
    Write-Log $summary -Color White
    Write-Log "|---" -Color White

    foreach ($t in $Tenants) {
        if ($results.ContainsKey($t)) {
            $r = $results[$t]
            $line = "| $t | $(if ($r['pull'] -eq 0) { '✅' } else { '❌' }) | $(if ($r['cache_clear'] -eq 0) { '✅' } else { '❌' }) |"
            if ($WithMigrate) {
                $line += " $(if ($r['migrate'] -eq 0) { '✅' } elseif ($r['migrate'] -eq -1) { '⏭️' } else { '❌' }) |"
            }
            if ($WithPermissions) {
                $line += " $(if ($r['permissions'] -eq 0) { '✅' } else { '❌' }) |"
            }
            Write-Log $line -Color White
        }
    }

    Write-Log ""

    # Determiner exit code global
    $hasFail = $false
    foreach ($t in $results.Values) {
        foreach ($v in $t.Values) {
            if ($v -ne 0 -and $v -ne -1) {
                $hasFail = $true
                break
            }
        }
    }

    if ($hasFail) {
        Write-Log "⚠️ Au moins 1 tenant a une erreur. Voir log: $LogFile" -Color Yellow
        exit 1
    } else {
        Write-Log "✅ Tous les tenants déployés avec succès." -Color Green
        exit 0
    }
}
finally {
    Pop-Location
}
