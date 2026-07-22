<#
.SYNOPSIS
    Restaura um backup gerado por db_backup.ps1, limpando e recarregando
    APENAS o schema "public" (onde vivem os dados reais da Loja: obreiros,
    balaustres, efemerides etc). NUNCA toca nos schemas internos do
    Supabase (auth, realtime, extensions, pgbouncer) que tambem estao
    dentro do arquivo de backup.

.USAGE
    powershell -ExecutionPolicy Bypass -File .\scripts\db_restore.ps1 -BackupFile "backup_20260722_153740_baseline.dump"

    Por seguranca, pede confirmacao digitada antes de executar (acao
    DESTRUTIVA e IRREVERSIVEL sobre os dados atuais do banco real).

.NOTES
    Precisa da MESMA versao major do cliente pg_restore que o servidor
    Postgres do Supabase (hoje: 17). Ajuste $PgImage se o Supabase
    atualizar a versao major do Postgres.
#>
param(
    [Parameter(Mandatory = $true)]
    [string]$BackupFile,
    [switch]$Force
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
$backupPath = Join-Path $backupsDir $BackupFile

if (-not (Test-Path $backupPath)) {
    Write-Host "Arquivo de backup nao encontrado: $backupPath" -ForegroundColor Red
    Write-Host "Backups disponiveis:" -ForegroundColor Yellow
    Get-ChildItem $backupsDir -Filter '*.dump' | ForEach-Object { Write-Host "  $($_.Name)" }
    exit 1
}

Write-Host "==================================================================" -ForegroundColor Red
Write-Host " ATENCAO: isso vai APAGAR e SUBSTITUIR todos os dados atuais do" -ForegroundColor Red
Write-Host " schema 'public' em $dbHost (banco REAL de producao) pelo" -ForegroundColor Red
Write-Host " conteudo de: $BackupFile" -ForegroundColor Red
Write-Host " Essa acao NAO tem volta. Schemas internos do Supabase (auth," -ForegroundColor Red
Write-Host " realtime, extensions) NAO sao tocados." -ForegroundColor Red
Write-Host "==================================================================" -ForegroundColor Red

if (-not $Force) {
    $confirm = Read-Host "Digite CONFIRMAR para prosseguir"
    if ($confirm -ne 'CONFIRMAR') {
        Write-Host "Cancelado." -ForegroundColor Yellow
        exit 0
    }
}

Write-Host "Restaurando schema 'public' a partir de $backupPath ..." -ForegroundColor Cyan

docker run --rm `
    -e PGPASSWORD=$dbPass `
    -v "${backupsDir}:/backups" `
    $PgImage `
    pg_restore -h $dbHost -p $dbPort -U $dbUser -d $dbName `
        --schema=public --clean --if-exists --no-owner --no-privileges `
        "/backups/$BackupFile"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Restore concluido com sucesso." -ForegroundColor Green
} else {
    Write-Host "Restore terminou com avisos/erros (exit code $LASTEXITCODE.) Revise a saida acima -- pg_restore costuma reportar erros nao-fatais (ex: extensoes ja existentes) sem falhar a restauracao dos dados." -ForegroundColor Yellow
}
