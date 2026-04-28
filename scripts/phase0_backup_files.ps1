param(
  [string]$Label = "pre-pwa-freeze-20260428"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$dest = Join-Path $root (".backups/" + $Label)

New-Item -ItemType Directory -Force -Path $dest | Out-Null

$files = @(".env", "render.yaml", "docker-compose.yml")
foreach ($f in $files) {
  $src = Join-Path $root $f
  if (Test-Path $src) {
    Copy-Item -Force -LiteralPath $src -Destination (Join-Path $dest $f)
  }
}

Write-Host "Backup salvo em: $dest"

