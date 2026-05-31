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

function Invoke-KlassciApiJson {
    param(
        [string]$Method,
        [string]$Path,
        [hashtable]$Config,
        [hashtable]$Body = @{}
    )

    Invoke-KlassciApi -Method $Method -Path $Path -Config $Config -Body $Body | ConvertTo-Json -Depth 10
}

function Get-BtsSemesterSnapshot {
    param(
        [hashtable]$Config,
        [string]$EtudiantId,
        [string]$ClasseId,
        [string]$AnneeUniversitaireId,
        [string]$Periode
    )

    $query = @{
        "classe_id" = $ClasseId
        "annee_universitaire_id" = $AnneeUniversitaireId
        "periode" = $Periode
    }

    $path = "/resultats/etudiant/{0}/bulletin-consistency-diagnose{1}" -f $EtudiantId, (New-KlassciQueryString -Query $query)
    return Invoke-KlassciApi -Method "GET" -Path $path -Config $Config
}

function Get-BtsAnnualSnapshotReport {
    param(
        [hashtable]$Config,
        [string]$EtudiantId,
        [string]$ClasseId,
        [string]$AnneeUniversitaireId,
        [string]$IncludeAllStatuses = "1"
    )

    $diagQuery = @{
        "classe_id" = $ClasseId
        "annee_universitaire_id" = $AnneeUniversitaireId
        "periode" = "annuel"
        "include_all_statuses" = $IncludeAllStatuses
    }

    $diagPath = "/resultats/etudiant/{0}/diagnose{1}" -f $EtudiantId, (New-KlassciQueryString -Query $diagQuery)
    $diagnose = Invoke-KlassciApi -Method "GET" -Path $diagPath -Config $Config

    $s1 = Get-BtsSemesterSnapshot -Config $Config -EtudiantId $EtudiantId -ClasseId $ClasseId -AnneeUniversitaireId $AnneeUniversitaireId -Periode "semestre1"
    $s2 = Get-BtsSemesterSnapshot -Config $Config -EtudiantId $EtudiantId -ClasseId $ClasseId -AnneeUniversitaireId $AnneeUniversitaireId -Periode "semestre2"

    $s1Current = $s1.data.snapshot.diagnostic.current
    $s2Current = $s2.data.snapshot.diagnostic.current
    $weights = $diagnose.data.averages.semester_weights

    $s1Effective = if ($null -ne $s1Current) { $s1Current.effective_total } else { $null }
    $s2Effective = if ($null -ne $s2Current) { $s2Current.effective_total } else { $null }
    $s1Raw = if ($null -ne $s1Current) { $s1Current.raw_total } else { $null }
    $s2Raw = if ($null -ne $s2Current) { $s2Current.raw_total } else { $null }

    $annualState = "no_data"
    $annualEffective = $null
    $annualRaw = $null
    $primarySemester = $null

    if ($null -ne $s1Effective -and $null -ne $s2Effective) {
        $totalWeight = [double]$weights.semester1 + [double]$weights.semester2
        if ($totalWeight -gt 0) {
            $annualEffective = [Math]::Round((([double]$s1Effective * [double]$weights.semester1) + ([double]$s2Effective * [double]$weights.semester2)) / $totalWeight, 2)
            $annualRaw = [Math]::Round((([double]$s1Raw * [double]$weights.semester1) + ([double]$s2Raw * [double]$weights.semester2)) / $totalWeight, 2)
            $annualState = "annual_complete"
        }
    } elseif ($null -ne $s1Effective) {
        $annualState = "annual_incomplete"
        $annualEffective = $s1Effective
        $annualRaw = $s1Raw
        $primarySemester = "semestre1"
    } elseif ($null -ne $s2Effective) {
        $annualState = "annual_incomplete"
        $annualEffective = $s2Effective
        $annualRaw = $s2Raw
        $primarySemester = "semestre2"
    }

    $annualRows = @()
    if ($diagnose.data.resultats_summary.by_class_and_periode) {
        $annualRows = @($diagnose.data.resultats_summary.by_class_and_periode | Where-Object { $_.periode -eq "annuel" })
    }

    return [PSCustomObject]@{
        student = $diagnose.data.student
        context = [PSCustomObject]@{
            classe_id = $ClasseId
            annee_universitaire_id = $AnneeUniversitaireId
            include_all_statuses = $IncludeAllStatuses
        }
        diagnose_annual = $diagnose.data.averages.requested_class
        semester_weights = $weights
        semester_snapshots = [PSCustomObject]@{
            semestre1 = [PSCustomObject]@{
                effective_total = $s1Effective
                raw_total = $s1Raw
                current_state = $s1.data.snapshot.current_state
                current_subjects = $s1.data.snapshot.current_subjects
            }
            semestre2 = [PSCustomObject]@{
                effective_total = $s2Effective
                raw_total = $s2Raw
                current_state = $s2.data.snapshot.current_state
                current_subjects = $s2.data.snapshot.current_subjects
            }
        }
        canonical_annual = [PSCustomObject]@{
            state = $annualState
            effective_total = $annualEffective
            raw_total = $annualRaw
            primary_semester = $primarySemester
        }
        stored_resultats_rows = [PSCustomObject]@{
            total = $diagnose.data.resultats_summary.total
            annual_rows = $annualRows
            by_class_and_periode = $diagnose.data.resultats_summary.by_class_and_periode
        }
        stored_bulletins_rows = [PSCustomObject]@{
            total = $diagnose.data.bulletins_summary.total
            entries = $diagnose.data.bulletins_summary.entries
        }
    }
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
    "resultats:bts-annual-snapshot" {
        if ($ExtraArgs.Count -lt 3) {
            throw "Usage: .\klassci-cli.ps1 resultats:bts-annual-snapshot [tenant] <etudiant_id> <classe_id> <annee_universitaire_id> [include_all_statuses]"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $includeAllStatuses = if ($ExtraArgs.Count -ge 4) { $ExtraArgs[3] } else { "1" }

        Get-BtsAnnualSnapshotReport `
            -Config $cfg `
            -EtudiantId $ExtraArgs[0] `
            -ClasseId $ExtraArgs[1] `
            -AnneeUniversitaireId $ExtraArgs[2] `
            -IncludeAllStatuses $includeAllStatuses | ConvertTo-Json -Depth 12
        break
    }
    "bts-tc:diagnose" {
        if ($ExtraArgs.Count -lt 1) {
            throw "Usage: .\klassci-cli.ps1 bts-tc:diagnose [presentation] <inscription_id>"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $path = "/bts-tc/inscriptions/{0}/diagnose" -f $ExtraArgs[0]
        Invoke-KlassciApi -Method "GET" -Path $path -Config $cfg | ConvertTo-Json -Depth 10
        break
    }
    "bts-tc:student-journey" {
        if ($ExtraArgs.Count -lt 1) {
            throw "Usage: .\klassci-cli.ps1 bts-tc:student-journey [presentation] <etudiant_id> [annee_universitaire_id]"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $query = @{}
        if ($ExtraArgs.Count -ge 2) { $query["annee_universitaire_id"] = $ExtraArgs[1] }

        $path = "/bts-tc/students/{0}/journey{1}" -f $ExtraArgs[0], (New-KlassciQueryString -Query $query)
        Invoke-KlassciApi -Method "GET" -Path $path -Config $cfg | ConvertTo-Json -Depth 10
        break
    }
    "bts-tc:orientation-check" {
        if ($ExtraArgs.Count -lt 1) {
            throw "Usage: .\klassci-cli.ps1 bts-tc:orientation-check [presentation] <classe_id>"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $path = "/bts-tc/classes/{0}/orientation-check" -f $ExtraArgs[0]
        Invoke-KlassciApi -Method "GET" -Path $path -Config $cfg | ConvertTo-Json -Depth 10
        break
    }
    "bts-tc:legacy-audit" {
        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $query = @{}
        if ($ExtraArgs.Count -ge 1) { $query["annee_universitaire_id"] = $ExtraArgs[0] }

        $path = "/bts-tc/legacy-audit{0}" -f (New-KlassciQueryString -Query $query)
        Invoke-KlassciApi -Method "GET" -Path $path -Config $cfg | ConvertTo-Json -Depth 10
        break
    }
    "bts-tc:results-consistency" {
        if ($ExtraArgs.Count -lt 1) {
            throw "Usage: .\klassci-cli.ps1 bts-tc:results-consistency [presentation] <etudiant_id> [annee_universitaire_id] [periode]"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $query = @{}
        if ($ExtraArgs.Count -ge 2) { $query["annee_universitaire_id"] = $ExtraArgs[1] }
        if ($ExtraArgs.Count -ge 3) { $query["periode"] = $ExtraArgs[2] }

        $path = "/bts-tc/students/{0}/results-consistency{1}" -f $ExtraArgs[0], (New-KlassciQueryString -Query $query)
        Invoke-KlassciApi -Method "GET" -Path $path -Config $cfg | ConvertTo-Json -Depth 10
        break
    }
    "bts-tc:mark-filiere-tc" {
        if ($ExtraArgs.Count -lt 1) {
            throw "Usage: .\klassci-cli.ps1 bts-tc:mark-filiere-tc [presentation] <filiere_id> [semestres_tronc_commun]"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $body = @{
            is_tronc_commun = $true
        }
        if ($ExtraArgs.Count -ge 2) { $body["semestres_tronc_commun"] = [int]$ExtraArgs[1] }

        $path = "/bts-tc/filieres/{0}/mark-tronc-commun" -f $ExtraArgs[0]
        Invoke-KlassciApiJson -Method "POST" -Path $path -Config $cfg -Body $body
        break
    }
    "bts-tc:add-target" {
        if ($ExtraArgs.Count -lt 2) {
            throw "Usage: .\klassci-cli.ps1 bts-tc:add-target [presentation] <source_classe_id> <target_classe_id> [semestre_activation] [sort_order]"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $body = @{
            target_classe_id = [int]$ExtraArgs[1]
        }
        if ($ExtraArgs.Count -ge 3) { $body["semestre_activation"] = [int]$ExtraArgs[2] }
        if ($ExtraArgs.Count -ge 4) { $body["sort_order"] = [int]$ExtraArgs[3] }

        $path = "/bts-tc/classes/{0}/targets" -f $ExtraArgs[0]
        Invoke-KlassciApiJson -Method "POST" -Path $path -Config $cfg -Body $body
        break
    }
    "bts-tc:orient" {
        if ($ExtraArgs.Count -lt 2) {
            throw "Usage: .\klassci-cli.ps1 bts-tc:orient [presentation] <inscription_id> <target_classe_id>"
        }

        $cfg = Get-KlassciConfig -TenantCode $Tenant
        $body = @{
            target_classe_id = [int]$ExtraArgs[1]
        }

        $path = "/bts-tc/inscriptions/{0}/orient" -f $ExtraArgs[0]
        Invoke-KlassciApiJson -Method "POST" -Path $path -Config $cfg -Body $body
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
        Write-Host "  .\klassci-cli.ps1 resultats:bts-annual-snapshot [presentation] <etudiant_id> <classe_id> <annee_universitaire_id> [include_all_statuses]"
        Write-Host "  .\klassci-cli.ps1 bts-tc:diagnose [presentation] <inscription_id>"
        Write-Host "  .\klassci-cli.ps1 bts-tc:student-journey [presentation] <etudiant_id> [annee_universitaire_id]"
        Write-Host "  .\klassci-cli.ps1 bts-tc:orientation-check [presentation] <classe_id>"
        Write-Host "  .\klassci-cli.ps1 bts-tc:legacy-audit [presentation] [annee_universitaire_id]"
        Write-Host "  .\klassci-cli.ps1 bts-tc:results-consistency [presentation] <etudiant_id> [annee_universitaire_id] [periode]"
        Write-Host "  .\klassci-cli.ps1 bts-tc:mark-filiere-tc [presentation] <filiere_id> [semestres_tronc_commun]"
        Write-Host "  .\klassci-cli.ps1 bts-tc:add-target [presentation] <source_classe_id> <target_classe_id> [semestre_activation] [sort_order]"
        Write-Host "  .\klassci-cli.ps1 bts-tc:orient [presentation] <inscription_id> <target_classe_id>"
        exit 1
    }
}
