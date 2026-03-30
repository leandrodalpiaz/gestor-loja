param(
    [int]$Port = 8000,
    [switch]$OpenAccess
)

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$publicDir = Join-Path $projectRoot 'public'
$router = Join-Path $publicDir 'router.php'

if ($OpenAccess) {
    $env:APP_TEST_OPEN_ACCESS = 'true'
    Write-Host "APP_TEST_OPEN_ACCESS=true habilitado para esta sessao." -ForegroundColor Yellow
}

Write-Host "Subindo servidor local em http://127.0.0.1:$Port" -ForegroundColor Green
Write-Host "Pasta publica: $publicDir" -ForegroundColor Green
Write-Host "Router: $router" -ForegroundColor Green
Write-Host "Pressione Ctrl+C para parar." -ForegroundColor Cyan

php -S "127.0.0.1:$Port" -t $publicDir $router
