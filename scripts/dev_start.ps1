param(
    [int]$Port = 8000,
    [int]$ChecklistPort = 8090,
    [switch]$OpenAccess,
    [switch]$SkipChecklist,
    [switch]$SkipTelegram,
    [switch]$NoHold,
    [switch]$WithTunnel,
    [switch]$WithPolling,
    [switch]$SkipWebhookUpdate,
    [switch]$SkipEnvUpdate,
    [ValidateSet('localhostrun', 'cloudflared')][string]$TunnelProvider = 'localhostrun'
)

$ErrorActionPreference = 'Stop'

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Set-DotEnvValue {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Key,
        [Parameter(Mandatory = $true)][string]$Value
    )

    $lines = @()
    if (Test-Path $Path) {
        $lines = Get-Content -Path $Path
    }

    $updated = $false
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match "^\s*$([regex]::Escape($Key))=") {
            $lines[$i] = "$Key=$Value"
            $updated = $true
            break
        }
    }

    if (-not $updated) {
        $lines += "$Key=$Value"
    }

    Set-Content -Path $Path -Value $lines -Encoding UTF8
}

function Ensure-Cloudflared {
    param(
        [Parameter(Mandatory = $true)][string]$ProjectRoot
    )

    $binDir = Join-Path $ProjectRoot 'scripts\bin'
    $exe = Join-Path $binDir 'cloudflared.exe'
    if (-not (Test-Path $exe)) {
        New-Item -ItemType Directory -Force -Path $binDir | Out-Null
        Write-Host "cloudflared nao encontrado. Baixando..." -ForegroundColor Yellow
        Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile $exe
    }

    return $exe
}

function Start-CloudflaredTunnel {
    param(
        [Parameter(Mandatory = $true)][string]$CloudflaredExe,
        [Parameter(Mandatory = $true)][int]$LocalPort,
        [Parameter(Mandatory = $true)][string]$ProjectRoot
    )

    $tmpDir = Join-Path $ProjectRoot 'scripts\tmp'
    New-Item -ItemType Directory -Force -Path $tmpDir | Out-Null
    $outFile = Join-Path $tmpDir 'cloudflared.out.log'
    $errFile = Join-Path $tmpDir 'cloudflared.err.log'
    if (Test-Path $outFile) { Remove-Item $outFile -Force }
    if (Test-Path $errFile) { Remove-Item $errFile -Force }

    $process = Start-Process `
        -FilePath $CloudflaredExe `
        -ArgumentList @('tunnel', '--no-autoupdate', '--url', "http://127.0.0.1:$LocalPort") `
        -WorkingDirectory $ProjectRoot `
        -PassThru `
        -RedirectStandardOutput $outFile `
        -RedirectStandardError $errFile

    return [pscustomobject]@{
        Process = $process
        OutFile = $outFile
        ErrFile = $errFile
    }
}

function Wait-TunnelUrl {
    param(
        [Parameter(Mandatory = $true)][string]$OutFile,
        [Parameter(Mandatory = $true)][string]$ErrFile,
        [Parameter(Mandatory = $true)][string]$Regex,
        [int]$TimeoutSeconds = 45
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)

    while ((Get-Date) -lt $deadline) {
        $text = ''
        if (Test-Path $OutFile) {
            $text += (Get-Content -Path $OutFile -Raw -ErrorAction SilentlyContinue)
        }
        if (Test-Path $ErrFile) {
            $text += "`n" + (Get-Content -Path $ErrFile -Raw -ErrorAction SilentlyContinue)
        }

        if ($text -match $regex) {
            if ($Matches.Count -gt 1 -and -not [string]::IsNullOrWhiteSpace($Matches[1])) {
                return $Matches[1]
            }
            return $Matches[0]
        }

        Start-Sleep -Milliseconds 500
    }

    return $null
}

