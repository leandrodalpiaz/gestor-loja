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

    <?php
    // Mini-App Telegram – Cadastro de Livro por ISBN (Scanner)
    // Não há lógica em PHP: tudo acontece no front-end HTML/JS abaixo
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0,maximum-scale=1.0" />
        <title>Scanner ISBN</title>

        <!-- Telegram Mini-App -->
        <script src="https://telegram.org/js/telegram-web-app.js"></script>

        <!-- Biblioteca de leitura de QR-Code / Código de barras -->
        <script src="https://unpkg.com/html5-qrcode@2.3.9/html5-qrcode.min.js"></script>

        <style>
            :root{--tg-bg:#0d1117;--tg-fg:#f0f0f0;--tg-accent:#0088cc;}
            body{
                margin:0;padding:0;
                background:var(--tg-bg);color:var(--tg-fg);
                font-family:system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,sans-serif;
                text-align:center
            }
            h1{font-size:1.1rem;margin:16px 0}
            #reader{width:100%;max-width:320px;margin:0 auto}
            #info, #erro{margin-top:12px;white-space:pre-wrap}
            #salvar{
                display:none;margin-top:16px;padding:10px 20px;
                background:var(--tg-accent);color:#fff;border:none;border-radius:4px;font-size:1rem
            }
            #salvar:active{transform:scale(.96)}
        </style>
    </head>

    <body>
        <h1>📚 Escaneie o código ISBN</h1>
        <div id="reader"></div>

        <div id="info"></div>
        <?php
        /* Mini-App Telegram – Cadastro de Livro por ISBN (scanner)
             Nenhum processamento em PHP aqui, é só para garantir MIME text/html. */
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport"
                    content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <title>Scanner ISBN</title>

        <!-- Telegram JS API -->
        <script src="https://telegram.org/js/telegram-web-app.js"></script>

        <!-- html5-qrcode minificado -->
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

        <style>
        :root{--tg-bg:var(--tg-theme-bg-color,#fff);--tg-txt:var(--tg-theme-text-color,#222);
         --tg-btn:var(--tg-theme-button-color,#2e77ff);}
        body{margin:0;font-family:Arial,Helvetica,sans-serif;background:var(--tg-bg);color:var(--tg-txt);}
        h1{font-size:18px;margin:16px;text-align:center}
        #scanner{width:100%;max-width:360px;margin:0 auto}
        #info{padding:12px;font-size:15px;line-height:1.4}
        button{display:block;width:90%;max-width:360px;margin:12px auto;padding:12px;
                        font-size:16px;border:none;border-radius:4px;color:#fff;
                        background:var(--tg-btn);}
        #error{color:#d00;text-align:center;margin-top:8px}
        .hidden{display:none}
        </style>
        </head>

        <body>
        <h1>📚 Ler ISBN</h1>

        <div id="scanner"></div>

        <div id="info" class="hidden"></div>

        <button id="btnStartCam">🎥 Ativar câmera</button>
        <button id="btnSalvar"   class="hidden">✅ Salvar livro</button>

        <div id="error"></div>

        <script>
        const apiURL   = "/api/biblioteca/isbn.php";
        const qrDiv    = document.getElementById("scanner");
        const btnStart = document.getElementById("btnStartCam");
        const btnSave  = document.getElementById("btnSalvar");
        const infoDiv  = document.getElementById("info");
        const errDiv   = document.getElementById("error");

        let ultimoISBN = null;
        let qrScanner  = null;

        // 1) Inicia câmera
        btnStart.onclick = async () => {
            btnStart.classList.add("hidden");
            Telegram.WebApp.HapticFeedback.impactOccurred('light');
            qrScanner = new Html5Qrcode(/* element id */ qrDiv.id);
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            qrScanner.start({ facingMode: "environment" }, config, onScanSuccess)
                             .catch(e => showError("Permissão negada ou câmera não disponível"));
        };

        // 2) Callback de leitura
        function onScanSuccess(text) {
            // ISBN-10 ou 13 é só dígito
            const match = text.match(/(\d{10}|\d{13})/);
            if (!match) return;

            ultimoISBN = match[0];
            qrScanner.pause();
            fetchBook(ultimoISBN);
        }

        // 3) Consulta a API interna
        async function fetchBook(isbn){
            errDiv.textContent = "";
            infoDiv.textContent = "🔄 consultando Google Books…";
            infoDiv.classList.remove("hidden");

            try{
                const res = await fetch(apiURL, {
                    method:"POST",
                    headers:{ "Content-Type":"application/json" },
                    body: JSON.stringify({ isbn })
                });
                const data = await res.json();
                if(!res.ok) throw new Error(data.error || "Falha na consulta");

                infoDiv.innerHTML = `<b>Título:</b> ${data.titulo}<br>
                                                         <b>Autor(es):</b> ${data.autor}<br>
                                                         <b>ISBN:</b> ${isbn}`;
                btnSave.classList.remove("hidden");
                Telegram.WebApp.HapticFeedback.notificationOccurred('success');
            }catch(err){
                showError(err.message);
                qrScanner.resume();
            }
        }

        // 4) Salva no banco (usa a MESMA API, sem isbn)
        btnSave.onclick = async ()=>{
            try{
                const res = await fetch(apiURL, {
                    method:"POST", headers:{ "Content-Type":"application/json" },
                    body: JSON.stringify({ isbn: ultimoISBN, salvar:true })
                });
                const d = await res.json();
                if(!res.ok) throw new Error(d.error || "Erro ao salvar");

                Telegram.WebApp.showAlert("✅ Livro cadastrado!");
                Telegram.WebApp.close();
            }catch(e){ showError(e.message); }
        };

        function showError(msg){
            errDiv.textContent = "❌ " + msg;
            Telegram.WebApp.HapticFeedback.notificationOccurred('error');
        }
        </script>
        </body>
        </html>