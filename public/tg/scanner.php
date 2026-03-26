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
        <div id="erro" style="color:#ff4d4f"></div>

        <button id="salvar">✅ Salvar</button>

        <script>
            Telegram.WebApp.ready();                    // notifica o Telegram que o app carregou
            Telegram.WebApp.expand();                  // força abrir em tela cheia

            const reader      = new Html5Qrcode("reader");
            const salvarBtn   = document.getElementById("salvar");
            const infoBox     = document.getElementById("info");
            const erroBox     = document.getElementById("erro");
            let   ultimoISBN  = null;                  // guarda o ISBN lido

            function startScanner(){
                reader.start(
                    { facingMode:"environment" },
                    { fps:10, qrbox:250, formatsToSupport:["EAN_13"] },
                    onScanSuccess,
                    err => { /* ruído do scanner: ignora */ }
                ).catch(err=>{
                    erroBox.textContent = "❌ Não foi possível acessar a câmera: "+err;
                });
            }

            async function onScanSuccess(decodedText){
                // Evita disparar N vezes a cada frame
                if(decodedText===ultimoISBN) return;
                ultimoISBN = decodedText;
                Html5QrcodeScanner.clearTimeout();       // trava o scanner

                infoBox.textContent = "🔎 Consultando ISBN "+ultimoISBN+"…";

                try{
                    const rsp = await fetch("/api/biblioteca/isbn.php",{
                        method:"POST",
                        headers:{ "Content-Type":"application/json" },
                        body:JSON.stringify({ isbn: ultimoISBN })
                    });
                    const data = await rsp.json();
                    if(!rsp.ok) throw new Error(data.error || "Falha no cadastro");

                    infoBox.innerHTML =
                        `<b>Título:</b> ${data.titulo}<br>`+
                        `<b>Autor(es):</b> ${data.autor}<br>`+
                        `<b>ISBN:</b> ${ultimoISBN}`;

                    salvarBtn.style.display = "inline-block";
                }catch(e){
                    erroBox.textContent = "❌ "+e.message;
                    salvarBtn.style.display = "none";
                }
            }

            salvarBtn.onclick = ()=>{ Telegram.WebApp.close(); };

            // inicia quando o usuário realmente abre o mini-app
            startScanner();
        </script>
    </body>
    </html>
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