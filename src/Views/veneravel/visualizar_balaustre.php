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
    <?php if ($mensagemSucesso): ?><div class="alert alert-success mb-4"><?= htmlspecialchars((string) $mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger mb-4"><?= htmlspecialchars((string) $mensagemErro) ?></div><?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="/veneravel" class="btn btn-secondary !py-2.5 !px-6 text-[10px] font-black uppercase tracking-widest">Voltar ao painel</a>
        <div class="flex flex-wrap gap-3">
            <button type="button"
                    class="btn btn-secondary !py-2.5 !px-6 text-[10px] font-black uppercase tracking-widest"
                    onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('texto-balaustre-oficial').innerText)">
                Copiar texto
            </button>
            <button type="button" onclick="window.print()" class="btn btn-primary !py-2.5 !px-6 text-[10px] font-black uppercase tracking-widest">Imprimir</button>
        </div>
    </div>

    <div class="card depth-1">
        <div class="card-header border-b border-erp-border/50 p-6">
            <h1 class="text-2xl font-black text-erp-navy tracking-tight">Balaústre <?= htmlspecialchars((string) ($balaustre['numero_balaustre'] ?: 'S/N')) ?></h1>
            <p class="text-xs font-bold text-erp-muted uppercase tracking-widest mt-2">
                <?= htmlspecialchars((string) ($balaustre['sessao_titulo'] ?: 'Documento independente')) ?>
                · <?= htmlspecialchars((string) ($balaustre['status'] ?? 'rascunho')) ?>
            </p>
        </div>
        <div class="card-body p-8 md:p-10">
            <pre id="texto-balaustre-oficial" class="whitespace-pre-wrap font-serif text-base leading-relaxed text-erp-navy"><?= htmlspecialchars($textoOficial) ?></pre>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Deliberação</h2>
                <p class="card-description">Escolha a próxima ação, mantendo a Secretaria como operadora do dia a dia.</p>
            </div>
            <div class="card-body space-y-4">
                <form method="POST" action="/veneravel/balaustre/enviar-votacao" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="balaustre_id" value="<?= (int) $balaustreId ?>">
                    <button type="submit" class="btn btn-primary">Enviar para votação</button>
                    <a href="/secretaria/balaustres/visualizar?id=<?= (int) $balaustreId ?>" class="btn btn-secondary">Ver como a Secretaria</a>
                </form>

                <div class="border-t border-erp-border/50 pt-4">
                    <form method="POST" action="/veneravel/balaustre/sugerir-edicao" class="space-y-3">
                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustreId ?>">
                        <label for="observacao" class="form-label">Sugestão de edição para a Secretaria</label>
                        <textarea id="observacao" name="observacao" rows="3" class="form-textarea" placeholder="Ex.: ajustar o trecho da Palavra a Bem da Ordem, incluir visitante ou corrigir título."></textarea>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-erp-muted">A sugestão não altera o texto automaticamente. Ela fica registrada para a Secretaria avaliar.</p>
                            <button type="submit" class="btn btn-secondary">Registrar sugestão</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Edição excepcional</h2>
                <p class="card-description">Quando necessário, edite pelo fluxo completo da Secretaria.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if ($sessaoId > 0): ?>
                    <a class="btn btn-secondary w-full" href="/veneravel/balaustre/editar?sessao_id=<?= (int) $sessaoId ?>">Abrir na Secretaria</a>
                <?php else: ?>
                    <a class="btn btn-secondary w-full" href="/veneravel/balaustre/editar?balaustre_sem_sessao=1">Abrir na Secretaria</a>
                <?php endif; ?>
                <p class="text-xs text-erp-muted">A edição direta pelo Venerável Mestre deve ser exceção e sempre registrada em Balaústre.</p>
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

