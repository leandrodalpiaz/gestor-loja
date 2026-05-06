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
$appShellTitle = 'Meus Empréstimos';
$appShellDescription = 'Acompanhe seu histórico de leitura, prazos e situação de devolução.';
$appShellActiveHref = '/biblioteca/meus-emprestimos';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

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
            <h2 class="card-title">Solicitacoes interloja</h2>
            <p class="card-description">Pedidos feitos para acervos compartilhados por outras lojas.</p>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($pedidosInterloja ?? [])): ?>
            <div class="card-placeholder">Nenhuma solicitacao interloja registrada.</div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($pedidosInterloja as $pedido): ?>
                    <?php $statusInfo = getStatusInfo(strtolower(trim((string) ($pedido['status'] ?? '')))); ?>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars((string) ($pedido['titulo'] ?? '-')) ?></h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Loja <?= htmlspecialchars((string) ($pedido['loja_origem_numero'] ?? '')) ?>
                                    <?= htmlspecialchars((string) ($pedido['loja_origem_sigla'] ?? '')) ?>
                                    <?= htmlspecialchars((string) ($pedido['loja_origem_nome'] ?? '')) ?>
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    Codigo <?= htmlspecialchars((string) ($pedido['codigo_acervo'] ?? '')) ?>
                                    - Solicitado em <?= $formatDate((string) ($pedido['solicitado_em'] ?? '')) ?>
                                </p>
                            </div>
                            <span class="<?= $statusInfo['badge'] ?>"><?= $statusInfo['label'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="space-y-4 md:hidden">
    <?php if (empty($emprestimos)): ?>
        <div class="card-placeholder">Nenhum empréstimo registrado em seu nome.</div>
    <?php else: ?>
        <?php foreach ($emprestimos as $emp): ?>
            <?php $statusInfo = getStatusInfo(strtolower(trim((string) ($emp['status'] ?? '')))); ?>
            <div class="card">
                <div class="p-4">
                    <div class="flex justify-between items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-base leading-tight"><?= htmlspecialchars((string) ($emp['titulo'] ?? '-')) ?></h3>
                            <p class="text-xs text-gray-500 font-mono mt-1"><?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></p>
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
                    <th>Empréstimo</th>
                    <th>Devolução Prevista</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emprestimos)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-10 text-gray-500">Nenhum empréstimo registrado em seu nome.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emprestimos as $emp): ?>
                        <?php $statusInfo = getStatusInfo(strtolower(trim((string) ($emp['status'] ?? '')))); ?>
                        <tr>
                            <td>
                                <div class="font-bold"><?= htmlspecialchars((string) ($emp['titulo'] ?? '-')) ?></div>
                                <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></div>
                            </td>
                            <td><?= $formatDate((string) ($emp['data_emprestimo'] ?? '')) ?></td>
                            <td><?= $formatDate((string) ($emp['data_devolucao_prevista'] ?? '')) ?></td>
                            <td><span class="<?= $statusInfo['badge'] ?>"><?= $statusInfo['label'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


