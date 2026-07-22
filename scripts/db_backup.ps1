<#
.SYNOPSIS
    Gera um backup logico completo (schema + dados) do banco Supabase
    via pg_dump, rodando dentro de um container Docker efemero (nao
    exige instalar postgresql-client na maquina).

.USAGE
    powershell -ExecutionPolicy Bypass -File .\scripts\db_backup.ps1
    powershell -ExecutionPolicy Bypass -File .\scripts\db_backup.ps1 -Label "antes-teste-fulano"

.NOTES
    Usa a porta 6543 (pooler transaction-mode) para leitura normal do
    app, mas pg_dump precisa do cliente na MESMA versao major do
    servidor Postgres (Supabase costuma rodar Postgres 17). Se a
    versao do servidor mudar, ajuste $PgImage abaixo.
#>
param(
    [string]$Label = ''
)

function Parse-DotEnv {
    param([string]$Path)
    $map = @{}
    if (-not (Test-Path $Path)) { return $map }
    foreach ($line in Get-Content $Path) {
        $trimmed = $line.Trim()
        if ($trimmed -eq '' -or $trimmed.StartsWith('#')) { continue }
        $idx = $trimmed.IndexOf('=')
        if ($idx -lt 1) { continue }
        $key = $trimmed.Substring(0, $idx).Trim()
        $value = $trimmed.Substring($idx + 1).Trim()
        $map[$key] = $value
    }
    return $map
}

$PgImage = 'postgres:17'
$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$envFile = Join-Path $projectRoot '.env'
$envMap = Parse-DotEnv -Path $envFile

$dbHost = $envMap['DB_HOST']
$dbPort = $envMap['DB_PORT']
$dbName = $envMap['DB_NAME']
$dbUser = $envMap['DB_USER']
$dbPass = $envMap['DB_PASS']

if (-not $dbHost -or -not $dbUser -or -not $dbPass) {
    Write-Host "DB_HOST/DB_USER/DB_PASS nao encontrados em .env. Abortando." -ForegroundColor Red
    exit 1
}

$backupsDir = Join-Path $projectRoot '.backups\db'
New-Item -ItemType Directory -Force -Path $backupsDir | Out-Null

$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$labelSuffix = if ($Label) { "_$($Label -replace '[^a-zA-Z0-9_-]', '-')" } else { '' }
$fileName = "backup_${timestamp}${labelSuffix}.dump"
$hostPath = Join-Path $backupsDir $fileName

Write-Host "Gerando backup em $hostPath ..." -ForegroundColor Cyan

docker run --rm `
    -e PGPASSWORD=$dbPass `
    -v "${backupsDir}:/backups" `
    $PgImage `
    pg_dump -h $dbHost -p $dbPort -U $dbUser -d $dbName -F c -f "/backups/$fileName"

if ($LASTEXITCODE -eq 0 -and (Test-Path $hostPath)) {
    $sizeKb = [math]::Round((Get-Item $hostPath).Length / 1KB, 1)
    Write-Host "Backup concluido: $hostPath ($sizeKb KB)" -ForegroundColor Green
    Write-Host "Para restaurar: powershell -ExecutionPolicy Bypass -File .\scripts\db_restore.ps1 -BackupFile `"$fileName`"" -ForegroundColor Yellow
} else {
    Write-Host "Falha ao gerar backup (exit code $LASTEXITCODE)." -ForegroundColor Red
    exit 1
}
