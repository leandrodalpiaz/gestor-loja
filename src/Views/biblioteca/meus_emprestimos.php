<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$formatDate = static fn($dateStr) => !empty($dateStr) ? (new DateTime($dateStr))->format('d/m/Y') : '-';

function getStatusInfo(string $status): array
{
    return match ($status) {
        'atrasado' => ['label' => 'Atrasado', 'badge' => 'badge-status-danger'],
        'pendente' => ['label' => 'Pendente', 'badge' => 'badge-status-warning'],
        'devolvido' => ['label' => 'Devolvido', 'badge' => 'badge-status-success'],
        'solicitado' => ['label' => 'Solicitado', 'badge' => 'badge-status-warning'],
        'aprovado' => ['label' => 'Aprovado', 'badge' => 'badge-status-success'],
        'negado' => ['label' => 'Negado', 'badge' => 'badge-status-danger'],
        'cancelado' => ['label' => 'Cancelado', 'badge' => 'badge-status-secondary'],
        default => ['label' => ucfirst($status), 'badge' => 'badge-status-secondary'],
    };
}

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Meus Empréstimos';
$appShellDescription = 'Acompanhe suas solicitações de leitura, prazos de devolução e histórico de leituras concluídas.';
$appShellActiveHref = '/biblioteca/meus-emprestimos';
$appShellActions = [
    ['label' => 'Catálogo', 'href' => '/biblioteca'],
    ['label' => 'Painel', 'href' => '/dashboard'],
];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Botão Voltar -->
<div class="mb-6 flex justify-between">
    <a href="/biblioteca" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-xs font-semibold inline-flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Voltar ao Catálogo
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Empréstimos Locais (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6">
                <h2 class="card-title text-white">Minhas Leituras & Empréstimos</h2>
                <p class="card-subtitle mt-0.5">Físicos obtidos no templo local.</p>
            </div>
            
            <div class="card-body p-6 space-y-4">
                <?php if (empty($emprestimos)): ?>
                    <p class="text-center text-slate-400 py-8 text-xs">Você não possui nenhum empréstimo ativo ou concluído registrado.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($emprestimos as $emp): 
                            $statusInfo = getStatusInfo(strtolower(trim((string) ($emp['status'] ?? ''))));
                        ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.01] p-4 flex flex-col justify-between hover:bg-white/[0.02] transition">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <h3 class="font-bold text-white text-sm leading-tight line-clamp-2"><?= htmlspecialchars((string) ($emp['titulo'] ?? '-')) ?></h3>
                                        <span class="badge-status <?= $statusInfo['badge'] ?> text-[10px] shrink-0"><?= $statusInfo['label'] ?></span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-mono">Cód: <?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></p>
                                </div>
                                
                                <div class="mt-4 pt-3 border-t border-white/5 grid grid-cols-2 gap-2 text-xs text-slate-400">
                                    <div>
                                        <p class="text-[9px] uppercase tracking-wider text-slate-500">Retirado em</p>
                                        <p class="font-semibold text-slate-300 mt-0.5"><?= $formatDate((string) ($emp['data_emprestimo'] ?? '')) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] uppercase tracking-wider text-slate-500">Devolução Prevista</p>
                                        <p class="font-semibold text-white mt-0.5"><?= $formatDate((string) ($emp['data_devolucao_prevista'] ?? '')) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pedidos Interloja (1/3) -->
    <div class="space-y-6">
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Solicitações Interloja</h2>
                <p class="card-subtitle mt-0.5">Livros solicitados de outras lojas da rede.</p>
            </div>
            
            <div class="card-body space-y-4">
                <?php if (empty($pedidosInterloja ?? [])): ?>
                    <p class="text-center text-slate-400 py-6 text-xs">Nenhuma solicitação de rede pendente.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($pedidosInterloja as $pedido): 
                            $statusInfo = getStatusInfo(strtolower(trim((string) ($pedido['status'] ?? ''))));
                        ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-xs space-y-2">
                                <div class="flex justify-between items-start gap-2">
                                    <p class="font-bold text-white truncate max-w-[12rem]"><?= htmlspecialchars((string) ($pedido['titulo'] ?? '-')) ?></p>
                                    <span class="badge-status <?= $statusInfo['badge'] ?> text-[9px] shrink-0"><?= $statusInfo['label'] ?></span>
                                </div>
                                <div class="text-[10px] text-slate-400 space-y-1">
                                    <p>Loja de Origem: <span class="text-white font-medium"><?= htmlspecialchars((string) ($pedido['loja_origem_nome'] ?? 'Loja Coirmã')) ?></span></p>
                                    <p>Cód: <span class="font-mono text-white"><?= htmlspecialchars((string) ($pedido['codigo_acervo'] ?? '')) ?></span></p>
                                    <p>Data Pedido: <span class="text-white"><?= $formatDate((string) ($pedido['solicitado_em'] ?? '')) ?></span></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
