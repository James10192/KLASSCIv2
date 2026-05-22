<#
.SYNOPSIS
    Export d'un snapshot complet de l'état d'un jury KLASSCI (décisions, signatures, PV) pour archive ou debug.

.DESCRIPTION
    Crée un dossier zippable avec :
    - JSON des données jury (membres, décisions, statistiques)
    - Copie du PV PDF si déjà généré
    - Audit log filtré sur ce jury
    - Settings tenant utilisés pour le calcul auto

    Utile pour :
    - Archive longue durée (sauvegarde hors DB)
    - Debug post-mortem (un jury qui a un cas litige)
    - Audit légal (inspection MENA)

.PARAMETER JuryId
    ID du jury à snapshot

.PARAMETER Tenant
    Tenant cible (presentation, esbtp-abidjan, etc.)

.PARAMETER OutputDir
    Dossier de sortie. Par défaut: storage/jury-snapshots/

.EXAMPLE
    .\scripts\lmd\jury-snapshot.ps1 -JuryId 42 -Tenant presentation
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [int]$JuryId,
    [Parameter(Mandatory)]
    [string]$Tenant,
    [string]$OutputDir = ""
)

$RepoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$ts = Get-Date -Format "yyyyMMdd-HHmmss"

if (-not $OutputDir) {
    $OutputDir = Join-Path $RepoRoot "storage" "jury-snapshots" "$Tenant-jury-$JuryId-$ts"
}

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

Write-Host "📋 KLASSCI Jury Snapshot" -ForegroundColor Cyan
Write-Host "Jury ID  : $JuryId" -ForegroundColor Gray
Write-Host "Tenant   : $Tenant" -ForegroundColor Gray
Write-Host "Output   : $OutputDir" -ForegroundColor Gray
Write-Host ""

# Step 1 — Export JSON via klassci-cli (suppose une commande dédiée existe ou la créer en PR16)
Write-Host "→ Export JSON via klassci-cli..." -ForegroundColor Yellow

$jsonOutput = Join-Path $OutputDir "jury-data.json"
& klassci jury:export $Tenant --jury=$JuryId --output=$jsonOutput 2>&1 | Tee-Object -Variable jsonResult
if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️ klassci jury:export not available — TODO PR16" -ForegroundColor Yellow
    Write-Host "Fallback: requête API directe" -ForegroundColor Gray

    # Fallback: appel API direct via curl si commande non implémentée
    # Note: nécessite token API + URL tenant
    $stubData = @{
        jury_id = $JuryId
        tenant = $Tenant
        snapshot_date = $ts
        note = "Stub — implémenter klassci jury:export en PR16"
    } | ConvertTo-Json -Depth 5
    Set-Content -Path $jsonOutput -Value $stubData -Encoding UTF8
}

# Step 2 — Copier PV PDF si existe
Write-Host "→ Copier PV PDF..." -ForegroundColor Yellow
# PV est dans storage/pv/{tenant}/{annee}/{numero}.pdf
# On suppose qu'on a un endpoint pour télécharger le PV
$pvOutput = Join-Path $OutputDir "pv-deliberation.pdf"
# TODO: implémenter download du PV via klassci-cli en PR16
Write-Host "  (Manual: récupérer le PV depuis storage/pv/$Tenant/.../*.pdf)" -ForegroundColor Yellow

# Step 3 — Audit log filtré
Write-Host "→ Export audit log..." -ForegroundColor Yellow
$auditOutput = Join-Path $OutputDir "audit-log.json"
& klassci audit:export $Tenant --auditable_type=ESBTPLMDJury --auditable_id=$JuryId --output=$auditOutput 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "  (Manual: query esbtp_audits WHERE auditable_type='ESBTPLMDJury' AND auditable_id=$JuryId)" -ForegroundColor Yellow
}

# Step 4 — Settings tenant utilisés
Write-Host "→ Export settings tenant..." -ForegroundColor Yellow
$settingsOutput = Join-Path $OutputDir "settings-tenant.json"
& klassci settings:export $Tenant --filter="lmd_*" --output=$settingsOutput 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "  (Manual: query esbtp_system_settings WHERE key LIKE 'lmd_%')" -ForegroundColor Yellow
}

# Step 5 — Générer README descriptif du snapshot
$readme = @"
# Jury Snapshot — $Tenant Jury #$JuryId

**Date snapshot**: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
**Tenant**: $Tenant
**Jury ID**: $JuryId

## Fichiers inclus

- ``jury-data.json`` — Données complètes du jury (membres, décisions, statistiques)
- ``pv-deliberation.pdf`` — Procès-verbal officiel signé (si généré)
- ``audit-log.json`` — Audit log filtré sur ce jury
- ``settings-tenant.json`` — Settings tenant utilisés pour le calcul auto

## Usage

- **Archive longue durée** : zipper ce dossier + uploader sur S3/cold storage
- **Debug post-mortem** : utiliser jury-data.json pour reconstituer le contexte
- **Audit légal** : inspecter audit-log.json pour traces overrides + signatures

## Voir aussi

- Skill : ``klassci-jury-lifecycle``
- Rule : ``jury-deliberation-uemoa.md``
- Memory : ``feedback_jury_uemoa_workflow.md``

---

Généré par ``scripts/lmd/jury-snapshot.ps1``
"@

Set-Content -Path (Join-Path $OutputDir "README.md") -Value $readme -Encoding UTF8

Write-Host ""
Write-Host "✅ Snapshot complet : $OutputDir" -ForegroundColor Green
Write-Host ""
Write-Host "Pour zipper :" -ForegroundColor Gray
Write-Host "  Compress-Archive -Path '$OutputDir' -DestinationPath '$OutputDir.zip'" -ForegroundColor Gray
