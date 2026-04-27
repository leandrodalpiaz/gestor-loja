param(
    [int]$Port = 8090,
    [switch]$OpenAccess,
    [switch]$SkipTelegram,
    [ValidateSet('webhook', 'polling', 'skip')][string]$TelegramMode = 'webhook'
)

$ErrorActionPreference = 'Stop'

$results = New-Object System.Collections.ArrayList
$failCount = 0

function Add-Result {
    param(
        [string]$Check,
        [string]$Status,
        [string]$Detail
    )

    $null = $results.Add([pscustomobject]@{
        Check = $Check
        Status = $Status
        Detail = $Detail
    })

    if ($Status -eq 'FAIL') {
        $script:failCount++
    }
}

function Parse-DotEnv {
    param([string]$Path)

    $map = @{}
    if (-not (Test-Path $Path)) {
        return $map
    }

    foreach ($line in Get-Content $Path) {
        $trimmed = $line.Trim()
        if ($trimmed -eq '' -or $trimmed.StartsWith('#')) {
            continue
        }

        $idx = $trimmed.IndexOf('=')
        if ($idx -lt 1) {
            continue
        }

        $key = $trimmed.Substring(0, $idx).Trim()
        $value = $trimmed.Substring($idx + 1).Trim()
        $map[$key] = $value
    }

    return $map
}

function Get-HttpNoRedirect {
    param([string]$Url)
    try {
        $resp = Invoke-WebRequest -Uri $Url -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop -TimeoutSec 25
        return @{
            StatusCode = [int]$resp.StatusCode
            Location = [string]$resp.Headers.Location
            Content = [string]$resp.Content
        }
    } catch {
        $webResp = $_.Exception.Response
        if ($null -ne $webResp) {
            return @{
                StatusCode = [int]$webResp.StatusCode
                Location = [string]$webResp.Headers['Location']
                Content = ''
            }
        }
        throw
    }
}

function Post-FormNoRedirect {
    param(
        [string]$Url,
        [hashtable]$Body
    )
    try {
        $resp = Invoke-WebRequest -Uri $Url -Method Post -Body $Body -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop -TimeoutSec 25
        return @{
            StatusCode = [int]$resp.StatusCode
            Location = [string]$resp.Headers.Location
            Content = [string]$resp.Content
        }
    } catch {
        $webResp = $_.Exception.Response
        if ($null -ne $webResp) {
            return @{
                StatusCode = [int]$webResp.StatusCode
                Location = [string]$webResp.Headers['Location']
                Content = ''
            }
        }
        throw
    }
}

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$publicDir = Join-Path $projectRoot 'public'
$router = Join-Path $publicDir 'router.php'
$envFile = Join-Path $projectRoot '.env'
$envMap = Parse-DotEnv -Path $envFile

if ($OpenAccess) {
    $env:APP_TEST_OPEN_ACCESS = 'true'
    Write-Host "APP_TEST_OPEN_ACCESS=true aplicado para esta execucao." -ForegroundColor Yellow
}