function Start-LocalhostRunTunnel {
    param(
        [Parameter(Mandatory = $true)][int]$LocalPort,
        [Parameter(Mandatory = $true)][string]$ProjectRoot
    )

    $tmpDir = Join-Path $ProjectRoot 'scripts\tmp'
    New-Item -ItemType Directory -Force -Path $tmpDir | Out-Null
    $outFile = Join-Path $tmpDir 'localhostrun.out.log'
    $errFile = Join-Path $tmpDir 'localhostrun.err.log'
    if (Test-Path $outFile) { Remove-Item $outFile -Force }
    if (Test-Path $errFile) { Remove-Item $errFile -Force }

    $process = Start-Process `
        -FilePath 'ssh' `
        -ArgumentList @(
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'ServerAliveInterval=30',
            '-R', "80:localhost:$LocalPort",
            'nokey@localhost.run'
        ) `
        -WorkingDirectory $ProjectRoot `
        -PassThru `
        -RedirectStandardOutput $outFile `
        -RedirectStandardError $errFile

    return [pscustomobject]@{
        Process = $process
        OutFile = $outFile
        ErrFile = $errFile
    }
}

function Set-WebhookWithRetry {
    param(
        [Parameter(Mandatory = $true)][string]$ProjectRoot,
        [Parameter(Mandatory = $true)][string]$WebhookUrl,
        [int]$Attempts = 20,
        [int]$DelaySeconds = 5
    )

    $scriptPath = Join-Path $ProjectRoot 'scripts/telegram_webhook.php'
    for ($attempt = 1; $attempt -le $Attempts; $attempt++) {
        $null = & php $scriptPath set $WebhookUrl
        if ($LASTEXITCODE -eq 0) {
            return $true
        }

        if ($attempt -lt $Attempts) {
            Write-Host "Tentativa $attempt/$Attempts falhou. Aguardando propagacao DNS..." -ForegroundColor Yellow
            Start-Sleep -Seconds $DelaySeconds
        }
    }

    return $false
}

function Start-TelegramPolling {
    param(
        [Parameter(Mandatory = $true)][string]$ProjectRoot
    )

    $tmpDir = Join-Path $ProjectRoot 'scripts\tmp'
    New-Item -ItemType Directory -Force -Path $tmpDir | Out-Null
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $outFile = Join-Path $tmpDir "telegram-polling.$stamp.out.log"
    $errFile = Join-Path $tmpDir "telegram-polling.$stamp.err.log"

    $pollScript = Join-Path $ProjectRoot 'scripts/telegram_polling.php'
    $process = Start-Process `
        -FilePath 'php' `
        -ArgumentList @($pollScript) `
        -WorkingDirectory $ProjectRoot `
        -PassThru `
        -RedirectStandardOutput $outFile `
        -RedirectStandardError $errFile

    return [pscustomobject]@{
        Process = $process
        OutFile = $outFile
        ErrFile = $errFile
    }
}

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$publicDir = Join-Path $projectRoot 'public'
$router = Join-Path $publicDir 'router.php'
$envFile = Join-Path $projectRoot '.env'

if ($OpenAccess) {
    $env:APP_TEST_OPEN_ACCESS = 'true'
    Write-Host "APP_TEST_OPEN_ACCESS=true aplicado nesta sessao." -ForegroundColor Yellow
}

$server = $null
$tunnel = $null
$tunnelUrl = $null
$polling = $null

