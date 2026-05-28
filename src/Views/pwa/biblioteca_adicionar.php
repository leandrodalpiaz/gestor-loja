<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$erro = $_GET['erro'] ?? '';
$pwaPageTitle      = 'Adicionar Livro';
$pwaShowBackButton = true;
$pwaBackUrl        = '/pwa/biblioteca';
$pwaActiveTab      = 'biblioteca';

ob_start();
?>

<div class="px-4 py-4 space-y-4">

    <!-- Alertas -->
    <div id="isbn-toast" style="display:none;" class="pwa-alert-success">
        📖 ISBN detectado — enviando para busca automática...
    </div>

    <?php if ($erro === 'isbn_vazio'): ?>
        <div class="pwa-alert-error">
            Por favor, informe um ISBN válido (10 ou 13 dígitos).
        </div>
    <?php elseif ($erro): ?>
        <div class="pwa-alert-error">
            Não foi possível adicionar o livro. Verifique o ISBN e tente novamente.
        </div>
    <?php endif; ?>

    <!-- Card principal de cadastro -->
    <div class="pwa-card space-y-3.5">
        <!-- Ícone + Título -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#a5b4fc" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-100">Cadastrar por ISBN</h2>
                <p class="text-[10px] text-slate-400 mt-0.5">
                    Escaneie o código de barras ou digite o ISBN
                </p>
            </div>
        </div>

        <!-- Painel do Scanner (oculto por padrão) -->
        <div id="isbn-scanner-panel" style="display:none;" class="space-y-3">
            <!-- Viewfinder dark -->
            <div class="relative rounded-2xl overflow-hidden bg-black border border-indigo-500/20">
                <video id="isbn-video" class="w-full h-[200px] object-cover block" playsinline muted></video>
                <!-- Mira de leitura -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[75%] h-[50px] border-2 border-indigo-400 rounded-lg shadow-[0_0_0_2000px_rgba(0,0,0,0.55)] pointer-events-none"></div>
                <!-- Linha de scan animada -->
                <div id="scan-line" class="absolute left-[12.5%] w-[75%] h-[2px] bg-gradient-to-r from-transparent via-indigo-400 to-transparent rounded-full animate-[scanMove_2s_linear_infinite] pointer-events-none"></div>
            </div>
            <style>
                @keyframes scanMove {
                    0%   { top: calc(50% - 25px); }
                    50%  { top: calc(50% + 23px); }
                    100% { top: calc(50% - 25px); }
                }
            </style>

            <p id="isbn-scan-status" class="text-[10px] text-slate-400 text-center font-medium mt-1">
                Aponte a câmera para o código de barras do livro
            </p>
            <button type="button" id="isbn-stop-scan" class="pwa-btn-secondary text-xs select-none">
                ✕ Cancelar leitura
            </button>
        </div>

        <!-- Formulário ISBN -->
        <form method="post" action="/pwa/biblioteca/adicionar" class="space-y-3.5">
            <div>
                <label for="isbn" class="pwa-label">Código ISBN</label>
                <input type="text" inputmode="numeric" name="isbn" id="isbn" required
                       class="pwa-input"
                       placeholder="Ex: 9788531206161"
                       autocomplete="off"
                       autocorrect="off"
                       spellcheck="false">
            </div>

            <div class="grid grid-cols-2 gap-2 select-none">
                <button type="button" id="isbn-start-scan" class="pwa-btn-secondary py-2.5 text-xs font-bold bg-indigo-500/10 border-indigo-500/20 text-indigo-300">
                    <svg class="h-4.5 w-4.5 mr-1.5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Câmera
                </button>
                <button type="submit" class="pwa-btn-primary py-2.5 text-xs font-bold">
                    Buscar e Salvar
                </button>
            </div>
        </form>
    </div>

    <!-- Info extra -->
    <p class="text-[10px] text-slate-500 text-center leading-relaxed select-none">
        O livro é cadastrado automaticamente via Google Books<br>e adicionado ao acervo da sua Loja.
    </p>
</div>

<script>
(function () {
    const html5Script = document.createElement('script');
    html5Script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
    html5Script.async = true;
    document.head.appendChild(html5Script);

    const startButton  = document.getElementById('isbn-start-scan');
    const stopButton   = document.getElementById('isbn-stop-scan');
    const panel        = document.getElementById('isbn-scanner-panel');
    const video        = document.getElementById('isbn-video');
    const input        = document.getElementById('isbn');
    const statusEl     = document.getElementById('isbn-scan-status');
    const toast        = document.getElementById('isbn-toast');
    const form         = input ? input.closest('form') : null;
    let stream         = null;
    let detector       = null;
    let raf            = null;
    let html5Reader    = null;
    let lastIsbn       = '';

    function normalizeIsbn(raw) {
        return String(raw || '').replace(/[^0-9Xx]/g, '').toUpperCase();
    }

    function finishWithIsbn(raw) {
        const isbn = normalizeIsbn(raw);
        if (isbn.length < 10 || isbn === lastIsbn) return false;
        lastIsbn = isbn;
        input.value = isbn;
        if (statusEl) statusEl.textContent = '✓ ISBN lido — buscando dados...';
        if (toast) { toast.style.display = 'block'; }
        stopScanner();
        if (window.navigator && window.navigator.vibrate) navigator.vibrate(60);
        setTimeout(() => {
            if (form && typeof form.requestSubmit === 'function') form.requestSubmit();
            else if (form) form.submit();
        }, 300);
        return true;
    }

    function stopScanner() {
        if (raf) { cancelAnimationFrame(raf); raf = null; }
        if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
        if (html5Reader) {
            html5Reader.stop().catch(() => {}).finally(() => { html5Reader = null; });
        }
        panel.style.display = 'none';
    }

    async function scanLoop() {
        if (!detector || !stream) return;
        try {
            const codes = await detector.detect(video);
            if (codes && codes.length > 0) {
                if (finishWithIsbn(codes[0].rawValue || '')) return;
            }
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Ajuste a distância para focar no código.';
        }
        raf = requestAnimationFrame(scanLoop);
    }

    startButton?.addEventListener('click', async () => {
        panel.style.display = 'block';
        if (statusEl) statusEl.textContent = 'Ativando câmera...';
        try {
            if ('BarcodeDetector' in window) {
                detector = new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e'] });
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                await video.play();
                if (statusEl) statusEl.textContent = 'Aponte para o código de barras do livro';
                scanLoop();
                return;
            }

            if (window.Html5Qrcode) {
                const readerId = 'isbn-video-reader';
                let readerEl = document.getElementById(readerId);
                if (!readerEl) {
                    readerEl = document.createElement('div');
                    readerEl.id = readerId;
                    readerEl.style.cssText = 'height:200px;width:100%;overflow:hidden;border-radius:0.75rem;';
                    video.style.display = 'none';
                    video.parentNode.insertBefore(readerEl, video);
                }
                html5Reader = new Html5Qrcode(readerId);
                await html5Reader.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 260, height: 100 } },
                    (decodedText) => { finishWithIsbn(decodedText); },
                    () => {}
                );
                if (statusEl) statusEl.textContent = 'Aponte para o código de barras do livro';
                return;
            }

            if (statusEl) statusEl.textContent = 'Leitor não disponível neste navegador. Digite o ISBN manualmente.';
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Não foi possível acessar a câmera. Verifique as permissões.';
        }
    });

    stopButton?.addEventListener('click', stopScanner);
    window.addEventListener('pagehide', stopScanner);
    window.addEventListener('visibilitychange', () => {
        if (document.hidden) stopScanner();
    });
})();
</script>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
