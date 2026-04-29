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

<div class="mb-6 flex justify-end">
    <a href="/biblioteca" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
        Voltar ao Catálogo
    </a>
</div>

<!-- Lista de Empréstimos (Cards para Mobile) -->
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