try {
    Write-Step "Subindo servidor local"
    $server = Start-Process -FilePath php -ArgumentList @('-S', "127.0.0.1:$Port", '-t', $publicDir, $router) -WorkingDirectory $projectRoot -PassThru
    Start-Sleep -Seconds 2
    Write-Host "Servidor: http://127.0.0.1:$Port"

    Write-Step "Healthcheck local"
    try {
        $health = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/health" -UseBasicParsing -TimeoutSec 25
        Write-Host "OK: /health -> HTTP $($health.StatusCode)" -ForegroundColor Green
    } catch {
        Write-Host "FALHA no healthcheck: $($_.Exception.Message)" -ForegroundColor Red
    }

    if ($WithTunnel) {
        Write-Step "Subindo tunnel publico"

        if ($TunnelProvider -eq 'cloudflared') {
            $cloudflaredExe = Ensure-Cloudflared -ProjectRoot $projectRoot
            $tunnel = Start-CloudflaredTunnel -CloudflaredExe $cloudflaredExe -LocalPort $Port -ProjectRoot $projectRoot
            $tunnelUrl = Wait-TunnelUrl -OutFile $tunnel.OutFile -ErrFile $tunnel.ErrFile -Regex 'https://[-a-zA-Z0-9]+\.trycloudflare\.com' -TimeoutSeconds 45
        } else {
            $tunnel = Start-LocalhostRunTunnel -LocalPort $Port -ProjectRoot $projectRoot
            $tunnelUrl = Wait-TunnelUrl -OutFile $tunnel.OutFile -ErrFile $tunnel.ErrFile -Regex 'tunneled with tls termination,\s*(https://[-a-zA-Z0-9\.]+)' -TimeoutSeconds 30
        }

        if ([string]::IsNullOrWhiteSpace($tunnelUrl)) {
            Write-Host "Nao foi possivel obter URL publica do tunnel." -ForegroundColor Red
            throw "Tunnel nao iniciado. Sem URL publica, os botoes Mini App nao irao abrir."
        } else {
            Write-Host "Tunnel URL: $tunnelUrl" -ForegroundColor Green
            Write-Host "Importante: envie /painel novamente no Telegram para gerar botoes com esta URL." -ForegroundColor Yellow

            if (-not $SkipEnvUpdate) {
                Set-DotEnvValue -Path $envFile -Key 'APP_URL' -Value $tunnelUrl
                Write-Host "APP_URL atualizado no .env." -ForegroundColor Green
            } else {
                Write-Host "SKIP update APP_URL (-SkipEnvUpdate)." -ForegroundColor Yellow
            }

            try {
                $tunnelHealth = Invoke-WebRequest -Uri "$tunnelUrl/health" -UseBasicParsing -TimeoutSec 20
                if ($tunnelHealth.StatusCode -eq 200) {
                    Write-Host "Tunnel externo validado em $tunnelUrl/health." -ForegroundColor Green
                } else {
                    Write-Host "Aviso: tunnel respondeu status inesperado ($($tunnelHealth.StatusCode))." -ForegroundColor Yellow
                }
            } catch {
                Write-Host "Aviso: nao foi possivel validar /health via tunnel agora. Tente novamente em alguns segundos." -ForegroundColor Yellow
            }

            if (-not $WithPolling -and -not $SkipTelegram -and -not $SkipWebhookUpdate) {
                try {
                    $okWebhook = Set-WebhookWithRetry -ProjectRoot $projectRoot -WebhookUrl "$tunnelUrl/webhook.php"
                    if ($okWebhook) {
                        Write-Host "Webhook atualizado para o tunnel." -ForegroundColor Green
                    } else {
                        Write-Host "Aviso: falha ao atualizar webhook apos varias tentativas." -ForegroundColor Yellow
                    }
                } catch {
                    Write-Host "Aviso: erro ao atualizar webhook. $($_.Exception.Message)" -ForegroundColor Yellow
                }
            } elseif ($WithPolling -and -not $SkipTelegram) {
                Write-Host "Modo polling ativo: webhook nao sera atualizado." -ForegroundColor Yellow
            } elseif ($SkipWebhookUpdate) {
                Write-Host "SKIP update webhook (-SkipWebhookUpdate)." -ForegroundColor Yellow
            }
        }
    }

    if ($WithPolling) {
        Write-Step "Iniciando Telegram em long polling (sem webhook publico)"
        if (-not $SkipTelegram) {
            try {
                & php (Join-Path $projectRoot 'scripts/telegram_webhook.php') delete
                if ($LASTEXITCODE -eq 0) {
                    Write-Host "Webhook removido para operar em polling." -ForegroundColor Green
                } else {
                    Write-Host "Aviso: nao foi possivel remover webhook (codigo $LASTEXITCODE)." -ForegroundColor Yellow
                }
            } catch {
                Write-Host "Aviso: erro ao remover webhook. $($_.Exception.Message)" -ForegroundColor Yellow
            }
        }

        $polling = Start-TelegramPolling -ProjectRoot $projectRoot
        Start-Sleep -Seconds 1
        if ($polling.Process.HasExited) {
            Write-Host "Falha ao iniciar polling. Verifique logs em scripts/tmp/telegram-polling.*.log" -ForegroundColor Red
        } else {
            Write-Host "Polling ativo (PID $($polling.Process.Id))." -ForegroundColor Green
        }
    }

    if (-not $SkipTelegram) {
        Write-Step "Status do webhook Telegram"
        try {
            & php (Join-Path $projectRoot 'scripts/telegram_webhook.php') status
            if ($LASTEXITCODE -ne 0) {
                Write-Host "Aviso: telegram_webhook.php retornou codigo $LASTEXITCODE." -ForegroundColor Yellow
            }
        } catch {
            Write-Host "Aviso: nao foi possivel consultar webhook Telegram. $($_.Exception.Message)" -ForegroundColor Yellow
        }
    } else {
        Write-Step "Status do webhook Telegram"
        Write-Host "SKIP (executado com -SkipTelegram)." -ForegroundColor Yellow
    }

    if (-not $SkipChecklist) {
        Write-Step "Checklist automatizado"
        $checkArgs = @(
            '-ExecutionPolicy', 'Bypass',
            '-File', (Join-Path $projectRoot 'scripts/checklist_local.ps1'),
            '-Port', $ChecklistPort
        )
        if ($OpenAccess) { $checkArgs += '-OpenAccess' }
        if ($SkipTelegram) { $checkArgs += '-SkipTelegram' }
        if ($WithPolling -and -not $SkipTelegram) {
            $checkArgs += '-TelegramMode'
            $checkArgs += 'polling'
        }

        & powershell @checkArgs
        $checkExit = $LASTEXITCODE
        if ($checkExit -eq 0) {
            Write-Host "Checklist concluido sem falhas." -ForegroundColor Green
        } else {
            Write-Host "Checklist concluiu com falhas (exit code $checkExit)." -ForegroundColor Yellow
        }
    } else {
        Write-Step "Checklist automatizado"
        Write-Host "SKIP (executado com -SkipChecklist)." -ForegroundColor Yellow
    }

    if ($NoHold) {
        Write-Step "Finalizando sem manter processos ativos (-NoHold)"
        if ($WithTunnel -and -not $SkipWebhookUpdate -and -not [string]::IsNullOrWhiteSpace($tunnelUrl)) {
            if ($WithPolling -and -not $SkipTelegram) {
                Write-Host "Aviso: o tunnel sera encerrado agora; os Mini Apps deixarao de abrir fora desta sessao." -ForegroundColor Yellow
            } else {
                Write-Host "Aviso: o tunnel sera encerrado agora; o webhook ficara temporariamente apontando para uma URL inativa." -ForegroundColor Yellow
            }
        }
        if ($WithPolling -and -not $SkipTelegram) {
            Write-Host "Aviso: o processo de polling sera encerrado agora." -ForegroundColor Yellow
        }
        return
    }

    Write-Step "Processos em execucao"
    Write-Host "Servidor: http://127.0.0.1:$Port" -ForegroundColor Green
    if ($WithTunnel -and -not [string]::IsNullOrWhiteSpace($tunnelUrl)) {
        Write-Host "Tunnel: $tunnelUrl" -ForegroundColor Green
    }
    if ($WithPolling -and $polling -and -not $polling.Process.HasExited) {
        Write-Host "Telegram Polling: PID $($polling.Process.Id)" -ForegroundColor Green
    }
    Write-Host "Pressione Ctrl+C para encerrar." -ForegroundColor Cyan

    while ($true) {
        Start-Sleep -Seconds 2
        if ($server.HasExited) {
            Write-Host "Servidor foi encerrado externamente." -ForegroundColor Yellow
            break
        }
        if ($WithTunnel -and $tunnel -and $tunnel.Process.HasExited) {
            Write-Host "Tunnel foi encerrado externamente." -ForegroundColor Yellow
            break
        }
        if ($WithPolling -and $polling -and $polling.Process.HasExited) {
            Write-Host "Polling do Telegram foi encerrado externamente." -ForegroundColor Yellow
            break
        }
    }
}
finally {
    if ($polling -and $polling.Process -and -not $polling.Process.HasExited) {
        Stop-Process -Id $polling.Process.Id -Force
    }
    if ($tunnel -and $tunnel.Process -and -not $tunnel.Process.HasExited) {
        Stop-Process -Id $tunnel.Process.Id -Force
    }
    if ($server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -Force
    }
}
