param(
    [Parameter(Position = 0)]
    [string]$Command,

    [Parameter(Position = 1)]
    [string]$Tenant = "presentation",

    [Parameter(Position = 2, ValueFromRemainingArguments = $true)]
    [string[]]$ExtraArgs = @(),

    [switch]$Json
)

$ErrorActionPreference = "Stop"

function Get-KlassciConfig {
    param([string]$TenantCode)

    $globalConfigPath = Join-Path $HOME ".klassci\config.json"
    $tenants = @{
        "presentation" = "https://presentation.klassci.com"
    }
    $globalConfig = $null

    if (Test-Path $globalConfigPath) {
        try {
            $globalConfig = Get-Content $globalConfigPath -Raw | ConvertFrom-Json
        } catch {
            throw "Unable to read ${globalConfigPath}: $($_.Exception.Message)"
        }
    }

    $tenantConfig = $null
    if ($globalConfig -and $globalConfig.tenants) {
        $tenantConfig = $globalConfig.tenants.PSObject.Properties[$TenantCode].Value
    }

    $baseUrl = $env:KLASSCI_CLI_BASE_URL
    if (-not $baseUrl) {
        $tenantKey = "KLASSCI_CLI_BASE_URL_{0}" -f ($TenantCode.ToUpper() -replace '-', '_')
        $baseUrl = [Environment]::GetEnvironmentVariable($tenantKey)
    }
    if (-not $baseUrl) {
        $baseUrl = $tenantConfig.url
    }
    if (-not $baseUrl) {
        $baseUrl = $tenants[$TenantCode]
    }

    $token = $env:KLASSCI_CLI_TOKEN
    if (-not $token) {
        $tenantKey = "KLASSCI_CLI_TOKEN_{0}" -f ($TenantCode.ToUpper() -replace '-', '_')
        $token = [Environment]::GetEnvironmentVariable($tenantKey)
    }
    if (-not $token) {
        $token = $tenantConfig.token
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

    $uri = "{0}/api/cli{1}" -f $Config.BaseUrl.TrimEnd('/'), $Path
    $curlArgs = @(
        "-sS",
        "-X", $Method,
        $uri,
        "-H", "Accept: application/json",
        "-H", "Authorization: Bearer $($Config.Token)"
    )

    if ($Body -ne $null) {
        $curlArgs += @(
            "-H", "Content-Type: application/json",
            "-d", ($Body | ConvertTo-Json -Depth 8 -Compress)
        )
    }

    $tmpFile = [System.IO.Path]::GetTempFileName()
    try {
        $curlArgs += @("-o", $tmpFile)
        & curl.exe @curlArgs | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw "curl failed with exit code $LASTEXITCODE"
        }

        $response = Get-Content $tmpFile -Raw
        if (-not $response) {
            return $null
        }

        return ConvertFrom-KlassciJson -Json $response
    } finally {
        Remove-Item $tmpFile -ErrorAction SilentlyContinue
    }
}

function Invoke-KlassciUrl {
    param(
        [string]$Method,
        [string]$Url,
        [hashtable]$Headers = @{},
        [object]$Body = $null
    )

    $curlArgs = @(
        "-sS",
        "-X", $Method,
        $Url
    )

    foreach ($key in $Headers.Keys) {
        $curlArgs += @("-H", "{0}: {1}" -f $key, $Headers[$key])
    }

    if ($Body -ne $null) {
        $curlArgs += @(
            "-H", "Content-Type: application/json",
            "-d", ($Body | ConvertTo-Json -Depth 8 -Compress)
        )
    }

    $tmpFile = [System.IO.Path]::GetTempFileName()
    try {
        $curlArgs += @("-o", $tmpFile)
        & curl.exe @curlArgs | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw "curl failed with exit code $LASTEXITCODE"
        }

        $response = Get-Content $tmpFile -Raw
        if (-not $response) {
            return $null
        }

        return ConvertFrom-KlassciJson -Json $response
    } finally {
        Remove-Item $tmpFile -ErrorAction SilentlyContinue
    }
}

