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
$appShellTitle = 'Painel do Bibliotecário';
$appShellDescription = 'Controle a fila de solicitações de empréstimo locais e interlojas, e gerencie devoluções de livros.';
$appShellActiveHref = '/biblioteca/emprestimos';
$appShellActions = [
    ['label' => 'Catálogo', 'href' => '/biblioteca'],
    ['label' => 'Painel', 'href' => '/dashboard'],
];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
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

<!-- Voltar -->
<div class="mb-6 flex justify-between">
    <a href="/biblioteca" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-xs font-semibold inline-flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Voltar ao Catálogo
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Fila de Empréstimos Locais (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6">
                <h2 class="card-title text-white">Fila de Empréstimos Locais</h2>
                <p class="card-subtitle mt-0.5">Leitores da Loja com empréstimos pendentes ou em atraso.</p>
            </div>
            
            <div class="card-body p-6 space-y-4">
                <?php if (empty($emprestimos)): ?>
                    <p class="text-center text-slate-400 py-10 text-sm">Nenhum empréstimo pendente ou atrasado registrado.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($emprestimos as $emp): 
                            $statusInfo = getStatusInfo((string) ($emp['status'] ?? '-'));
                            $isPendente = in_array(($emp['status'] ?? ''), ['pendente', 'atrasado'], true);
                        ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.01] p-5 flex flex-col justify-between hover:bg-white/[0.02] transition">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <h3 class="font-bold text-white text-sm leading-tight"><?= htmlspecialchars((string) ($emp['titulo'] ?? $emp['acervo_id'] ?? '-')) ?></h3>
                                            <p class="text-xs text-slate-400 mt-1">Leitor: <strong class="text-white"><?= htmlspecialchars((string) ($emp['obreiro_nome'] ?? $emp['obreiro_id'] ?? '-')) ?></strong></p>
                                        </div>
                                        <span class="badge-status <?= $statusInfo['badge'] ?> text-[10px] shrink-0"><?= $statusInfo['label'] ?></span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-mono">Cód: <?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></p>
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

                                <?php if ($isPendente): ?>
                                    <div class="mt-4 pt-2">
                                        <form action="/biblioteca/devolver" method="POST" onsubmit="return confirm('Confirmar a devolução física deste exemplar ao acervo?');">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($emp['id'] ?? '')) ?>">
                                            <button type="submit" class="btn btn-primary w-full py-2 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                                Registrar Devolução
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pedidos Interlojas Recebidos (1/3) -->
    <div class="space-y-6">
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Pedidos Interloja Recebidos</h2>
                <p class="card-subtitle mt-0.5">Requisições externas de outras Lojas da rede.</p>
            </div>
            
            <div class="card-body space-y-4">
                <?php if (empty($pedidosInterloja ?? [])): ?>
                    <p class="text-center text-slate-400 py-6 text-xs">Nenhum pedido de intercâmbio recebido.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pedidosInterloja as $pedido): 
                            $statusInfo = getStatusInfo((string) ($pedido['status'] ?? '-'));
                            $isSolicitado = ($pedido['status'] ?? '') === 'solicitado';
                        ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-xs space-y-3">
                                <div class="flex justify-between items-start gap-2">
                                    <div>
                                        <p class="font-bold text-white"><?= htmlspecialchars((string) ($pedido['titulo'] ?? '-')) ?></p>
                                        <p class="text-[10px] text-slate-400 mt-1">Loja Destinatária: <span class="text-white font-medium"><?= htmlspecialchars((string) ($pedido['loja_destino_nome'] ?? 'Loja Coirmã')) ?></span></p>
                                        <p class="text-[10px] text-slate-400">Leitor: <span class="text-white"><?= htmlspecialchars((string) ($pedido['obreiro_nome'] ?? '-')) ?></span></p>
                                    </div>
                                    <span class="badge-status <?= $statusInfo['badge'] ?> text-[9px] shrink-0"><?= $statusInfo['label'] ?></span>
                                </div>
                                
                                <div class="text-[9px] text-slate-500 font-mono space-y-0.5">
                                    <p>Cód: <?= htmlspecialchars((string) ($pedido['codigo_acervo'] ?? '')) ?></p>
                                    <p>Estoque local: <?= (int) ($pedido['quantidade_disponivel'] ?? 0) ?> ex.</p>
                                    <p>Pedido em: <?= $formatDate((string) ($pedido['solicitado_em'] ?? '')) ?></p>
                                </div>

                                <?php if ($isSolicitado): ?>
                                    <div class="flex gap-2 pt-2">
                                        <form action="/biblioteca/interloja/decidir" method="POST" class="flex-grow">
                                            <input type="hidden" name="pedido_id" value="<?= (int) ($pedido['id'] ?? 0) ?>">
                                            <input type="hidden" name="decisao" value="aprovado">
                                            <button type="submit" class="btn btn-success text-[10px] font-bold py-1.5 w-full">Aprovar</button>
                                        </form>
                                        <form action="/biblioteca/interloja/decidir" method="POST" class="flex-grow">
                                            <input type="hidden" name="pedido_id" value="<?= (int) ($pedido['id'] ?? 0) ?>">
                                            <input type="hidden" name="decisao" value="negado">
                                            <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1.5 w-full text-[10px]">Negar</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
