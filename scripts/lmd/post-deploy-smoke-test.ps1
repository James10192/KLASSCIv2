<#
.SYNOPSIS
    Smoke tests post-déploiement via curl HTTP — vérifie les routes critiques répondent 200/302 sur chaque tenant.

.PARAMETER AllTenants
    Si présent, check les 6 tenants. Sinon, juste presentation.

.PARAMETER Tenants
    Liste explicite des tenants. Override -AllTenants.

.EXAMPLE
    .\scripts\lmd\post-deploy-smoke-test.ps1
    .\scripts\lmd\post-deploy-smoke-test.ps1 -AllTenants
    .\scripts\lmd\post-deploy-smoke-test.ps1 -Tenants @('presentation', 'esbtp-abidjan')
#>

[CmdletBinding()]
param(
    [switch]$AllTenants,
    [string[]]$Tenants = $null
)

if (-not $Tenants) {
    if ($AllTenants) {
        $Tenants = @('presentation', 'esbtp-abidjan', 'esbtp-yakro', 'ephrata', 'hetec', 'rostan')
    } else {
        $Tenants = @('presentation')
    }
}

# Tenant URLs (override si subdomain diff)
$TenantUrls = @{
    'presentation' = 'https://presentation.klassci.com'
    'esbtp-abidjan' = 'https://esbtp-abidjan.klassci.com'
    'esbtp-yakro' = 'https://esbtp-yakro.klassci.com'
    'ephrata' = 'https://ephrata.klassci.com'
    'hetec' = 'https://hetec.klassci.com'
    'rostan' = 'https://rostan.klassci.com'
}

# Routes critiques à smoke-test (URL + expected status)
$Routes = @(
    @{ path = "/"; expected = @(200, 302); name = "Homepage" },
    @{ path = "/login"; expected = @(200); name = "Login page" },
    @{ path = "/esbtp/emploi-temps"; expected = @(302, 401); name = "Emploi-temps (requires auth)" },
    @{ path = "/esbtp/lmd/planning"; expected = @(302, 401); name = "LMD planning (requires auth)" },
    @{ path = "/install"; expected = @(302); name = "Install (should redirect)" }
)

Write-Host "🩺 KLASSCI Post-Deploy Smoke Tests" -ForegroundColor Cyan
Write-Host "Tenants : $($Tenants -join ', ')" -ForegroundColor Gray
Write-Host ""

$allOk = $true

foreach ($tenant in $Tenants) {
    if (-not $TenantUrls.ContainsKey($tenant)) {
        Write-Host "⚠️ Tenant URL inconnue: $tenant" -ForegroundColor Yellow
        continue
    }

    $baseUrl = $TenantUrls[$tenant]
    Write-Host "──── Tenant: $tenant ($baseUrl) ────" -ForegroundColor Cyan

    foreach ($route in $Routes) {
        $url = "$baseUrl$($route.path)"
        try {
            $response = Invoke-WebRequest -Uri $url -MaximumRedirection 0 -ErrorAction Stop -SkipHttpErrorCheck
            $code = [int]$response.StatusCode
        } catch [System.Net.WebException] {
            $code = [int]$_.Exception.Response.StatusCode
        } catch {
            $code = 0
        }

        $expected = $route.expected
        $isOk = $expected -contains $code

        $statusMsg = if ($isOk) { "✅" } else { "❌" }
        $color = if ($isOk) { "Green" } else { "Red" }

        Write-Host "  $statusMsg $($route.name) [$code] $url" -ForegroundColor $color

        if (-not $isOk) {
            $allOk = $false
            Write-Host "     Expected: $($expected -join ' or '), got: $code" -ForegroundColor Red
        }
    }
    Write-Host ""
}

Write-Host ""
if ($allOk) {
    Write-Host "✅ Tous les smoke tests passent." -ForegroundColor Green
    exit 0
} else {
    Write-Host "❌ Au moins 1 smoke test échoue. Investigation requise." -ForegroundColor Red
    exit 1
}
