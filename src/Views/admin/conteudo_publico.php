<?php

$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$erpPageTitle = 'Conteudo publico';
$appShellEyebrow = 'Administracao';
$appShellTitle = 'Landing/Login';
$appShellDescription = 'Agenda, convites, noticias e espacos discretos de apoio exibidos em /login.';
$appShellActiveHref = '/admin/conteudo-publico';
$appShellActions = [
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Administracao',
        'items' => [
            ['label' => 'Conteudo publico', 'href' => '/admin/conteudo-publico'],
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
            ['label' => 'Parametros da Loja', 'href' => '/admin/loja'],
            ['label' => 'Dashboard', 'href' => '/dashboard'],
        ],
    ],
];

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
    <div class="rounded-2xl border border-erpBorder bg-white">
        <div class="border-b border-erpBorder px-6 py-5">
            <div class="text-sm font-semibold text-erpNavy">Conteudos cadastrados</div>
            <div class="mt-1 text-sm text-erpMuted">Ative/desative e ajuste prioridade para controlar a exibicao.</div>
        </div>

        <div class="divide-y divide-erpBorder">
            <?php if (empty($itens)): ?>
                <div class="px-6 py-8 text-sm text-erpMuted">Nenhum conteudo cadastrado ainda.</div>
            <?php else: ?>
                <?php foreach ($itens as $item): ?>
                    <?php
                    $tipo = (string) ($item['tipo'] ?? '');
                    $titulo = (string) ($item['titulo'] ?? '');
                    $resumo = (string) ($item['resumo'] ?? '');
                    $publicado = (bool) ($item['publicado'] ?? false);
                    $prioridade = (int) ($item['prioridade'] ?? 0);
                    $inicioEm = (string) ($item['inicio_em'] ?? '');
                    $fimEm = (string) ($item['fim_em'] ?? '');
                    ?>
                    <div class="px-6 py-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border border-erpBorder bg-slate-50 px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-erpMuted">
                                        <?= htmlspecialchars($tipo) ?>
                                    </span>
                                    <?php if ($publicado): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-emerald-700">Publicado</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-rose-700">Oculto</span>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center rounded-full bg-erpBg px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-erpMuted">
                                        Prioridade <?= (int) $prioridade ?>
                                    </span>
                                </div>
                                <div class="mt-3 text-base font-semibold text-erpText break-words"><?= htmlspecialchars($titulo) ?></div>
                                <?php if ($resumo !== ''): ?>
                                    <div class="mt-2 text-sm leading-6 text-erpMuted break-words"><?= htmlspecialchars($resumo) ?></div>
                                <?php endif; ?>
                                <?php if ($inicioEm !== '' || $fimEm !== ''): ?>
                                    <div class="mt-3 text-xs text-erpMuted">
                                        Janela:
                                        <?= htmlspecialchars($inicioEm !== '' ? $inicioEm : 'sem inicio') ?>
                                        &rarr;
                                        <?= htmlspecialchars($fimEm !== '' ? $fimEm : 'sem fim') ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-xl border border-erpBorder bg-white px-4 py-2 text-sm font-semibold text-erpNavy hover:bg-erpBg"
                                    onclick="window.dispatchEvent(new CustomEvent('gl:conteudo-editar', { detail: <?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?> }))"
                                >Editar</button>
                                <form method="POST" action="/admin/conteudo-publico/excluir" onsubmit="return confirm('Remover este conteudo?');">
                                    <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                    <button type="submit" class="inline-flex items-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="rounded-2xl border border-erpBorder bg-white">
        <div class="border-b border-erpBorder px-6 py-5">
            <div class="text-sm font-semibold text-erpNavy">Novo / editar</div>
            <div class="mt-1 text-sm text-erpMuted">Use tipo <span class="font-semibold">ad</span> para publicidade discreta.</div>
        </div>

        <?php if ($mensagem): ?>
            <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mx-6 mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/conteudo-publico/salvar" class="px-6 py-6">
            <input type="hidden" name="id" id="gl-id" value="">

            <div class="grid gap-4">
                <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                    Tipo
                    <select name="tipo" id="gl-tipo" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none">
                        <option value="agenda">Agenda</option>
                        <option value="convite">Convite</option>
                        <option value="noticia">Noticia</option>
                        <option value="ad">Ad (apoio)</option>
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                    Titulo
                    <input type="text" name="titulo" id="gl-titulo" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none" maxlength="180" required>
                </label>

                <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                    Resumo (opcional)
                    <input type="text" name="resumo" id="gl-resumo" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none" maxlength="320">
                </label>

                <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                    Corpo (opcional)
                    <textarea name="corpo" id="gl-corpo" rows="5" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none"></textarea>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                        Link (opcional)
                        <input type="url" name="link_url" id="gl-link" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none" placeholder="https://...">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                        Imagem URL (opcional)
                        <input type="url" name="imagem_url" id="gl-imagem" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none" placeholder="https://...">
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                        Prioridade
                        <input type="number" name="prioridade" id="gl-prioridade" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none" value="0">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                        Inicio (opcional)
                        <input type="date" name="inicio_em" id="gl-inicio" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-erpNavy">
                        Fim (opcional)
                        <input type="date" name="fim_em" id="gl-fim" class="w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-sm text-erpText focus:border-erpGold focus:bg-white focus:outline-none">
                    </label>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-erpBorder bg-slate-50 px-4 py-4 text-sm font-semibold text-erpNavy">
                    <input type="checkbox" name="publicado" id="gl-publicado" class="h-4 w-4 rounded border-erpBorder text-erpNavyDeep" checked>
                    Publicado (visivel em /login)
                </label>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-erpNavyDeep px-6 py-3 text-sm font-semibold text-white hover:opacity-95">Salvar</button>
                    <button type="button" class="inline-flex items-center justify-center rounded-xl border border-erpBorder bg-white px-6 py-3 text-sm font-semibold text-erpMuted hover:bg-erpBg" onclick="window.dispatchEvent(new CustomEvent('gl:conteudo-reset'));">Limpar</button>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    function glResetForm() {
        document.getElementById('gl-id').value = '';
        document.getElementById('gl-tipo').value = 'agenda';
        document.getElementById('gl-titulo').value = '';
        document.getElementById('gl-resumo').value = '';
        document.getElementById('gl-corpo').value = '';
        document.getElementById('gl-link').value = '';
        document.getElementById('gl-imagem').value = '';
        document.getElementById('gl-prioridade').value = '0';
        document.getElementById('gl-inicio').value = '';
        document.getElementById('gl-fim').value = '';
        document.getElementById('gl-publicado').checked = true;
    }

    window.addEventListener('gl:conteudo-reset', glResetForm);

    window.addEventListener('gl:conteudo-editar', function (event) {
        const item = (event && event.detail) ? event.detail : null;
        if (!item) return;

        document.getElementById('gl-id').value = item.id || '';
        document.getElementById('gl-tipo').value = item.tipo || 'agenda';
        document.getElementById('gl-titulo').value = item.titulo || '';
        document.getElementById('gl-resumo').value = item.resumo || '';
        document.getElementById('gl-corpo').value = item.corpo || '';
        document.getElementById('gl-link').value = item.link_url || '';
        document.getElementById('gl-imagem').value = item.imagem_url || '';
        document.getElementById('gl-prioridade').value = (item.prioridade ?? 0);
        document.getElementById('gl-inicio').value = item.inicio_em || '';
        document.getElementById('gl-fim').value = item.fim_em || '';
        document.getElementById('gl-publicado').checked = !!item.publicado;

        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

