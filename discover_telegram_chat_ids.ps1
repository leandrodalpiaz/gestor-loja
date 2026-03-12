param(
    [string]$EnvPath = ".env",
    [int]$WaitSeconds = 30,
    [switch]$AutoApply
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $EnvPath)) {
    throw "Arquivo .env não encontrado em: $EnvPath"
}

$envLines = Get-Content $EnvPath
$tokenLine = $envLines | Where-Object { $_ -like "TELEGRAM_BOT_TOKEN=*" } | Select-Object -First 1
if (-not $tokenLine) {
    throw "TELEGRAM_BOT_TOKEN não encontrado no .env"
}

$token = $tokenLine.Split("=", 2)[1].Trim()
$base = "https://api.telegram.org/bot$token"

$webhookInfo = Invoke-RestMethod -Uri "$base/getWebhookInfo"
$webhookUrl = $webhookInfo.result.url
Write-Host "Webhook atual: $webhookUrl"

Invoke-RestMethod -Uri "$base/deleteWebhook?drop_pending_updates=false" | Out-Null
Write-Host "Webhook pausado temporariamente para captura de updates."
Write-Host "Envie AGORA uma mensagem privada para o bot e uma mensagem no grupo do bot."
Write-Host "Aguardando $WaitSeconds segundos..."
Start-Sleep -Seconds $WaitSeconds

$updates = Invoke-RestMethod -Uri "$base/getUpdates?limit=100&timeout=10"

if ($webhookUrl) {
    $encoded = [System.Uri]::EscapeDataString($webhookUrl)
    Invoke-RestMethod -Uri "$base/setWebhook?url=$encoded" | Out-Null
    Write-Host "Webhook restaurado."
}

$rows = @()
foreach ($u in $updates.result) {
    if ($u.message -and $u.message.chat) {
        $c = $u.message.chat
        $rows += [PSCustomObject]@{
            chat_id = [string]$c.id
            type = [string]$c.type
            title = [string]$c.title
            username = [string]$c.username
            first_name = [string]$c.first_name
            source = "message"
        }
    }
    if ($u.callback_query -and $u.callback_query.message -and $u.callback_query.message.chat) {
        $c = $u.callback_query.message.chat
        $rows += [PSCustomObject]@{
            chat_id = [string]$c.id
            type = [string]$c.type
            title = [string]$c.title
            username = [string]$c.username
            first_name = [string]$c.first_name
            source = "callback"
        }
    }
}

$unique = $rows | Sort-Object chat_id -Unique
if (-not $unique -or $unique.Count -eq 0) {
    Write-Host "Nenhum chat capturado. Rode novamente e envie mensagens durante a janela de captura." -ForegroundColor Yellow
    exit 1
}

Write-Host "Chats capturados:"
$unique | Format-Table -AutoSize

$privateChat = $unique | Where-Object { $_.type -eq "private" } | Select-Object -First 1
$groupChat = $unique | Where-Object { $_.type -in @("group", "supergroup") } | Select-Object -First 1

if ($privateChat) {
    Write-Host "TELEGRAM_CHAT_ID_CHANCELER sugerido: $($privateChat.chat_id)"
}
if ($groupChat) {
    Write-Host "TELEGRAM_CHAT_ID_GROUP sugerido: $($groupChat.chat_id)"
}

if ($AutoApply) {
    $newLines = @()
    $hasGroup = $false
    $hasReview = $false

    foreach ($line in $envLines) {
        if ($line -like "TELEGRAM_CHAT_ID_GROUP=*") {
            $hasGroup = $true
            if ($groupChat) {
                $newLines += "TELEGRAM_CHAT_ID_GROUP=$($groupChat.chat_id)"
            } else {
                $newLines += $line
            }
            continue
        }

        if ($line -like "TELEGRAM_CHAT_ID_CHANCELER=*") {
            $hasReview = $true
            if ($privateChat) {
                $newLines += "TELEGRAM_CHAT_ID_CHANCELER=$($privateChat.chat_id)"
            } else {
                $newLines += $line
            }
            continue
        }

        $newLines += $line
    }

    if (-not $hasGroup -and $groupChat) {
        $newLines += "TELEGRAM_CHAT_ID_GROUP=$($groupChat.chat_id)"
    }
    if (-not $hasReview -and $privateChat) {
        $newLines += "TELEGRAM_CHAT_ID_CHANCELER=$($privateChat.chat_id)"
    }

    Set-Content -Path $EnvPath -Value $newLines -Encoding UTF8
    Write-Host ".env atualizado automaticamente com os IDs encontrados."
}
