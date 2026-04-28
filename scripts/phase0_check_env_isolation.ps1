param(
  [string]$EnvFile = ".env"
)

$ErrorActionPreference = "Stop"

if (!(Test-Path $EnvFile)) {
  throw "Arquivo não encontrado: $EnvFile"
}

$kv = @{}
Get-Content $EnvFile | ForEach-Object {
  $line = $_.Trim()
  if ($line -eq "" -or $line.StartsWith("#")) { return }
  $parts = $line.Split("=", 2)
  if ($parts.Length -ne 2) { return }
  $kv[$parts[0].Trim()] = $parts[1].Trim()
}

$appEnv = ($kv["APP_ENV"] ?? "").ToLower()
if ($appEnv -eq "") { $appEnv = "local" }

$dbSchema = ($kv["DB_SCHEMA"] ?? "").ToLower()
$tgDryRun = ($kv["TELEGRAM_DRY_RUN"] ?? "").ToLower()

Write-Host "APP_ENV=$appEnv"
Write-Host "DB_SCHEMA=$dbSchema"
Write-Host "TELEGRAM_DRY_RUN=$tgDryRun"

if ($appEnv -ne "production" -and $dbSchema -eq "app_prod") {
  throw "INSEGURO: APP_ENV=$appEnv usando DB_SCHEMA=app_prod"
}

Write-Host "OK: isolamento básico validado."

