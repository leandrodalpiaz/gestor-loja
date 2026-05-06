<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$formatDate = static fn($dateStr) => !empty($dateStr) ? (new DateTime($dateStr))->format('d/m/Y') : '-';

function getStatusInfo(string $status): array
{
    return match ($status) {
        'atrasado' => ['label' => 'Atrasado', 'badge' => 'badge-status danger'],
        'pendente' => ['label' => 'Pendente', 'badge' => 'badge-status warning'],
        'devolvido' => ['label' => 'Devolvido', 'badge' => 'badge-status success'],
        'solicitado' => ['label' => 'Solicitado', 'badge' => 'badge-status warning'],
        'aprovado' => ['label' => 'Aprovado', 'badge' => 'badge-status success'],
        'negado' => ['label' => 'Negado', 'badge' => 'badge-status danger'],
        'cancelado' => ['label' => 'Cancelado', 'badge' => 'badge-status neutral'],
        default => ['label' => ucfirst($status), 'badge' => 'badge-status neutral'],
    };
}

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Controle de Empréstimos';
$appShellDescription = 'Gerencie os empréstimos pendentes, atrasados e registre as devoluções.';
$appShellActiveHref = '/biblioteca/emprestimos';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

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

<div class="mb-6 flex justify-end">
    <a href="/biblioteca" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
        Voltar ao Catálogo
    </a>
</div>

<!-- Lista de Empréstimos (Cards para Mobile) -->
<div class="card mb-6">
    <div class="card-header">
        <div>
            <h2 class="card-title">Pedidos interloja recebidos</h2>
            <p class="card-description">Solicitações de outras lojas para livros compartilhados por esta biblioteca.</p>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($pedidosInterloja ?? [])): ?>
            <div class="card-placeholder">Nenhum pedido interloja recebido.</div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($pedidosInterloja as $pedido): ?>
                    <?php $statusInfo = getStatusInfo((string) ($pedido['status'] ?? '-')); ?>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars((string) ($pedido['titulo'] ?? '-')) ?></h3>
                                    <span class="<?= $statusInfo['badge'] ?>"><?= $statusInfo['label'] ?></span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars((string) ($pedido['obreiro_nome'] ?? '-')) ?>
                                    - Loja <?= htmlspecialchars((string) ($pedido['loja_destino_numero'] ?? '')) ?>
                                    <?= htmlspecialchars((string) ($pedido['loja_destino_sigla'] ?? '')) ?>
                                    <?= htmlspecialchars((string) ($pedido['loja_destino_nome'] ?? '')) ?>
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Codigo <?= htmlspecialchars((string) ($pedido['codigo_acervo'] ?? '')) ?>
                                    - Disponivel: <?= (int) ($pedido['quantidade_disponivel'] ?? 0) ?>
                                    - Pedido em <?= $formatDate((string) ($pedido['solicitado_em'] ?? '')) ?>
                                </p>
                            </div>
                            <?php if (($pedido['status'] ?? '') === 'solicitado'): ?>
                                <div class="flex flex-wrap gap-2 md:justify-end">
                                    <form action="/biblioteca/interloja/decidir" method="POST">
                                        <input type="hidden" name="pedido_id" value="<?= (int) ($pedido['id'] ?? 0) ?>">
                                        <input type="hidden" name="decisao" value="aprovado">
                                        <button type="submit" class="btn btn-primary">Aprovar</button>
                                    </form>
                                    <form action="/biblioteca/interloja/decidir" method="POST">
                                        <input type="hidden" name="pedido_id" value="<?= (int) ($pedido['id'] ?? 0) ?>">
                                        <input type="hidden" name="decisao" value="negado">
                                        <button type="submit" class="btn btn-secondary">Negar</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="space-y-4 md:hidden">
    <?php if (empty($emprestimos)): ?>
        <div class="card-placeholder">Nenhum empréstimo pendente ou atrasado.</div>
    <?php else: ?>
        <?php foreach ($emprestimos as $emp): ?>
            <?php $statusInfo = getStatusInfo((string) ($emp['status'] ?? '-')); ?>
            <div class="card">
                <div class="p-4">
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-base leading-tight"><?= htmlspecialchars((string) ($emp['titulo'] ?? $emp['acervo_id'] ?? '-')) ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= htmlspecialchars((string) ($emp['obreiro_nome'] ?? $emp['obreiro_id'] ?? '-')) ?></p>
                        </div>
                        <span class="<?= $statusInfo['badge'] ?>"><?= $statusInfo['label'] ?></span>
                    </div>
                    <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Data do Empréstimo:</span>
                            <strong><?= $formatDate((string) ($emp['data_emprestimo'] ?? '')) ?></strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Devolução Prevista:</span>
                            <strong><?= $formatDate((string) ($emp['data_devolucao_prevista'] ?? '')) ?></strong>
                        </div>
                    </div>
                    <?php if (($emp['status'] ?? '') === 'pendente' || ($emp['status'] ?? '') === 'atrasado'): ?>
                        <div class="mt-4">
                            <form action="/biblioteca/devolver" method="POST">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($emp['id'] ?? '')) ?>">
                                <button type="submit" class="btn btn-primary w-full">Registrar Devolução</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Tabela de Empréstimos (Desktop) -->
<div class="card hidden md:block">
    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th>Livro</th>
                    <th>Obreiro</th>
                    <th>Empréstimo</th>
                    <th>Devolução Prevista</th>
                    <th>Status</th>
                    <th class="w-48">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emprestimos)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">Nenhum empréstimo pendente ou atrasado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emprestimos as $emp): ?>
                        <?php $statusInfo = getStatusInfo((string) ($emp['status'] ?? '-')); ?>
                        <tr>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars((string) ($emp['titulo'] ?? $emp['acervo_id'] ?? '-')) ?></div>
                                <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></div>
                            </td>
                            <td><?= htmlspecialchars((string) ($emp['obreiro_nome'] ?? $emp['obreiro_id'] ?? '-')) ?></td>
                            <td><?= $formatDate((string) ($emp['data_emprestimo'] ?? '')) ?></td>
                            <td><?= $formatDate((string) ($emp['data_devolucao_prevista'] ?? '')) ?></td>
                            <td><span class="<?= $statusInfo['badge'] ?>"><?= $statusInfo['label'] ?></span></td>
                            <td class="text-right">
                                <?php if (($emp['status'] ?? '') === 'pendente' || ($emp['status'] ?? '') === 'atrasado'): ?>
                                    <form action="/biblioteca/devolver" method="POST">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($emp['id'] ?? '')) ?>">
                                        <button type="submit" class="btn btn-primary">Registrar Devolução</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


