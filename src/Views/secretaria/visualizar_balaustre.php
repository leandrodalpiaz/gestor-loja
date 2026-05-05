<?php
declare(strict_types=1);

$textoOficial = trim((string) ($balaustre['texto_final'] ?? ''));
if ($textoOficial === '') {
    $textoOficial = 'O texto oficial deste balaústre ainda não foi gerado.';
}

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Prévia Oficial do Balaústre';
$appShellDescription = 'Leitura contínua do snapshot usado em votação, impressão e arquivo.';
$appShellActiveHref = '/secretaria/balaustres';
require __DIR__ . '/_sidebar.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="javascript:history.back()" class="btn btn-secondary !py-2.5 !px-6 text-[10px] font-black uppercase tracking-widest">Voltar</a>
        <div class="flex flex-wrap gap-3">
            <button type="button" class="btn btn-secondary !py-2.5 !px-6 text-[10px] font-black uppercase tracking-widest" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('texto-balaustre-oficial').innerText)">Copiar texto</button>
            <button type="button" onclick="window.print()" class="btn btn-primary !py-2.5 !px-6 text-[10px] font-black uppercase tracking-widest">Imprimir</button>
        </div>
    </div>

    <div class="card depth-1">
        <div class="card-header border-b border-erp-border/50 p-6">
            <h1 class="text-2xl font-black text-erp-navy tracking-tight">Balaústre <?= htmlspecialchars((string) ($balaustre['numero_balaustre'] ?: 'S/N')) ?></h1>
            <p class="text-xs font-bold text-erp-muted uppercase tracking-widest mt-2"><?= htmlspecialchars((string) ($balaustre['sessao_titulo'] ?: 'Documento independente')) ?> · <?= htmlspecialchars((string) ($balaustre['status'] ?? 'rascunho')) ?></p>
        </div>
        <div class="card-body p-8 md:p-10">
            <pre id="texto-balaustre-oficial" class="whitespace-pre-wrap font-serif text-base leading-relaxed text-erp-navy"><?= htmlspecialchars($textoOficial) ?></pre>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .btn, .app-header, nav, aside { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    body { background: white !important; }
    .max-w-5xl { max-width: 100% !important; margin: 0 !important; }
}
</style>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
