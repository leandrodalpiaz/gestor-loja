<?php
// Mini App Telegram — Cadastro via scanner de ISBN
// Mesmos campos do novo.php + leitura de código de barras via câmera
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Scanner ISBN</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root {
            --bg: var(--tg-theme-bg-color, #F5F5F0);
            --bg2: var(--tg-theme-secondary-bg-color, #ffffff);
            --text: var(--tg-theme-text-color, #1a1a1a);
            --hint: var(--tg-theme-hint-color, #6b7280);
            --btn: var(--tg-theme-button-color, #0047AB);
            --btn-text: var(--tg-theme-button-text-color, #ffffff);
            --border: var(--tg-theme-hint-color, #d1d5db);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 16px;
            padding-bottom: 80px;
            text-align: center;
        }
        #scanner-container {
            background: var(--bg2);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 14px;
            border: 1px solid rgba(0,0,0,0.06);
        }
        #reader{width:100%;max-width:360px;margin:1rem auto}
        #info{margin-top:1rem}
        button{padding:.6rem 1.2rem;font-size:1rem;border:none;border-radius:6px;background:var(--btn);color:var(--btn-text);cursor:pointer}
        #salvar{display:none;margin-top:1rem;background:#22bb33}
        #erro{color:#d33;margin-top:1rem}
    </style>
</head>
<body>
    <h3>📚 Escaneie o código de barras (EAN/ISBN)</h3>
    <div id="reader"></div>
    <div id="info"></div>
    <button id="salvar">✅ Salvar</button>
    <div id="erro"></div>
    <script>
        Telegram.WebApp.ready();
        const reader = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: 250, formatsToSupport:[ Html5QrcodeSupportedFormats.CODE_128,
                                                      Html5QrcodeSupportedFormats.EAN_13,
                                                      Html5QrcodeSupportedFormats.EAN_8 ]},
            false);
        let ultimoISBN = null;
        reader.render(onScanSuccess);
        function onScanSuccess(decodedText){
            if(decodedText === ultimoISBN) return;
            ultimoISBN = decodedText.replace(/[^0-9X]/gi,'');
            document.getElementById("erro").textContent = "";
            document.getElementById("info").innerHTML = "🔍 Consultando ISBN "+ultimoISBN+" …";
            fetch("/api/biblioteca/isbn.php",{
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                body: JSON.stringify({ isbn: ultimoISBN, tg_id: Telegram.WebApp.initDataUnsafe?.user?.id ?? null })
            })
            .then(r=>r.json())
            .then(d=>{
                if(!d.ok){
                   throw new Error(d.error ?? "Falha no cadastro");
                }
                document.getElementById("info").innerHTML =
                    `<b>Título:</b> ${d.titulo}<br><b>Autor(es):</b> ${d.autor}<br><b>ISBN:</b> ${ultimoISBN}`;
                const btn = document.getElementById("salvar");
                btn.style.display="inline-block";
                btn.onclick = ()=>{ Telegram.WebApp.close(); };
            })
            .catch(err=>{
                document.getElementById("erro").textContent = "❌ "+err.message;
                document.getElementById("salvar").style.display="none";
            });
        }
    </script>
</body>
</html>
