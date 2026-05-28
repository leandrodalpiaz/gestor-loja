<?php
declare(strict_types=1);

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$textoOficial = trim((string) ($balaustre['texto_final'] ?? ''));
if ($textoOficial === '') {
    $textoOficial = 'O texto oficial deste Balaústre ainda não foi gerado.';
}

$sessaoId = (int) ($balaustre['sessao_id'] ?? 0);
$balaustreId = (int) ($balaustre['id'] ?? 0);

$appShellEyebrow = 'Venerável Mestre';
$appShellTitle = 'Prévia Oficial do Balaústre';
$appShellDescription = 'Validação executiva do texto final antes de seguir para votação e arquivo.';
$appShellActiveHref = '/veneravel';
$appShellActions = [
    ['label' => 'Painel', 'href' => '/veneravel'],
    ['label' => 'Secretaria', 'href' => '/secretaria'],
];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="max-w-5xl mx-auto space-y-6">
    <?php if ($mensagemSucesso): ?>
        <div class="alert alert-success mb-4"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="/veneravel" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-6 text-xs font-semibold">
            Voltar ao Painel
        </a>
        <div class="flex flex-wrap gap-3">
            <button type="button"
                    class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-6 text-xs font-semibold"
                    onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('texto-balaustre-oficial').innerText)">
                Copiar Texto
            </button>
            <button type="button" onclick="window.print()" class="btn btn-primary py-2 px-6 text-xs font-semibold">
                Imprimir
            </button>
        </div>
    </div>

    <div class="card depth-1">
        <div class="card-header border-b border-white/5 p-6">
            <h1 class="text-2xl font-black text-white tracking-tight">Balaústre <?= htmlspecialchars((string) ($balaustre['numero_balaustre'] ?: 'S/N')) ?></h1>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">
                <?= htmlspecialchars((string) ($balaustre['sessao_titulo'] ?: 'Documento independente')) ?>
                · <span class="text-erp-gold"><?= htmlspecialchars((string) ($balaustre['status'] ?? 'rascunho')) ?></span>
            </p>
        </div>
        <div class="card-body p-8 md:p-10 bg-black/10 rounded-b-xl">
            <pre id="texto-balaustre-oficial" class="whitespace-pre-wrap font-serif text-base leading-relaxed text-slate-200"><?= htmlspecialchars($textoOficial) ?></pre>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card depth-1 lg:col-span-2">
            <div class="card-header border-b border-white/5 p-6">
                <h2 class="card-title text-white">Deliberação</h2>
                <p class="card-subtitle mt-1">Homologue o texto e encaminhe para votação.</p>
            </div>
            <div class="card-body p-6 space-y-4">
                <form method="POST" action="/veneravel/balaustre/enviar-votacao" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="balaustre_id" value="<?= (int) $balaustreId ?>">
                    <button type="submit" class="btn btn-primary">Homologar e Abrir Votação</button>
                    <a href="/secretaria/balaustres/visualizar?id=<?= (int) $balaustreId ?>" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-sm font-semibold">
                        Ver como a Secretaria
                    </a>
                </form>

                <div class="border-t border-white/5 pt-4">
                    <form method="POST" action="/veneravel/balaustre/sugerir-edicao" class="space-y-3">
                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustreId ?>">
                        <label for="observacao" class="form-label text-slate-300">Solicitar Ajustes à Secretaria</label>
                        <textarea id="observacao" name="observacao" rows="3" class="form-textarea w-full" placeholder="Ex.: ajustar o trecho da Palavra a Bem da Ordem, incluir visitante ou corrigir título."></textarea>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                            <p class="text-xs text-slate-400">A sugestão não altera o texto automaticamente, ela será analisada pela Secretaria.</p>
                            <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-6 text-xs font-semibold">
                                Registrar Sugestão
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Edição Excepcional</h2>
                <p class="card-subtitle mt-1">Abrir editor na Secretaria caso necessário.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if ($sessaoId > 0): ?>
                    <a class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full text-center text-xs font-semibold py-2.5 block" href="/veneravel/balaustre/editar?sessao_id=<?= (int) $sessaoId ?>">
                        Abrir Editor
                    </a>
                <?php else: ?>
                    <a class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full text-center text-xs font-semibold py-2.5 block" href="/veneravel/balaustre/editar?balaustre_sem_sessao=1">
                        Abrir Editor
                    </a>
                <?php endif; ?>
                <p class="text-xs text-slate-400 leading-relaxed">
                    A edição direta pelo Venerável Mestre deve ser a exceção, mantendo o controle operacional com o Secretário.
                </p>
            </div>
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
