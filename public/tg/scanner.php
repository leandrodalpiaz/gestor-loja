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
            <?php
            // Mini-App Telegram – Cadastro de Livro por ISBN (scanner)
            // Nenhuma lógica em PHP; tudo acontece no front-end.
            ?>
            <!DOCTYPE html>
            <html lang="pt-BR">
            <head>
                <meta charset="UTF-8" />
                <meta name="viewport"
                            content="width=device-width,initial-scale=1.0,maximum-scale=1.0" />
                <title>📚 Ler ISBN</title>

                <!-- Telegram Mini-App SDK -->
                <script src="https://telegram.org/js/telegram-web-app.js"></script>

                <!-- Leitor de código de barras (EAN-13 / ISBN-13) -->
                <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

                <style>
                    :root{
                        --bg:   var(--tg-theme-bg-color,#f5f5f0);
                        --fg:   var(--tg-theme-text-color,#1a1a1a);
                        --btn:  var(--tg-theme-button-color,#1d4ed8);
                        --btnf: var(--tg-theme-button-text-color,#fff);
                    }
                    *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Inter','Segoe UI',sans-serif}
                    body   {background:var(--bg);color:var(--fg);padding:16px;padding-bottom:80px}
                    h1     {font-size:18px;font-weight:700;margin-bottom:12px}
                    #reader{width:100%;max-width:480px;margin:0 auto;border-radius:12px;overflow:hidden}
                    #info  {margin-top:18px;font-size:14px;line-height:1.45}
                    #erro  {margin-top:12px;color:#e53935;font-size:14px}
                    #btn-salvar{
                        margin-top:20px;padding:12px;width:100%;
                        background:var(--btn);color:var(--btnf);
                        border:none;border-radius:8px;font-weight:600;font-size:15px;
                        display:none;cursor:pointer
                    }
                </style>
            </head>
            <body>
                <h1>📷 Escaneie o código de barras</h1>
                <div id="reader"></div>

                <div id="info"></div>
                <div id="erro"></div>
                <button id="btn-salvar">✅ Salvar livro</button>

                <script>
                    Telegram.WebApp.ready();
                    const info  = document.getElementById('info');
                    const erro  = document.getElementById('erro');
                    const salvar= document.getElementById('btn-salvar');
                    let   ultimoISBN = null;

                    const reader = new Html5Qrcode("reader");
                    reader.start(
                        { facingMode:"environment" },            // câmera traseira
                        { fps:10, qrbox:{ width:250, height:120 } },
                        onScanSuccess,
                        () =>{}                                  // onScanFailure (ignorado)
                    ).catch(()=>erro.textContent="❌ Não foi possível abrir a câmera. Verifique as permissões.");

                    async function onScanSuccess(text){
                        const isbnLimpo = text.replace(/[^0-9X]/gi,'');
                        if(isbnLimpo === ultimoISBN) return;     // evita múltiplas leituras
                        ultimoISBN = isbnLimpo;
                        erro.textContent = "";
                        info.innerHTML = `🔍 Consultando ISBN <b>${isbnLimpo}</b>…`;

                        try{
                            const res = await fetch("/api/biblioteca/isbn.php",{
                                method:"POST",
                                headers:{ "Content-Type":"application/json" },
                                body:JSON.stringify({ isbn:isbnLimpo })
                            });
                            const d = await res.json();
                            if(!d.ok) throw new Error(d.error || "Falha no cadastro");

                            info.innerHTML =
                                `<b>Título:</b> ${d.titulo}<br>` +
                                `<b>Autor(es):</b> ${d.autor}<br>`  +
                                `<b>ISBN:</b> ${isbnLimpo}`;

                            salvar.style.display = "block";
                            salvar.onclick = ()=>{
                                Telegram.WebApp.showAlert("✅ Livro cadastrado!");
                                Telegram.WebApp.close();
                            };
                            Telegram.WebApp.HapticFeedback.notificationOccurred('success');
                        }catch(e){
                            erro.textContent = "❌ " + e.message;
                            salvar.style.display = "none";
                            Telegram.WebApp.HapticFeedback.notificationOccurred('error');
                        }
                    }
                </script>
            </body>
            </html>
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