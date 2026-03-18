<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini App Scanner de Livros</title>
    <!-- Telegram WebApp Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- html5-qrcode CDN -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
</head>
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg flex flex-col items-center">
        <h1 class="text-2xl font-bold mb-4 text-blue-600">Scanner de Livros</h1>
        <button id="btn-camera" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mb-4 transition">Abrir Câmera</button>
        <div id="scanner" class="w-full" style="display:none;"></div>
    </div>
    <script>
        const btnCamera = document.getElementById('btn-camera');
        const scannerDiv = document.getElementById('scanner');
        let html5QrcodeScanner = null;

        btnCamera.addEventListener('click', () => {
            btnCamera.style.display = 'none';
            scannerDiv.style.display = 'block';
            html5QrcodeScanner = new Html5Qrcode("scanner");
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 100 },
                    formatsToSupport: [Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8]
                },
                (decodedText, decodedResult) => {
                    // Sucesso: código lido
                    alert("ISBN lido: " + decodedText);
                    html5QrcodeScanner.stop().then(() => {
                        scannerDiv.style.display = 'none';
                        btnCamera.style.display = 'block';
                    });
                },
                (errorMessage) => {
                    // Ignorar erros de leitura
                }
            ).catch(err => {
                alert("Erro ao acessar a câmera: " + err);
                scannerDiv.style.display = 'none';
                btnCamera.style.display = 'block';
            });
        });
    </script>
</body>
</html>
