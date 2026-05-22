<#
.SYNOPSIS
    Audit complet des sites consommateurs de matières d'une classe — LMD-aware ou BTS-only.

.DESCRIPTION
    Grep les patterns canonical/anti-canonical dans app/Http/Controllers/ et resources/views/
    Génère un rapport tableau croisé en Markdown dans tmp/audit-lmd-bts-YYYYMMDD.md

.PARAMETER OutputFile
    Chemin du fichier de sortie. Par défaut: tmp/audit-lmd-bts-<date>.md

.EXAMPLE
    .\scripts\lmd\audit-callsites.ps1
    .\scripts\lmd\audit-callsites.ps1 -OutputFile "audit.md"
#>

[CmdletBinding()]
param(
    [string]$OutputFile = ""
)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$ProjectName = "KLASSCI emploi-temps LMD/BTS audit"

if (-not $OutputFile) {
    $tmpDir = Join-Path $RepoRoot "tmp"
    if (-not (Test-Path $tmpDir)) { New-Item -ItemType Directory -Path $tmpDir | Out-Null }
    $OutputFile = Join-Path $tmpDir ("audit-lmd-bts-" + (Get-Date -Format "yyyyMMdd-HHmmss") + ".md")
}

Write-Host "🔍 $ProjectName" -ForegroundColor Cyan
Write-Host "Repository: $RepoRoot" -ForegroundColor Gray
Write-Host "Output    : $OutputFile" -ForegroundColor Gray
Write-Host ""

