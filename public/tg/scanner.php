<?php
// Mini-App Telegram  –  Cadastro de livro via leitura de código de barras (EAN/ISBN)
// Não há processamento em PHP aqui; o corpo é todo HTML/JS.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <title>Scanner ISBN</title>

    <!-- SDK do Telegram Web-App -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Biblioteca de leitura de código de barras / QR -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        :root {
            --bg:   var(--tg-theme-bg-color,          #F5F5F0);
            --bg2:  var(--tg-theme-secondary-bg-color,#FFFFFF);
            --text: var(--tg-theme-text-color,        #1A1A1A);
            --hint: var(--tg-theme-hint-color,        #6B7280);
            --btn:  var(--tg-theme-button-color,      #0047AB);
            --btn-txt: var(--tg-theme-button-text-color,#FFFFFF);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter','Segoe UI',sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 16px 16px 80px;
            text-align: center;
        }

        #reader        { width: 100%; max-width: 360px; margin: 1rem auto; }
        #info          { margin-top: 1rem; }
        #erro          { color: #D33;   margin-top: .8rem; }
        button         {
            padding: .6rem 1.4rem; font-size: 1rem; border: none; border-radius: 6px;
            background: var(--btn); color: var(--btn-txt); cursor: pointer;
        }
        #salvar        { display: none; margin-top: 1.2rem; background: #22BB33; }
    </style>
</head>

<body>
    <h3>📚 Escaneie o código de barras (EAN/ISBN)</h3>

    <div id="reader"></div>

    <div id="info"></div>
    <button id="salvar">✅ Salvar</button>
    <div id="erro"></div>

    <script>
        /* --- Inicialização do Web-App --- */
        Telegram.WebApp.ready();

        /* --- Configuração do leitor de código de barras --- */
        const reader = new Html5QrcodeScanner(
            "reader",
            {
                fps: 10,
                qrbox: 250,
                /* Filtros só para formatos de barras comuns de livro */
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.CODE_128
                ]
            },
            /* verbose = false */ false
        );

        let ultimoISBN = null;

        reader.render(onScanSuccess);

        function onScanSuccess(decodedText) {
            /* Evita chamadas repetidas para o mesmo código */
            if (decodedText === ultimoISBN) return;

            /* Limpa, mantém apenas dígitos ou X final (ISBN-10) */
            ultimoISBN = decodedText.replace(/[^0-9X]/gi, '');

            /* Feedback na tela */
            document.getElementById('erro').textContent = '';
            document.getElementById('info').innerHTML =
                "🔍 Consultando ISBN " + ultimoISBN + " …";

            /* Chamada ao endpoint PHP */
            fetch("/api/biblioteca/isbn.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    isbn:  ultimoISBN,
                    tg_id: Telegram.WebApp.initDataUnsafe?.user?.id || null
                })
            })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) throw new Error(d.error || "Falha no cadastro");

                /* Mostra dados retornados */
                document.getElementById('info').innerHTML =
                    `<b>Título:</b> ${d.titulo}<br>` +
                    `<b>Autor(es):</b> ${d.autor}<br>` +
                    `<b>ISBN:</b> ${ultimoISBN}`;

                /* Exibe botão Salvar */
                const btn = document.getElementById('salvar');
                btn.style.display = 'inline-block';
                btn.onclick = () => Telegram.WebApp.close();
            })
            .catch(err => {
                document.getElementById('erro').textContent = "❌ " + err.message;
                document.getElementById('salvar').style.display = 'none';
            });
        }
    </script>
</body>
</html>