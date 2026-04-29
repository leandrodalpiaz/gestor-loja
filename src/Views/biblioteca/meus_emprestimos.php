<?php
declare(strict_types=1);

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E HELPERS
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
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Meus EmprÃ©stimos';
$appShellDescription = 'Acompanhe seu histÃ³rico de leitura, prazos e situaÃ§Ã£o de devoluÃ§Ã£o.';
$appShellActiveHref = '/biblioteca/meus-emprestimos';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="mb-6 flex justify-end">
    <a href="/biblioteca" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
        Voltar ao CatÃ¡logo
    </a>
</div>

<!-- Lista de EmprÃ©stimos (Cards para Mobile) -->
<div class="space-y-4 md:hidden">
    <?php if (empty($emprestimos)): ?>
        <div class="card-placeholder">Nenhum emprÃ©stimo registrado em seu nome.</div>
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
                            <span class="text-gray-500">Data do EmprÃ©stimo:</span>
                            <strong><?= $formatDate((string) ($emp['data_emprestimo'] ?? '')) ?></strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">DevoluÃ§Ã£o Prevista:</span>
                            <strong><?= $formatDate((string) ($emp['data_devolucao_prevista'] ?? '')) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Tabela de EmprÃ©stimos (Desktop) -->
<div class="card hidden md:block">
    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th>Livro</th>
                    <th>EmprÃ©stimo</th>
                    <th>DevoluÃ§Ã£o Prevista</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emprestimos)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-10 text-gray-500">Nenhum emprÃ©stimo registrado em seu nome.</td>
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