Push-Location $RepoRoot
try {
    function Grep-Pattern {
        param(
            [string]$Pattern,
            [string]$Path = "app/Http/Controllers/"
        )
        # Use git grep for performance + respects .gitignore
        $results = & git grep -nE "$Pattern" -- $Path 2>$null
        if (-not $results) { return @() }
        return $results
    }

    # Pattern 1 : $classe->matieres direct (BTS-only)
    Write-Host "Pattern 1 : `$classe->matieres direct..." -ForegroundColor Yellow
    $pattern1 = Grep-Pattern -Pattern '\$classe->matieres\b' -Path "app/Http/Controllers/"

    # Pattern 2 : whereHas('filieres') (BTS-only)
    Write-Host "Pattern 2 : whereHas('filieres')..." -ForegroundColor Yellow
    $pattern2 = Grep-Pattern -Pattern "whereHas\('filieres'\)" -Path "app/Http/Controllers/"

    # Pattern 3 : whereHas('niveaux') (BTS-only)
    Write-Host "Pattern 3 : whereHas('niveaux')..." -ForegroundColor Yellow
    $pattern3 = Grep-Pattern -Pattern "whereHas\('niveaux'\)" -Path "app/Http/Controllers/"

    # Pattern 4 : MatiereTreeBuilder usage (LMD-aware)
    Write-Host "Pattern 4 : MatiereTreeBuilder usage..." -ForegroundColor Yellow
    $pattern4 = Grep-Pattern -Pattern "MatiereTreeBuilder" -Path "app/"

    # Pattern 5 : ESBTPPlanificationAcademique usage (canonical source)
    Write-Host "Pattern 5 : ESBTPPlanificationAcademique..." -ForegroundColor Yellow
    $pattern5 = Grep-Pattern -Pattern "ESBTPPlanificationAcademique" -Path "app/Http/Controllers/"

    # Pattern 6 : getMatieresClasse helper (LMD-aware)
    Write-Host "Pattern 6 : getMatieresClasse helper..." -ForegroundColor Yellow
    $pattern6 = Grep-Pattern -Pattern "getMatieresClasse" -Path "app/"

    # Generate report
    $report = @"
# Audit LMD/BTS — $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

> Sources de matières d'une classe — verdict LMD-aware ou BTS-only.
> Rule de référence : `.claude/rules/lmd-bts-matieres-single-source.md`

## Résumé

| Catégorie | Pattern | Hits | Verdict global |
|---|---|---|---|
| 🚨 BTS-only #1 | ``\``$classe->matieres direct | $($pattern1.Count) | $(if ($pattern1.Count -gt 0) { "❌ À FIXER" } else { "✅ OK" }) |
| 🚨 BTS-only #2 | whereHas('filieres') | $($pattern2.Count) | $(if ($pattern2.Count -gt 0) { "❌ À FIXER" } else { "✅ OK" }) |
| 🚨 BTS-only #3 | whereHas('niveaux') | $($pattern3.Count) | $(if ($pattern3.Count -gt 0) { "❌ À FIXER" } else { "✅ OK" }) |
| ✅ Canonical | MatiereTreeBuilder | $($pattern4.Count) | ✅ Usage OK |
| ✅ Canonical | ESBTPPlanificationAcademique | $($pattern5.Count) | ✅ Usage OK |
| ✅ Canonical | getMatieresClasse helper | $($pattern6.Count) | ✅ Usage OK |

---

## 🚨 BTS-only #1 — ``\``$classe->matieres direct

"@

    if ($pattern1.Count -gt 0) {
        $report += "`n| File:Line | Code |`n|---|---|`n"
        foreach ($line in $pattern1) {
            $report += "| ``$line`` |`n"
        }
        $report += "`n**Action** : Refactor ces sites pour utiliser ``MatiereTreeBuilder`` ou ajouter guard ``abort_if(systeme_academique === 'LMD')``.`n"
    } else {
        $report += "`n✅ Aucun hit — propre.`n"
    }

    $report += "`n---`n`n## 🚨 BTS-only #2 — whereHas('filieres')`n"

    if ($pattern2.Count -gt 0) {
        $report += "`n| File:Line | Code |`n|---|---|`n"
        foreach ($line in $pattern2) {
            $report += "| ``$line`` |`n"
        }
        $report += "`n**Action** : Refactor pour utiliser ``MatiereTreeBuilder::buildForPlanning()`` ou ``ESBTPPlanificationAcademique`` direct.`n"
    } else {
        $report += "`n✅ Aucun hit — propre.`n"
    }

    $report += "`n---`n`n## 🚨 BTS-only #3 — whereHas('niveaux')`n"

    if ($pattern3.Count -gt 0) {
        $report += "`n| File:Line | Code |`n|---|---|`n"
        foreach ($line in $pattern3) {
            $report += "| ``$line`` |`n"
        }
        $report += "`n**Action** : Mêmes recommandations.`n"
    } else {
        $report += "`n✅ Aucun hit — propre.`n"
    }

    $report += "`n---`n`n## ✅ Canonical usage — MatiereTreeBuilder (info)`n"

    if ($pattern4.Count -gt 0) {
        $report += "`n| File:Line | Code |`n|---|---|`n"
        foreach ($line in $pattern4) {
            $report += "| ``$line`` |`n"
        }
    } else {
        $report += "`n⚠️ Aucun hit — MatiereTreeBuilder n'est pas encore utilisé. Voir master plan PR1.`n"
    }

    $report += @"

---

## Score global

- **Sites BTS-only** (à fixer) : $($pattern1.Count + $pattern2.Count + $pattern3.Count)
- **Sites canonical** (OK) : $($pattern4.Count + $pattern5.Count + $pattern6.Count)
- **Ratio** : $(if ($pattern1.Count + $pattern2.Count + $pattern3.Count -eq 0) { "100% canonical ✅" } else { "À améliorer" })

## Voir aussi

- Rule : ``.claude/rules/lmd-bts-matieres-single-source.md``
- Memory : ``feedback_matiere_tree_builder_canonical.md``
- Master plan : ``docs/MASTER-PLAN-emploi-temps-lmd-unification.md``
- Skill : ``klassci-lmd-bts-audit`` (équivalent manuel interactif)

---

*Généré par ``scripts/lmd/audit-callsites.ps1`` le $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')*
"@

    Set-Content -Path $OutputFile -Value $report -Encoding UTF8

    Write-Host ""
    Write-Host "✅ Rapport généré : $OutputFile" -ForegroundColor Green
    Write-Host ""
    Write-Host "Résumé :" -ForegroundColor Cyan
    Write-Host "  BTS-only : `$classe->matieres   = $($pattern1.Count) hits" -ForegroundColor $(if ($pattern1.Count -gt 0) { "Red" } else { "Green" })
    Write-Host "  BTS-only : whereHas('filieres') = $($pattern2.Count) hits" -ForegroundColor $(if ($pattern2.Count -gt 0) { "Red" } else { "Green" })
    Write-Host "  BTS-only : whereHas('niveaux')  = $($pattern3.Count) hits" -ForegroundColor $(if ($pattern3.Count -gt 0) { "Red" } else { "Green" })
    Write-Host "  Canonical: MatiereTreeBuilder    = $($pattern4.Count) hits" -ForegroundColor Green
    Write-Host ""

    if ($pattern1.Count + $pattern2.Count + $pattern3.Count -gt 0) {
        Write-Host "⚠️ Des sites BTS-only restent à fixer. Voir le rapport pour les détails." -ForegroundColor Yellow
        exit 1
    } else {
        Write-Host "✅ Aucun site BTS-only détecté. Architecture canonical respectée." -ForegroundColor Green
        exit 0
    }
}
finally {
    Pop-Location
}
