param(
    [Parameter(Position = 0)]
    [string]$Command,

    [Parameter(Position = 1)]
    [string]$Tenant = "presentation",

    [switch]$Json
)

$ErrorActionPreference = "Stop"

function Get-KlassciConfig {
    param([string]$TenantCode)

    $tenants = @{
        "presentation" = "https://presentation.klassci.com"
    }

    $baseUrl = $env:KLASSCI_CLI_BASE_URL
    if (-not $baseUrl) {
        $tenantKey = "KLASSCI_CLI_BASE_URL_{0}" -f ($TenantCode.ToUpper() -replace '-', '_')
        $baseUrl = [Environment]::GetEnvironmentVariable($tenantKey)
    }
    if (-not $baseUrl) {
        $baseUrl = $tenants[$TenantCode]
    }

    $token = $env:KLASSCI_CLI_TOKEN
    if (-not $token) {
        $tenantKey = "KLASSCI_CLI_TOKEN_{0}" -f ($TenantCode.ToUpper() -replace '-', '_')
        $token = [Environment]::GetEnvironmentVariable($tenantKey)
    }

    return @{
        Tenant = $TenantCode
        BaseUrl = $baseUrl
        Token = $token
    }
}

function Invoke-KlassciApi {
    param(
        [string]$Method,
        [string]$Path,
        [hashtable]$Config,
        [object]$Body = $null
    )

    if (-not $Config.BaseUrl) {
        throw "No base URL configured for tenant '$($Config.Tenant)'."
    }
    if (-not $Config.Token) {
        throw "No CLI token configured for tenant '$($Config.Tenant)'. Set KLASSCI_CLI_TOKEN_$($Config.Tenant.ToUpper().Replace('-', '_'))."
    }

    $headers = @{
        "Accept" = "application/json"
        "Authorization" = "Bearer $($Config.Token)"
    }

    $uri = "{0}/api/cli{1}" -f $Config.BaseUrl.TrimEnd('/'), $Path
    if ($Body -ne $null) {
        return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers -ContentType "application/json" -Body ($Body | ConvertTo-Json -Depth 8)
    }

    return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers
}

switch ($Command) {
    "doctor" {
        if ($Json.IsPresent) {
            php artisan klassci:doctor --json
        } else {
            php artisan klassci:doctor
        }
        break
    }
    "pull" {
        $cfg = Get-KlassciConfig -TenantCode $Tenant
        Invoke-KlassciApi -Method "POST" -Path "/pull" -Config $cfg | ConvertTo-Json -Depth 8
        break
    }
    "migrate" {
        $cfg = Get-KlassciConfig -TenantCode $Tenant
        Invoke-KlassciApi -Method "POST" -Path "/migrate" -Config $cfg | ConvertTo-Json -Depth 8
        break
    }
    "cache:clear" {
        $cfg = Get-KlassciConfig -TenantCode $Tenant
        Invoke-KlassciApi -Method "POST" -Path "/cache/clear" -Config $cfg | ConvertTo-Json -Depth 8
        break
    }
    default {
        Write-Host "Usage:" -ForegroundColor Yellow
        Write-Host "  .\klassci-cli.ps1 doctor [--Json]"
        Write-Host "  .\klassci-cli.ps1 pull [presentation]"
        Write-Host "  .\klassci-cli.ps1 migrate [presentation]"
        Write-Host "  .\klassci-cli.ps1 cache:clear [presentation]"
        exit 1
    }
}
