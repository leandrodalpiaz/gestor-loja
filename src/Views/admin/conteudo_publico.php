<?php

$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$erpPageTitle = 'Conteúdo público';
$appShellEyebrow = 'Sistema';
$appShellTitle = 'Landing/Login';
$appShellDescription = 'Agenda, convites, notícias e espaços discretos de apoio exibidos em /login.';
$appShellActiveHref = '/admin/conteudo-publico';
$appShellActions = [
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];

$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Nominata Oficial', 'href' => '/admin/cargos'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos', 'href' => '/admin/acessos'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaústres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatório Anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

if (!empty($_SESSION['is_system_admin'])) {
    $appShellSidebarSections[] = [
        'title' => 'Sistema',
        'items' => [
            ['label' => 'Painel do sistema', 'href' => '/sistema'],
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
        ],
    ];
}

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr] items-start">
    <div class="card depth-1">
        <div class="card-header border-b border-white/5 p-6">
            <div class="text-sm font-semibold text-white">Conteúdos cadastrados</div>
            <div class="mt-1 text-xs text-slate-400">Ative/desative e ajuste prioridade para controlar a exibição.</div>
        </div>

        <div class="divide-y divide-white/5">
            <?php if (empty($itens)): ?>
                <div class="px-6 py-8 text-sm text-slate-400">Nenhum conteúdo cadastrado ainda.</div>
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
                    <div class="p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/[0.02] px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-slate-300">
                                        <?= htmlspecialchars($tipo) ?>
                                    </span>
                                    <?php if ($publicado): ?>
                                        <span class="badge-status badge-status-success">Publicado</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-status-danger">Oculto</span>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center rounded-full border border-white/10 bg-white/[0.02] px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-slate-300">
                                        Prioridade <?= (int) $prioridade ?>
                                    </span>
                                </div>
                                <div class="mt-3 text-base font-semibold text-white break-words"><?= htmlspecialchars($titulo) ?></div>
                                <?php if ($resumo !== ''): ?>
                                    <div class="mt-2 text-sm leading-6 text-slate-400 break-words"><?= htmlspecialchars($resumo) ?></div>
                                <?php endif; ?>
                                <?php if ($inicioEm !== '' || $fimEm !== ''): ?>
                                    <div class="mt-3 text-xs text-slate-400">
                                        Janela:
                                        <span class="text-white font-medium"><?= htmlspecialchars($inicioEm !== '' ? $inicioEm : 'sem início') ?></span>
                                        &rarr;
                                        <span class="text-white font-medium"><?= htmlspecialchars($fimEm !== '' ? $fimEm : 'sem fim') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 self-start sm:self-center">
                                <button
                                    type="button"
                                    class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1.5 px-4 text-xs font-semibold"
                                    onclick="window.dispatchEvent(new CustomEvent('gl:conteudo-editar', { detail: <?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?> }))"
                                >Editar</button>
                                <form method="POST" action="/admin/conteudo-publico/excluir" onsubmit="return confirm('Remover este conteúdo?');">
                                    <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-danger py-1.5 px-4 text-xs font-semibold">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card depth-1">
        <div class="card-header border-b border-white/5 p-6">
            <div class="text-sm font-semibold text-white">Novo / editar</div>
            <div class="mt-1 text-xs text-slate-400">Use tipo <span class="font-semibold text-white">ad</span> para publicidade discreta.</div>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-success mx-6 mt-6">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger mx-6 mt-6">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/conteudo-publico/salvar" class="p-6">
            <input type="hidden" name="id" id="gl-id" value="">

            <div class="grid gap-5">
                <div class="grid gap-2">
                    <label for="gl-tipo" class="form-label">Tipo</label>
                    <select name="tipo" id="gl-tipo" class="form-select w-full">
                        <option value="agenda">Agenda</option>
                        <option value="convite">Convite</option>
                        <option value="noticia">Notícia</option>
                        <option value="ad">Ad (apoio)</option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <label for="gl-titulo" class="form-label">Título</label>
                    <input type="text" name="titulo" id="gl-titulo" class="form-input w-full" maxlength="180" required>
                </div>

                <div class="grid gap-2">
                    <label for="gl-resumo" class="form-label">Resumo (opcional)</label>
                    <input type="text" name="resumo" id="gl-resumo" class="form-input w-full" maxlength="320">
                </div>

                <div class="grid gap-2">
                    <label for="gl-corpo" class="form-label">Corpo (opcional)</label>
                    <textarea name="corpo" id="gl-corpo" rows="5" class="form-textarea w-full"></textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <label for="gl-link" class="form-label">Link (opcional)</label>
                        <input type="url" name="link_url" id="gl-link" class="form-input w-full" placeholder="https://...">
                    </div>
                    <div class="grid gap-2">
                        <label for="gl-imagem" class="form-label">Imagem URL (opcional)</label>
                        <input type="url" name="imagem_url" id="gl-imagem" class="form-input w-full" placeholder="https://...">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <label for="gl-prioridade" class="form-label">Prioridade</label>
                        <input type="number" name="prioridade" id="gl-prioridade" class="form-input w-full" value="0">
                    </div>
                    <div class="grid gap-2">
                        <label for="gl-inicio" class="form-label">Início (opcional)</label>
                        <input type="date" name="inicio_em" id="gl-inicio" class="form-input w-full">
                    </div>
                    <div class="grid gap-2">
                        <label for="gl-fim" class="form-label">Fim (opcional)</label>
                        <input type="date" name="fim_em" id="gl-fim" class="form-input w-full">
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] px-4 py-4">
                    <input type="checkbox" name="publicado" id="gl-publicado" class="form-checkbox" checked>
                    <label for="gl-publicado" class="form-check-label text-sm text-slate-300">Publicado (visível em /login)</label>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row pt-2">
                    <button type="submit" class="btn btn-primary px-6 py-3 text-sm font-semibold">Salvar</button>
                    <button type="button" class="btn border border-white/10 text-slate-300 hover:bg-white/5 px-6 py-3 text-sm font-semibold" onclick="window.dispatchEvent(new CustomEvent('gl:conteudo-reset'));">Limpar</button>
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
