<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$erro = $_GET['erro'] ?? '';
$pwaPageTitle = 'Adicionar Titulo';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/biblioteca';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($erro === 'isbn_vazio'): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            Por favor, informe um ISBN valido.
        </div>
    <?php elseif ($erro): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            Nao foi possivel adicionar o livro. Verifique o ISBN e tente novamente.
        </div>
    <?php endif; ?>

    <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm space-y-4">
        <div>
            <h2 class="text-xl font-bold text-erpNavy leading-tight">Cadastrar por ISBN</h2>
            <p class="mt-1 text-sm text-erpMuted">
                Digite ou escaneie o codigo de barras do livro com a camera do celular.
            </p>
        </div>

        <div id="isbn-scanner-panel" class="hidden rounded-xl border border-erpBorder bg-erpBg p-3">
            <video id="isbn-video" class="h-48 w-full rounded-lg bg-slate-900 object-cover" playsinline muted></video>
            <button type="button" id="isbn-stop-scan" class="mt-2 w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm font-semibold text-erpNavy">Parar camera</button>
            <p id="isbn-scan-status" class="mt-2 text-xs text-erpMuted">Aponte a camera para o codigo de barras ISBN.</p>
        </div>

        <form method="post" action="/pwa/biblioteca/adicionar" class="space-y-4 pt-2">
            <div class="space-y-3">
                <label for="isbn" class="block text-sm font-semibold text-erpNavy">Codigo ISBN</label>
                <input type="text" inputmode="numeric" name="isbn" id="isbn" required
                       class="w-full rounded-lg border border-erpBorder bg-erpBg px-4 py-3 text-sm placeholder-erpMuted focus:border-erpNavy focus:outline-none"
                       placeholder="Ex: 9788531206161">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="isbn-start-scan" class="rounded-lg border border-erpBorder bg-white px-4 py-3 text-sm font-semibold text-erpNavy transition hover:bg-erpBg">
                    Ler pela camera
                </button>
                <button type="submit" class="rounded-lg bg-erpNavy px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                    Pesquisar e adicionar
                </button>
            </div>
        </form>
    </div>

    <div class="text-center text-xs text-erpMuted">
        O livro sera adicionado ao acervo da sua Loja com disponibilidade imediata.
    </div>
</div>

<script>
(function () {
    const startButton = document.getElementById('isbn-start-scan');
    const stopButton = document.getElementById('isbn-stop-scan');
    const panel = document.getElementById('isbn-scanner-panel');
    const video = document.getElementById('isbn-video');
    const input = document.getElementById('isbn');
    const status = document.getElementById('isbn-scan-status');
    let stream = null;
    let detector = null;
    let raf = null;

    function stopScanner() {
        if (raf) {
            cancelAnimationFrame(raf);
            raf = null;
        }
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        panel.classList.add('hidden');
    }

    async function scanLoop() {
        if (!detector || !stream) return;
        try {
            const codes = await detector.detect(video);
            if (codes && codes.length > 0) {
                const raw = String(codes[0].rawValue || '').replace(/[^0-9Xx]/g, '');
                if (raw.length >= 10) {
                    input.value = raw;
                    status.textContent = 'ISBN lido. Confira e toque em Pesquisar e adicionar.';
                    stopScanner();
                    return;
                }
            }
        } catch (e) {
            status.textContent = 'Nao foi possivel ler este quadro. Ajuste a distancia.';
        }
        raf = requestAnimationFrame(scanLoop);
    }

    startButton?.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window)) {
            status.textContent = 'Este navegador nao oferece leitura nativa. Digite o ISBN manualmente.';
            panel.classList.remove('hidden');
            return;
        }
        try {
            detector = new BarcodeDetector({formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e']});
            stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
            video.srcObject = stream;
            await video.play();
            panel.classList.remove('hidden');
            status.textContent = 'Camera ativa. Aponte para o codigo de barras.';
            scanLoop();
        } catch (e) {
            status.textContent = 'Nao foi possivel acessar a camera. Verifique as permissoes do navegador.';
            panel.classList.remove('hidden');
        }
    });

    stopButton?.addEventListener('click', stopScanner);
    window.addEventListener('pagehide', stopScanner);
})();
</script>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
