<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Ler ISBN</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root {
            --bg: var(--tg-theme-bg-color, #f5f5f0);
            --fg: var(--tg-theme-text-color, #1a1a1a);
            --btn: var(--tg-theme-button-color, #1d4ed8);
            --btnf: var(--tg-theme-button-text-color, #fff);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        body {
            background: var(--bg);
            color: var(--fg);
            padding: 16px;
            padding-bottom: 80px;
        }
        h1 { font-size: 18px; font-weight: 700; margin-bottom: 12px; }
        #reader {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
        }
        #info { margin-top: 18px; font-size: 14px; line-height: 1.45; }
        #erro { margin-top: 12px; color: #e53935; font-size: 14px; }
        #btn-salvar {
            margin-top: 20px;
            padding: 12px;
            width: 100%;
            background: var(--btn);
            color: var(--btnf);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            display: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Escaneie o codigo de barras do livro</h1>
    <div id="reader"></div>
    <div id="info"></div>
    <div id="erro"></div>
    <button id="btn-salvar">Salvar livro</button>

    <script>
        const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
        if (tg) {
            tg.ready();
            tg.expand();
        }

        function notifySuccess() {
            if (tg && tg.HapticFeedback) {
                tg.HapticFeedback.notificationOccurred("success");
            }
        }

        function notifyError() {
            if (tg && tg.HapticFeedback) {
                tg.HapticFeedback.notificationOccurred("error");
            }
        }

        function closeApp() {
            if (tg && typeof tg.close === "function") {
                tg.close();
                return;
            }
            window.history.back();
        }

        function successAlert(message) {
            if (tg && typeof tg.showAlert === "function") {
                tg.showAlert(message, () => closeApp());
                return;
            }
            alert(message);
            closeApp();
        }

        const info = document.getElementById("info");
        const erro = document.getElementById("erro");
        const salvar = document.getElementById("btn-salvar");
        let ultimoISBN = null;

        const reader = new Html5Qrcode("reader");
        reader.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 120 } },
            onScanSuccess,
            () => {}
        ).catch(() => {
            erro.textContent = "Nao foi possivel abrir a camera. Verifique as permissoes e tente novamente.";
        });

        async function onScanSuccess(text) {
            const isbnLimpo = text.replace(/[^0-9X]/gi, "");
            if (isbnLimpo === ultimoISBN) return;
            ultimoISBN = isbnLimpo;

            erro.textContent = "";
            info.innerHTML = `Consultando ISBN <b>${isbnLimpo}</b>...`;

            try {
                const res = await fetch("/api/biblioteca/isbn.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        isbn: isbnLimpo,
                        init_data: tg ? (tg.initData || "") : ""
                    })
                });
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(data.erro || data.error || "Nao foi possivel cadastrar o livro por ISBN agora.");
                }

                info.innerHTML =
                    `<b>Titulo:</b> ${data.titulo}<br>` +
                    `<b>Autor(es):</b> ${data.autor}<br>` +
                    `<b>ISBN:</b> ${isbnLimpo}<br>` +
                    `<b>Codigo:</b> ${data.codigo_acervo || '-'}<br>` +
                    `<b>Resumo:</b> ${(data.resumo || 'Resumo nao informado').slice(0, 280)}`;

                salvar.style.display = "block";
                salvar.onclick = () => successAlert("Livro cadastrado com sucesso.");
                notifySuccess();
            } catch (e) {
                erro.textContent = e.message ? e.message : "Nao foi possivel consultar o ISBN agora. Tente novamente.";
                salvar.style.display = "none";
                notifyError();
            }
        }
    </script>
</body>
</html>
