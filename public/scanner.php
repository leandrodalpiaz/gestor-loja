<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Scanner de Livros</title>
    <!-- Telegram WebApp Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- html5-qrcode CDN -->
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg flex flex-col items-center">
        <h1 class="text-2xl font-bold mb-6 text-blue-600">Cadastrar Livro</h1>

        <button id="btn-camera" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 px-6 rounded-lg mb-4 w-full text-lg shadow-md transition-colors">
            📸 Abrir Câmera
        </button>

        <!-- Adicionado min-height para evitar quebra da biblioteca -->
        <div id="scanner" class="w-full min-h-[300px] rounded overflow-hidden" style="display:none;"></div>
    </div>

    <script>
        // 1. Inicializa e expande o Mini App do Telegram
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();

        const btnCamera = document.getElementById('btn-camera');
        const scannerDiv = document.getElementById('scanner');
        let html5QrCode;

        btnCamera.addEventListener('click', () => {
            // Esconde o botão e mostra a área do scanner
            btnCamera.style.display = 'none';
            scannerDiv.style.display = 'block';

            // Instancia o leitor
            html5QrCode = new Html5Qrcode("scanner");

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 150 },
                aspectRatio: 1.0
            };

            // Inicia a câmera traseira
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    // SUCESSO: Código lido!
                    html5QrCode.stop().then(() => {
                        scannerDiv.style.display = 'none';
                        btnCamera.style.display = 'block';

                        // Vibra o celular usando a API do Telegram
                        window.Telegram.WebApp.HapticFeedback.notificationOccurred('success');

                        // Mostra o resultado
                        alert("📚 ISBN Lido com sucesso: " + decodedText);
                    });
                },
                (errorMessage) => {
                    // Ignora erros de frame (normal enquanto a câmera tenta focar)
                }
            ).catch((err) => {
                // ERRO: Câmera bloqueada ou não encontrada
                alert("Erro ao acessar a câmera. Verifique as permissões do Telegram no seu celular.");
                scannerDiv.style.display = 'none';
                btnCamera.style.display = 'block';
            });
        });
    </script>
</body>
</html>