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

<div class="pwa-premium-page">

    <!-- Alertas -->
    <div id="isbn-toast" style="display:none;margin-bottom:1rem;"
         class="pwa-alert-success">
        📖 ISBN detectado — enviando para busca automática...
    </div>

    <?php if ($erro === 'isbn_vazio'): ?>
        <div class="pwa-alert-error" style="margin-bottom:1rem;">
            Por favor, informe um ISBN válido (10 ou 13 dígitos).
        </div>
    <?php elseif ($erro): ?>
        <div class="pwa-alert-error" style="margin-bottom:1rem;">
            Não foi possível adicionar o livro. Verifique o ISBN e tente novamente.
        </div>
    <?php endif; ?>

    <!-- Card principal de cadastro -->
    <div class="pwa-card" style="padding:1.25rem;">
        <!-- Ícone + Título -->
        <div style="display:flex;align-items:center;gap:0.875rem;margin-bottom:1rem;">
            <div style="
                width:48px;height:48px;
                border-radius:0.875rem;
                background:rgba(99,102,241,0.2);
                border:1px solid rgba(99,102,241,0.3);
                display:flex;align-items:center;justify-content:center;
                flex-shrink:0;
            ">
                <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="#a5b4fc" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <div>
                <h2 style="font-size:1rem;font-weight:800;color:#f1f5f9;margin:0;">Cadastrar por ISBN</h2>
                <p style="font-size:0.775rem;color:#94a3b8;margin:0.15rem 0 0;">
                    Escaneie o código de barras ou digite o ISBN
                </p>
            </div>
        </div>

        <!-- Painel do Scanner (oculto por padrão) -->
        <div id="isbn-scanner-panel" style="display:none;margin-bottom:1rem;">
            <!-- Viewfinder dark -->
            <div style="
                position:relative;
                border-radius:0.875rem;
                overflow:hidden;
                background:#000;
                border:1px solid rgba(99,102,241,0.35);
            ">
                <video id="isbn-video"
                       style="width:100%;height:200px;object-fit:cover;display:block;"
                       playsinline muted></video>
                <!-- Mira de leitura -->
                <div style="
                    position:absolute;
                    top:50%;left:50%;
                    transform:translate(-50%,-50%);
                    width:75%;height:50px;
                    border:2px solid #a5b4fc;
                    border-radius:0.5rem;
                    box-shadow:0 0 0 2000px rgba(0,0,0,0.45);
                    pointer-events:none;
                "></div>
                <!-- Linha de scan animada -->
                <div id="scan-line" style="
                    position:absolute;
                    left:12.5%;width:75%;
                    height:2px;
                    background:linear-gradient(to right, transparent, #a5b4fc, transparent);
                    border-radius:1px;
                    animation:scanMove 2s linear infinite;
                    pointer-events:none;
                "></div>
            </div>
            <style>
                @keyframes scanMove {
                    0%   { top: calc(50% - 25px); }
                    50%  { top: calc(50% + 23px); }
                    100% { top: calc(50% - 25px); }
                }
            </style>

            <p id="isbn-scan-status"
               style="font-size:0.75rem;color:#94a3b8;text-align:center;margin:0.625rem 0 0.5rem;font-weight:500;">
                Aponte a câmera para o código de barras do livro
            </p>
            <button type="button" id="isbn-stop-scan" class="pwa-btn-secondary" style="font-size:0.8125rem;">
                ✕ Cancelar leitura
            </button>
        </div>

        <!-- Formulário ISBN -->
        <form method="post" action="/pwa/biblioteca/adicionar" style="display:flex;flex-direction:column;gap:0.75rem;">
            <div>
                <label for="isbn" class="pwa-label">Código ISBN</label>
                <input type="text" inputmode="numeric" name="isbn" id="isbn" required
                       class="pwa-input"
                       placeholder="Ex: 9788531206161"
                       autocomplete="off"
                       autocorrect="off"
                       spellcheck="false">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
                <button type="button" id="isbn-start-scan" style="
                    display:flex;align-items:center;justify-content:center;gap:0.5rem;
                    padding:0.75rem 0.5rem;
                    background:rgba(99,102,241,0.18);
                    border:1px solid rgba(99,102,241,0.3);
                    border-radius:0.75rem;
                    font-size:0.8125rem;font-weight:700;
                    color:#a5b4fc;cursor:pointer;font-family:inherit;
                ">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Câmera
                </button>
                <button type="submit" class="pwa-btn-primary" style="font-size:0.8125rem;">
                    Buscar e Adicionar
                </button>
            </div>
        </form>
    </div>

    <!-- Info extra -->
    <p style="font-size:0.75rem;color:#475569;text-align:center;margin-top:0.875rem;line-height:1.5;">
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
