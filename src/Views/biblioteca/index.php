<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$lista = $itens ?? [];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';

$bibliotecaPermissions = is_array($bibliotecaPermissions ?? null) ? $bibliotecaPermissions : [];
$podeGerenciar = !empty($bibliotecaPermissions['biblioteca.manage']);
$podeClassificar = !empty($bibliotecaPermissions['biblioteca.classificar']);
$redeConfigTela = is_array($bibliotecaRedeConfig ?? null) ? $bibliotecaRedeConfig : [];
$redeCompartilha = !empty($redeConfigTela['compartilhar_acervo']);
$redePermiteEmprestimo = !empty($redeConfigTela['permitir_emprestimo_cruzado']);

$formatGrau = static fn($grau) => $grau ? ucfirst(strtolower($grau)) : 'Livre';

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Catálogo da Loja';
$appShellDescription = 'Consulte o acervo, verifique a disponibilidade e use o grau recomendado como orientação de leitura.';
$appShellActiveHref = '/biblioteca';
$appShellActions = [
    ['label' => 'Painel', 'href' => '/dashboard'],
];

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Cabeçalho com Ações e Filtros -->
<?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
    <div class="alert alert-success mb-6">
        <?= htmlspecialchars((string) $_SESSION['mensagem_sucesso']) ?>
    </div>
    <?php unset($_SESSION['mensagem_sucesso']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['mensagem_erro'])): ?>
    <div class="alert alert-danger mb-6">
        <?= htmlspecialchars((string) $_SESSION['mensagem_erro']) ?>
    </div>
    <?php unset($_SESSION['mensagem_erro']); ?>
<?php endif; ?>

<div class="card mb-6">
    <div class="card-body">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Filtro de Acervo -->
            <?php if (!empty($bibliotecaRedeHabilitada)): ?>
                <div class="flex-shrink-0">
                    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 p-1 text-sm">
                        <a href="/biblioteca?acervo=minha" class="btn-tab <?= ($catalogScope ?? 'minha') === 'minha' ? 'active' : '' ?>">Minha loja</a>
                        <a href="/biblioteca?acervo=rede" class="btn-tab <?= ($catalogScope ?? 'minha') === 'rede' ? 'active' : '' ?>">Rede</a>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">A rede aparece se a loja optou por compartilhar o acervo.</p>
                </div>
            <?php endif; ?>

            <!-- Botões de Ação -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="/biblioteca/meus-emprestimos" class="btn btn-secondary">Meus empréstimos</a>
                <?php if ($podeGerenciar): ?>
                    <a href="/biblioteca/emprestimos" class="btn btn-secondary-amber">Gerenciar empréstimos</a>
                    <a href="/biblioteca/adicionar" class="btn btn-primary">Novo Título</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($podeGerenciar): ?>
    <div class="card mb-6">
        <form action="/biblioteca/configurar-rede" method="POST" class="card-body">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Rede de bibliotecas</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Controle se o acervo desta loja aparece para outras lojas que adotarem o Gestor.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <input type="checkbox" name="compartilhar_acervo" value="1" class="mt-1" <?= $redeCompartilha ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">Compartilhar acervo</span>
                            <span class="block text-xs text-gray-500">Livros desta loja aparecem na aba Rede.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                        <input type="checkbox" name="permitir_emprestimo_cruzado" value="1" class="mt-1" <?= $redePermiteEmprestimo ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">Permitir solicitaÃ§Ã£o interloja</span>
                            <span class="block text-xs text-gray-500">Outra loja pode solicitar emprÃ©stimo; a biblioteca decide o atendimento.</span>
                        </span>
                    </label>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Salvar rede</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="alert alert-info mb-6">
    O grau recomendado é uma sugestão formativa para orientar a leitura. Ele não bloqueia nem restringe automaticamente o acesso ao livro.
</div>

<!-- Lista de Itens (Cards para Mobile) -->
<div class="space-y-4 md:hidden">
    <?php if (empty($lista)): ?>
        <div class="card-placeholder">Nenhum título cadastrado no acervo.</div>
    <?php else: ?>
        <?php foreach ($lista as $item): ?>
            <div class="card">
                <div class="p-4">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <?php if (!empty($item['capa_url'])): ?>
                                <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa" class="h-24 w-16 rounded object-cover border border-gray-200 dark:border-gray-700">
                            <?php else: ?>
                                <div class="h-24 w-16 rounded bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-700"></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-2">
                                <h3 class="font-bold text-base flex-1 leading-tight"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></h3>
                                <?php if ((bool) ($item['disponivel'] ?? false)): ?>
                                    <span class="badge-status success">Disponível</span>
                                <?php else: ?>
                                    <span class="badge-status danger">Indisponível</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= htmlspecialchars((string) ($item['autor'] ?? '-')) ?></p>
                            <?php if (($catalogScope ?? 'minha') === 'rede' && !empty($item['loja_nome'])): ?>
                                <p class="text-xs text-gray-500 mt-1">Loja: <?= htmlspecialchars((string) ($item['loja_nome'] ?? '')) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-2">Código: <span class="font-mono"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></span></p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-col gap-2">
                        <?php $detalhesHref = '/biblioteca/detalhes?id=' . (int) ($item['id'] ?? 0) . ((($catalogScope ?? 'minha') === 'rede') ? '&loja_id=' . (int) ($item['loja_id'] ?? 0) : ''); ?>
                        <a href="<?= htmlspecialchars($detalhesHref) ?>" class="btn btn-primary w-full">Ver Detalhes</a>
                        <div class="flex gap-2">
                            <?php if ($podeGerenciar): ?>
                                <a href="/biblioteca/editar?id=<?= (int) ($item['id'] ?? 0) ?>" class="btn btn-secondary w-full">Editar</a>
                            <?php endif; ?>
                            <?php if ($podeClassificar): ?>
                                <button onclick="abrirModalClassificacao(<?= (int) ($item['id'] ?? 0) ?>, '<?= addslashes((string) ($item['titulo'] ?? '')) ?>', '<?= addslashes((string) ($item['grau_recomendado'] ?? 'Livre')) ?>', '<?= addslashes((string) ($item['nota_instrucao'] ?? '')) ?>')" class="btn btn-secondary-purple w-full">Classificar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Tabela de Itens (Desktop) -->