$proc = $null
try {
    $proc = Start-Process -FilePath php -ArgumentList @('-S', "127.0.0.1:$Port", '-t', $publicDir, $router) -WorkingDirectory $projectRoot -PassThru
    Start-Sleep -Seconds 2

    $baseUrl = "http://127.0.0.1:$Port"

    try {
        $health = Invoke-WebRequest -Uri "$baseUrl/health" -UseBasicParsing -TimeoutSec 25
        $healthJson = $health.Content | ConvertFrom-Json
        if ($health.StatusCode -eq 200 -and $healthJson.status -eq 'ok') {
            Add-Result 'Web Health' 'PASS' '/health retornou status ok.'
        } else {
            Add-Result 'Web Health' 'FAIL' "Resposta inesperada: HTTP $($health.StatusCode)"
        }
    } catch {
        Add-Result 'Web Health' 'FAIL' $_.Exception.Message
    }

    try {
        $login = Invoke-WebRequest -Uri "$baseUrl/login" -UseBasicParsing -TimeoutSec 25
        if ($login.StatusCode -eq 200) {
            Add-Result 'Web Login Page' 'PASS' '/login acessivel.'
        } else {
            Add-Result 'Web Login Page' 'FAIL' "HTTP $($login.StatusCode)"
        }
    } catch {
        Add-Result 'Web Login Page' 'FAIL' $_.Exception.Message
    }

    Add-Result 'Validacao multi-tenant' 'SKIP' 'Execute docs/validacao_multi_tenant.md para validar isolamento entre lojas.'

    try {
        $miniNovo = Invoke-WebRequest -Uri "$baseUrl/biblioteca/novo" -UseBasicParsing -TimeoutSec 25
        $miniScanner = Invoke-WebRequest -Uri "$baseUrl/biblioteca/scanner" -UseBasicParsing -TimeoutSec 25
        if ($miniNovo.StatusCode -eq 200 -and $miniScanner.StatusCode -eq 200) {
            Add-Result 'Miniapps Pages' 'PASS' '/biblioteca/novo e /biblioteca/scanner responderam 200.'
        } else {
            Add-Result 'Miniapps Pages' 'FAIL' 'Uma das rotas miniapp nao respondeu 200.'
        }
    } catch {
        Add-Result 'Miniapps Pages' 'FAIL' $_.Exception.Message
    }

    try {
        $webhook = Invoke-WebRequest -Uri "$baseUrl/webhook.php" -Method Post -ContentType 'application/json' -Body '{}' -UseBasicParsing -TimeoutSec 25
        if ($webhook.StatusCode -eq 200 -and $webhook.Content -match 'OK') {
            Add-Result 'Webhook Local' 'PASS' '/webhook.php respondeu 200 OK.'
        } else {
            Add-Result 'Webhook Local' 'FAIL' 'Resposta inesperada no webhook local.'
        }
    } catch {
        Add-Result 'Webhook Local' 'FAIL' $_.Exception.Message
    }

    try {
        $isbnBody = @{ isbn = '9788575225638' } | ConvertTo-Json
        $isbnResp = Invoke-WebRequest -Uri "$baseUrl/api/biblioteca/isbn.php" -Method Post -ContentType 'application/json' -Body $isbnBody -UseBasicParsing -TimeoutSec 25
        $isbnJson = $isbnResp.Content | ConvertFrom-Json
        if ($isbnResp.StatusCode -eq 200 -and $isbnJson.ok -eq $true) {
            Add-Result 'Biblioteca ISBN API' 'PASS' 'Cadastro/consulta por ISBN funcionando.'
        } else {
            Add-Result 'Biblioteca ISBN API' 'FAIL' 'Resposta JSON sem ok=true.'
        }
    } catch {
        Add-Result 'Biblioteca ISBN API' 'FAIL' $_.Exception.Message
    }

    try {
        $isbnCompatBody = @{ isbn = '9788575225638' } | ConvertTo-Json
        $isbnCompatResp = Invoke-WebRequest -Uri "$baseUrl/api/cadastrar_isbn.php" -Method Post -ContentType 'application/json' -Body $isbnCompatBody -UseBasicParsing -TimeoutSec 25
        $isbnCompatJson = $isbnCompatResp.Content | ConvertFrom-Json
        if ($isbnCompatResp.StatusCode -eq 200 -and $isbnCompatJson.ok -eq $true) {
            Add-Result 'Biblioteca ISBN Compat' 'PASS' 'Endpoint legado /api/cadastrar_isbn.php funcionando.'
        } else {
            Add-Result 'Biblioteca ISBN Compat' 'FAIL' 'Endpoint legado nao retornou ok=true.'
        }
    } catch {
        Add-Result 'Biblioteca ISBN Compat' 'FAIL' $_.Exception.Message
    }

    try {
        $emprestimos = Invoke-WebRequest -Uri "$baseUrl/biblioteca/emprestimos" -UseBasicParsing -TimeoutSec 25
        $finalPath = [string]$emprestimos.BaseResponse.ResponseUri.AbsolutePath
        if ($emprestimos.StatusCode -eq 200 -and ($finalPath -eq '/biblioteca/emprestimos' -or $finalPath -eq '/login')) {
            Add-Result 'Biblioteca Emprestimos View' 'PASS' "Rota respondeu 200 (destino final: $finalPath)."
        } else {
            Add-Result 'Biblioteca Emprestimos View' 'FAIL' "HTTP $($emprestimos.StatusCode) destino final: $finalPath"
        }
    } catch {
        Add-Result 'Biblioteca Emprestimos View' 'FAIL' $_.Exception.Message
    }

    try {
        $tesouraria = Invoke-WebRequest -Uri "$baseUrl/api/tesouraria/comprovantes" -UseBasicParsing -TimeoutSec 25
        $tesourariaJson = $tesouraria.Content | ConvertFrom-Json
        if ($tesouraria.StatusCode -eq 200 -and $tesourariaJson.ok -eq $true) {
            Add-Result 'Tesouraria API' 'PASS' '/api/tesouraria/comprovantes respondeu ok=true.'
        } else {
            Add-Result 'Tesouraria API' 'FAIL' 'Resposta inesperada da API de tesouraria.'
        }
    } catch {
        Add-Result 'Tesouraria API' 'FAIL' $_.Exception.Message
    }

    $checkLoginCim = $envMap['CHECK_LOGIN_CIM']
    $checkLoginPassword = $envMap['CHECK_LOGIN_PASSWORD']
    if ([string]::IsNullOrWhiteSpace($checkLoginCim) -or [string]::IsNullOrWhiteSpace($checkLoginPassword)) {
        Add-Result 'Login Real' 'SKIP' 'Defina CHECK_LOGIN_CIM e CHECK_LOGIN_PASSWORD no .env para validar login real.'
    } else {
        try {
            $loginResp = Post-FormNoRedirect -Url "$baseUrl/login" -Body @{
                matricula = $checkLoginCim
                password = $checkLoginPassword
            }

            if (@(301, 302, 303) -contains $loginResp.StatusCode -and $loginResp.Location -match '/dashboard') {
                Add-Result 'Login Real' 'PASS' 'Login real redirecionou para /dashboard.'
            } else {
                Add-Result 'Login Real' 'FAIL' "Nao redirecionou para /dashboard (HTTP $($loginResp.StatusCode))."
            }
        } catch {
            Add-Result 'Login Real' 'FAIL' $_.Exception.Message
        }
    }

    if ($SkipTelegram -or $TelegramMode -eq 'skip') {
        Add-Result 'Telegram Webhook (externo)' 'SKIP' 'Executado com -SkipTelegram ou -TelegramMode skip.'
    } else {
        $token = $env:TELEGRAM_BOT_TOKEN
        if ([string]::IsNullOrWhiteSpace($token)) {
            $token = $envMap['TELEGRAM_BOT_TOKEN']
        }

        if ([string]::IsNullOrWhiteSpace($token)) {
            Add-Result 'Telegram Webhook (externo)' 'SKIP' 'TELEGRAM_BOT_TOKEN nao definido.'
        } else {
            try {
                $hook = Invoke-RestMethod -Uri "https://api.telegram.org/bot$token/getWebhookInfo" -Method Get -TimeoutSec 25
                if ($hook.ok -ne $true) {
                    Add-Result 'Telegram Webhook (externo)' 'FAIL' 'getWebhookInfo retornou ok=false.'
                } else {
                    $urlAtual = [string]$hook.result.url

                    if ($TelegramMode -eq 'polling') {
                        if ([string]::IsNullOrWhiteSpace($urlAtual)) {
                            Add-Result 'Telegram Webhook (externo)' 'PASS' 'Webhook desativado (esperado no modo polling).'
                        } else {
                            Add-Result 'Telegram Webhook (externo)' 'FAIL' "Webhook ainda ativo no modo polling: $urlAtual"
                        }

                        try {
                            $me = Invoke-RestMethod -Uri "https://api.telegram.org/bot$token/getMe" -Method Get -TimeoutSec 25
                            if ($me.ok -eq $true) {
                                Add-Result 'Telegram Polling API' 'PASS' "Bot acessivel: @$($me.result.username)"
                            } else {
                                Add-Result 'Telegram Polling API' 'FAIL' 'getMe retornou ok=false.'
                            }
                        } catch {
                            Add-Result 'Telegram Polling API' 'FAIL' $_.Exception.Message
                        }

                        Add-Result 'Telegram Webhook URL Match' 'SKIP' 'Nao se aplica no modo polling.'
                    } else {
                        if ([string]::IsNullOrWhiteSpace($urlAtual)) {
                            Add-Result 'Telegram Webhook (externo)' 'FAIL' 'Webhook vazio no Telegram.'
                        } else {
                            Add-Result 'Telegram Webhook (externo)' 'PASS' "Webhook atual: $urlAtual"
                        }

                        $appUrl = $envMap['APP_URL']
                        if (-not [string]::IsNullOrWhiteSpace($appUrl) -and $appUrl.StartsWith('https://')) {
                            $esperado = ($appUrl.TrimEnd('/')) + '/webhook.php'
                            if ($urlAtual -eq $esperado) {
                                Add-Result 'Telegram Webhook URL Match' 'PASS' "Webhook alinhado com APP_URL ($esperado)."
                            } else {
                                Add-Result 'Telegram Webhook URL Match' 'FAIL' "Webhook atual '$urlAtual' difere de '$esperado'."
                            }
                        } else {
                            Add-Result 'Telegram Webhook URL Match' 'SKIP' 'APP_URL nao esta em HTTPS publico no .env.'
                        }
                    }
                }
            } catch {
                Add-Result 'Telegram Webhook (externo)' 'FAIL' $_.Exception.Message
            }
        }
    }
}
finally {
    if ($proc -and -not $proc.HasExited) {
        Stop-Process -Id $proc.Id -Force
    }
}

Write-Host ""
Write-Host "Checklist Local - Resultado" -ForegroundColor Cyan
$results | Format-Table -AutoSize

$passCount = ($results | Where-Object { $_.Status -eq 'PASS' }).Count
$skipCount = ($results | Where-Object { $_.Status -eq 'SKIP' }).Count
$failCountResult = ($results | Where-Object { $_.Status -eq 'FAIL' }).Count

Write-Host ""
Write-Host "PASS: $passCount | SKIP: $skipCount | FAIL: $failCountResult"

if ($failCountResult -gt 0) {
    exit 1
}

exit 0
