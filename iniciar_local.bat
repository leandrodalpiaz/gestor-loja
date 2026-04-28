@echo off
setlocal

cd /d "%~dp0"

echo ===========================================================
echo Gestor Loja - Inicio local (1 clique, sem Render)
echo Modo: Web local + Telegram polling + tunnel HTTPS
echo URL web: http://127.0.0.1:8000
echo Pressione Ctrl+C para encerrar quando terminar.
echo ===========================================================
echo.

powershell -NoLogo -NoProfile -ExecutionPolicy Bypass -File ".\scripts\dev_start.ps1" -Port 8000 -ChecklistPort 8090 -WithPolling -WithTunnel -TunnelProvider cloudflared
set "EXIT_CODE=%ERRORLEVEL%"

echo.
if not "%EXIT_CODE%"=="0" (
    echo Execucao finalizada com erro (codigo %EXIT_CODE%).
) else (
    echo Execucao finalizada.
)

echo.
pause
exit /b %EXIT_CODE%