<div class="card hidden md:block">
    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th class="w-20">Capa</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <?php if (($catalogScope ?? 'minha') === 'rede'): ?><th>Loja</th><?php endif; ?>
                    <th>Status</th>
                    <th>Grau recomendado</th>
                    <th class="w-40">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lista)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-500">Nenhum título cadastrado no acervo.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lista as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['capa_url'])): ?>
                                    <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa" class="h-16 w-12 object-cover rounded border border-gray-200 dark:border-gray-700">
                                <?php else: ?>
                                    <div class="h-16 w-12 rounded bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-700"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></div>
                                <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></div>
                            </td>
                            <td><?= htmlspecialchars((string) ($item['autor'] ?? '-')) ?></td>
                            <?php if (($catalogScope ?? 'minha') === 'rede'): ?>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars((string) ($item['loja_nome'] ?? '')) ?></div>
                                    <div class="text-xs text-gray-500">Nº <?= htmlspecialchars((string) ($item['numero_loja'] ?? '')) ?></div>
                                </td>
                            <?php endif; ?>
                            <td>
                                <?php if ((bool) ($item['disponivel'] ?? false)): ?>
                                    <span class="badge-status success">Disponível (<?= (int) ($item['quantidade_disponivel'] ?? 0) ?>)</span>
                                <?php else: ?>
                                    <span class="badge-status danger">Indisponível</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-grau"><?= $formatGrau((string) ($item['grau_recomendado'] ?? '')) ?></span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <?php $detalhesHref = '/biblioteca/detalhes?id=' . (int) ($item['id'] ?? 0) . ((($catalogScope ?? 'minha') === 'rede') ? '&loja_id=' . (int) ($item['loja_id'] ?? 0) : ''); ?>
                                    <a href="<?= htmlspecialchars($detalhesHref) ?>" class="btn-action">Detalhes</a>
                                    <?php if ($podeGerenciar): ?>
                                        <a href="/biblioteca/editar?id=<?= (int) ($item['id'] ?? 0) ?>" class="btn-action">Editar</a>
                                    <?php endif; ?>
                                    <?php if ($podeClassificar): ?>
                                        <button onclick="abrirModalClassificacao(<?= (int) ($item['id'] ?? 0) ?>, '<?= addslashes((string) ($item['titulo'] ?? '')) ?>', '<?= addslashes((string) ($item['grau_recomendado'] ?? 'Livre')) ?>', '<?= addslashes((string) ($item['nota_instrucao'] ?? '')) ?>')" class="btn-action">Classificar</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Classificação -->
<div id="modalClassificacao" class="modal-container hidden">
    <div class="modal-content">
        <form action="/biblioteca/classificar" method="POST">
            <div class="modal-header">
                <h3 class="modal-title">Definir grau recomendado</h3>
                <p id="modal-livro-titulo" class="modal-description"></p>
            </div>
            <div class="modal-body">
                <input type="hidden" name="livro_id" id="modal-livro-id">
                <div>
                    <label for="modal-grau" class="form-label">Grau recomendado</label>
                    <select name="grau_recomendado" id="modal-grau" class="form-select">
                        <option value="Livre">Livre</option>
                        <option value="Aprendiz">Aprendiz</option>
                        <option value="Companheiro">Companheiro</option>
                        <option value="Mestre">Mestre</option>
                    </select>
                </div>
                <div>
                    <label for="modal-nota" class="form-label">Nota de Instrução</label>
                    <textarea name="nota_instrucao" id="modal-nota" rows="3" class="form-textarea"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary-purple">Salvar recomendação</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('modalClassificacao');
    function abrirModalClassificacao(id, titulo, grauAtual, notaAtual) {
        document.getElementById('modal-livro-id').value = id;
        document.getElementById('modal-livro-titulo').innerText = titulo;
        document.getElementById('modal-grau').value = grauAtual || 'Livre';
        document.getElementById('modal-nota').value = notaAtual || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function fecharModal() {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
</script>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>