function Invoke-KlassciUrlRaw {
    param(
        [string]$Method,
        [string]$Url,
        [hashtable]$Headers = @{},
        [object]$Body = $null
    )

    $curlArgs = @(
        "-sS",
        "-X", $Method,
        $Url
    )

    foreach ($key in $Headers.Keys) {
        $curlArgs += @("-H", "{0}: {1}" -f $key, $Headers[$key])
    }

    if ($Body -ne $null) {
        $curlArgs += @(
            "-H", "Content-Type: application/json",
            "-d", ($Body | ConvertTo-Json -Depth 8 -Compress)
        )
    }

    $tmpFile = [System.IO.Path]::GetTempFileName()
    try {
        $curlArgs += @("-o", $tmpFile)
        & curl.exe @curlArgs | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw "curl failed with exit code $LASTEXITCODE"
        }

        return (Get-Content $tmpFile -Raw)
    } finally {
        Remove-Item $tmpFile -ErrorAction SilentlyContinue
    }
}

function ConvertFrom-KlassciJson {
    param([string]$Json)

    try {
        return $Json | ConvertFrom-Json
    } catch {
        Add-Type -AssemblyName System.Web.Extensions
        $serializer = New-Object System.Web.Script.Serialization.JavaScriptSerializer
        $serializer.MaxJsonLength = 67108864
        return $serializer.DeserializeObject($Json)
    }
}

function Get-LmdCoverageReport {
    param([string]$TenantCode)

    $cfg = Get-KlassciConfig -TenantCode $TenantCode
    $tree = Invoke-KlassciApi -Method "GET" -Path "/lmd/tree" -Config $cfg
    $rawClassesJson = Invoke-KlassciUrlRaw -Method "GET" -Url ("{0}/api/classes" -f $cfg.BaseUrl.TrimEnd('/')) -Headers @{ "Accept" = "application/json" }
    $domaines = @()

    if ($tree -and $tree.data -and $tree.data.domaines) {
        $domaines = @($tree.data.domaines)
    }

    $parcours = @()
    foreach ($domaine in $domaines) {
        $mentions = @()
        if ($domaine.mentions) {
            $mentions = @($domaine.mentions)
        }

        foreach ($mention in $mentions) {
            $mentionParcours = @()
            if ($mention.parcours) {
                $mentionParcours = @($mention.parcours)
            }

            foreach ($item in $mentionParcours) {
                $parcours += [PSCustomObject]@{
                    id = [int]$item.id
                    name = [string]$item.name
                    mention = [string]$mention.name
                    domaine = [string]$domaine.name
                    filiere = [string]$item.filiere.name
                }
            }
        }
    }

    $classMatches = [regex]::Matches($rawClassesJson, '"id":(?<id>\d+),"name":"(?<name>(?:\\.|[^"])*)","libelle":.*?"is_active":true,"systeme_academique":"LMD","parcours_id":(?<parcours>null|\d+)', [System.Text.RegularExpressions.RegexOptions]::Singleline)
    $lmdClasses = @()
    foreach ($match in $classMatches) {
        $parcoursValue = $match.Groups['parcours'].Value
        $lmdClasses += [PSCustomObject]@{
            id = [int]$match.Groups['id'].Value
            name = [regex]::Unescape($match.Groups['name'].Value)
            parcours_id = if ($parcoursValue -eq 'null') { $null } else { [int]$parcoursValue }
        }
    }

    $classParcoursIds = @($lmdClasses | Where-Object { $_.parcours_id } | ForEach-Object { [int]$_.parcours_id } | Sort-Object -Unique)
    $missingParcours = @($parcours | Where-Object { $classParcoursIds -notcontains $_.id })
    $classesWithoutParcours = @($lmdClasses | Where-Object { -not $_.parcours_id } | ForEach-Object {
        [PSCustomObject]@{
            id = $_.id
            name = $_.name
        }
    })
    $matchedParcours = @($parcours | Where-Object { $classParcoursIds -contains $_.id })

    return [PSCustomObject]@{
        success = $true
        tenant = $TenantCode
        totals = [PSCustomObject]@{
            parcours_in_tree = @($parcours).Count
            lmd_classes = @($lmdClasses).Count
            parcours_with_class = @($matchedParcours).Count
            parcours_missing_class = @($missingParcours).Count
            classes_without_parcours_id = @($classesWithoutParcours).Count
        }
        parcours_with_class = $matchedParcours
        parcours_missing_class = $missingParcours
        classes_without_parcours_id = $classesWithoutParcours
    }
}

