<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner de Livros</title>

    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- TailwindCSS CDN (modo JIT) -->
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">

    <!-- html5-qrcode (versão minificada estável) -->
    <script src="https://unpkg.com/html5-qrcode@2.3.9/minified/html5-qrcode.min.js"></script>
</head>
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg flex flex-col items-center">
        <h1 class="text-2xl font-bold mb-6 text-blue-600">Cadastrar Livro</h1>

        <button id="btn-camera"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 px-6 rounded-lg mb-4 w-full text-lg shadow-md transition-colors">
            📸 Abrir Câmera
        </button>

        <!-- Área do scanner (inicia oculta) -->
        <div id="scanner"
             class="w-full min-h-[300px] rounded overflow-hidden"
             style="display:none;"></div>
    </div>

    <script>
        /* ------------------------------------------------------------------
           1. Inicializa o Mini-App do Telegram
        ------------------------------------------------------------------ */
        Telegram.WebApp.ready();
        Telegram.WebApp.expand();

        /* ------------------------------------------------------------------
           2. Referências de DOM
        ------------------------------------------------------------------ */
        const btnCamera  = document.getElementById('btn-camera');
        const scannerDiv = document.getElementById('scanner');
        let html5QrCode; // instância do leitor

        /* ------------------------------------------------------------------
           3. Clique no botão: abre câmera e inicia leitura
        ------------------------------------------------------------------ */
        btnCamera.addEventListener('click', () => {
            // UI
            btnCamera.style.display = 'none';
            scannerDiv.style.display = 'block';

            html5QrCode = new Html5Qrcode("scanner");

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 150 },
                aspectRatio: 1
            };

            html5QrCode.start(
                { facingMode: "environment" },  // câmera traseira
                config,
                onScanSuccess,
                /* onScanFailure */ () => {}
            ).catch(onCameraError);
        });

        /* ------------------------------------------------------------------
           4. Callback de sucesso: ISBN lido
        ------------------------------------------------------------------ */
        function onScanSuccess(decodedText) {
            html5QrCode.stop().then(() => {
                // feedback e UI
                Telegram.WebApp.HapticFeedback.notificationOccurred('success');
                scannerDiv.style.display = 'none';
                btnCamera.style.display = 'block';

                // envia para o backend
                fetch('/api/biblioteca/isbn.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        isbn: decodedText,
                        init_data: window.Telegram && window.Telegram.WebApp ? (window.Telegram.WebApp.initData || '') : ''
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        Telegram.WebApp.showAlert('✅ Livro cadastrado!');
                        Telegram.WebApp.close();
                    } else {
                        Telegram.WebApp.HapticFeedback.notificationOccurred('error');
                        Telegram.WebApp.showAlert('Erro: ' + (data.erro || data.error || 'Desconhecido'));
                    }
                })
                .catch(() => {
                    Telegram.WebApp.HapticFeedback.notificationOccurred('error');
                    Telegram.WebApp.showAlert('Erro ao conectar com o servidor.');
                });
            });
        }

        /* ------------------------------------------------------------------
           5. Tratamento de falha ao abrir a câmera
        ------------------------------------------------------------------ */
        function onCameraError(err) {
            Telegram.WebApp.HapticFeedback.notificationOccurred('error');
            alert('⚠️ Não foi possível acessar a câmera.\nVerifique as permissões do Telegram.');
            scannerDiv.style.display = 'none';
            btnCamera.style.display = 'block';
        }
    </script>
</body>
</html>
