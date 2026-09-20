$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path -LiteralPath (Join-Path $root '.claude\rules'))) {
    $root = (Get-Location).Path
}
$opencode = Join-Path $root '.opencode'
New-Item -ItemType Directory -Path $opencode -Force | Out-Null
$map = @{
    rules  = '.claude\rules'
    hooks  = '.claude\hooks'
    skills = '.claude\skills'
}
foreach ($name in $map.Keys) {
    $link = Join-Path $opencode $name
    $target = Join-Path $root $map[$name]
    if (Test-Path -LiteralPath $link) { continue }
    cmd /c "mklink /J `"$link`" `"$target`""
}
Write-Host "OpenCode -> Claude : rules, hooks, skills"
