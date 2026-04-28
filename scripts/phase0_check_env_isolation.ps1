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

$appEnvRaw = ""
if ($kv.ContainsKey("APP_ENV")) { $appEnvRaw = $kv["APP_ENV"] }
$appEnv = ($appEnvRaw + "").ToLower()
if ($appEnv -eq "") { $appEnv = "local" }

$dbSchemaRaw = ""
if ($kv.ContainsKey("DB_SCHEMA")) { $dbSchemaRaw = $kv["DB_SCHEMA"] }
$dbSchema = ($dbSchemaRaw + "").ToLower()

$tgDryRunRaw = ""
if ($kv.ContainsKey("TELEGRAM_DRY_RUN")) { $tgDryRunRaw = $kv["TELEGRAM_DRY_RUN"] }
$tgDryRun = ($tgDryRunRaw + "").ToLower()

Write-Host "APP_ENV=$appEnv"
Write-Host "DB_SCHEMA=$dbSchema"
Write-Host "TELEGRAM_DRY_RUN=$tgDryRun"

if ($appEnv -ne "production" -and $dbSchema -eq "app_prod") {
  throw "INSEGURO: APP_ENV=$appEnv usando DB_SCHEMA=app_prod"
}

Write-Host "OK: isolamento básico validado."
