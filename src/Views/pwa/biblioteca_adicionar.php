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
    <div id="isbn-toast" class="hidden rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 shadow-sm">
        ISBN detectado. Enviando para busca automatica...
    </div>
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
    const html5Script = document.createElement('script');
    html5Script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
    html5Script.async = true;
    document.head.appendChild(html5Script);

    const startButton = document.getElementById('isbn-start-scan');
    const stopButton = document.getElementById('isbn-stop-scan');
    const panel = document.getElementById('isbn-scanner-panel');
    const video = document.getElementById('isbn-video');
    const input = document.getElementById('isbn');
    const status = document.getElementById('isbn-scan-status');
    const toast = document.getElementById('isbn-toast');
    const form = input ? input.closest('form') : null;
    let stream = null;
    let detector = null;
    let raf = null;
    let html5Reader = null;
    let lastIsbn = '';

    function normalizeIsbn(raw) {
        return String(raw || '').replace(/[^0-9Xx]/g, '').toUpperCase();
    }

    function finishWithIsbn(raw) {
        const isbn = normalizeIsbn(raw);
        if (isbn.length < 10 || isbn === lastIsbn) return false;
        lastIsbn = isbn;
        input.value = isbn;
        status.textContent = 'ISBN lido. Executando busca automaticamente...';
        if (toast) {
            toast.classList.remove('hidden');
        }
        stopScanner();
        setTimeout(() => {
            if (form && typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else if (form) {
                form.submit();
            }
        }, 200);
        return true;
    }

    function stopScanner() {
        if (raf) {
            cancelAnimationFrame(raf);
            raf = null;
        }
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        if (html5Reader) {
            html5Reader.stop().catch(() => {}).finally(() => { html5Reader = null; });
        }
        panel.classList.add('hidden');
    }

    async function scanLoop() {
        if (!detector || !stream) return;
        try {
            const codes = await detector.detect(video);
            if (codes && codes.length > 0) {
                if (finishWithIsbn(codes[0].rawValue || '')) {
                    return;
                }
            }
        } catch (e) {
            status.textContent = 'Nao foi possivel ler este quadro. Ajuste a distancia.';
        }
        raf = requestAnimationFrame(scanLoop);
    }

    startButton?.addEventListener('click', async () => {
        panel.classList.remove('hidden');
        status.textContent = 'Ativando camera...';
        try {
            if ('BarcodeDetector' in window) {
                detector = new BarcodeDetector({formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e']});
                stream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
                video.srcObject = stream;
                await video.play();
                status.textContent = 'Camera ativa. Aponte para o codigo de barras.';
                scanLoop();
                return;
            }

            if (window.Html5Qrcode) {
                const readerId = 'isbn-video-reader';
                let readerEl = document.getElementById(readerId);
                if (!readerEl) {
                    readerEl = document.createElement('div');
                    readerEl.id = readerId;
                    readerEl.className = 'h-48 w-full rounded-lg bg-slate-900 overflow-hidden';
                    video.classList.add('hidden');
                    panel.insertBefore(readerEl, stopButton);
                }
                html5Reader = new Html5Qrcode(readerId);
                await html5Reader.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 260, height: 120 } },
                    (decodedText) => {
                        if (finishWithIsbn(decodedText)) {
                            status.textContent = 'ISBN lido. Executando busca automaticamente...';
                        }
                    },
                    () => {}
                );
                status.textContent = 'Camera ativa. Aponte para o codigo de barras.';
                return;
            }

            status.textContent = 'Leitor de codigo indisponivel neste navegador. Digite o ISBN manualmente.';
        } catch (e) {
            status.textContent = 'Nao foi possivel acessar a camera. Verifique as permissoes do navegador.';
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