function New-KlassciQueryString {
    param([hashtable]$Query)

    if (-not $Query -or $Query.Count -eq 0) {
        return ""
    }

    $pairs = @()
    foreach ($key in $Query.Keys) {
        if ($null -eq $Query[$key] -or $Query[$key] -eq "") {
            continue
        }

        $pairs += ("{0}={1}" -f [uri]::EscapeDataString([string]$key), [uri]::EscapeDataString([string]$Query[$key]))
    }

    if ($pairs.Count -eq 0) {
        return ""
    }

    return "?" + ($pairs -join "&")
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
    "classes" {
        $cfg = Get-KlassciConfig -TenantCode $Tenant
        Invoke-KlassciApi -Method "GET" -Path "/classes" -Config $cfg | ConvertTo-Json -Depth 8
        break
    }
    "lmd:tree" {
        $cfg = Get-KlassciConfig -TenantCode $Tenant
        Invoke-KlassciApi -Method "GET" -Path "/lmd/tree" -Config $cfg | ConvertTo-Json -Depth 8
        break
    }
    "classes:raw" {
        $cfg = Get-KlassciConfig -TenantCode $Tenant
        Invoke-KlassciUrlRaw -Method "GET" -Url ("{0}/api/classes" -f $cfg.BaseUrl.TrimEnd('/')) -Headers @{ "Accept" = "application/json" }
        break
    }
    "lmd:coverage" {
        Get-LmdCoverageReport -TenantCode $Tenant | ConvertTo-Json -Depth 8
        break
    }
    "resultats:diagnose" {
        if ($ExtraArgs.Count -lt 1) {
            throw "Usage: .\klassci-cli.ps1 resultats:diagnose [tenant] <etudiant_id> [classe_id] [annee_universitaire_id] [periode] [include_all_statuses]"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $query = @{}
        if ($ExtraArgs.Count -ge 2) { $query["classe_id"] = $ExtraArgs[1] }
        if ($ExtraArgs.Count -ge 3) { $query["annee_universitaire_id"] = $ExtraArgs[2] }
        if ($ExtraArgs.Count -ge 4) { $query["periode"] = $ExtraArgs[3] }
        if ($ExtraArgs.Count -ge 5) { $query["include_all_statuses"] = $ExtraArgs[4] }

        $path = "/resultats/etudiant/{0}/diagnose{1}" -f $ExtraArgs[0], (New-KlassciQueryString -Query $query)
        Invoke-KlassciApi -Method "GET" -Path $path -Config $cfg | ConvertTo-Json -Depth 8
        break
    }
    "resultats:bulletin-consistency-diagnose" {
        if ($ExtraArgs.Count -lt 4) {
            throw "Usage: .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose [tenant] <etudiant_id> <classe_id> <annee_universitaire_id> <periode>"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $query = @{
            "classe_id" = $ExtraArgs[1]
            "annee_universitaire_id" = $ExtraArgs[2]
            "periode" = $ExtraArgs[3]
        }

        $path = "/resultats/etudiant/{0}/bulletin-consistency-diagnose{1}" -f $ExtraArgs[0], (New-KlassciQueryString -Query $query)
        Invoke-KlassciApi -Method "GET" -Path $path -Config $cfg | ConvertTo-Json -Depth 10
        break
    }
    default {
        Write-Host "Usage:" -ForegroundColor Yellow
        Write-Host "  .\klassci-cli.ps1 doctor [--Json]"
        Write-Host "  .\klassci-cli.ps1 pull [presentation]"
        Write-Host "  .\klassci-cli.ps1 migrate [presentation]"
        Write-Host "  .\klassci-cli.ps1 cache:clear [presentation]"
        Write-Host "  .\klassci-cli.ps1 classes [presentation]"
        Write-Host "  .\klassci-cli.ps1 classes:raw [presentation]"
        Write-Host "  .\klassci-cli.ps1 lmd:tree [presentation]"
        Write-Host "  .\klassci-cli.ps1 lmd:coverage [presentation]"
        Write-Host "  .\klassci-cli.ps1 resultats:diagnose [presentation] <etudiant_id> [classe_id] [annee_universitaire_id] [periode] [include_all_statuses]"
        Write-Host "  .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose [presentation] <etudiant_id> <classe_id> <annee_universitaire_id> <periode>"
        exit 1
    }
}
