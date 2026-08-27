<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$isTestSession = isset($_SESSION['usuario_id']) && (string) $_SESSION['usuario_id'] === '0';

$pwaPageTitle = 'Sessões';
$pwaShowBackButton = true;
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="px-4 py-4 space-y-4">
    <?php if ($mensagemSucesso): ?>
        <div class="pwa-alert-success"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <?php if (empty($sessoesFuturas)): ?>
        <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-2xl border border-dashed border-white/10 select-none">
            <div class="text-sm font-semibold text-slate-300">Nenhuma sessão futura</div>
            <p class="mt-1">Não há sessões publicadas para a Loja no momento.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
        <?php foreach ($sessoesFuturas as $index => $sessao): ?>
            <?php
            $resposta = $respostas[$sessao['id']] ?? null;
            $statusConfirmacao = (string) ($resposta['status_confirmacao'] ?? '');
            $participaraAgape = (bool) ($resposta['participara_agape'] ?? false);
            $observacaoAtual = trim((string) ($resposta['observacao'] ?? ''));

            $statusBadgeClass = match ($statusConfirmacao) {
                'confirmado' => 'pwa-badge-success',
                'ausente' => 'pwa-badge-danger',
                default => 'pwa-badge-muted',
            };
            $statusBadgeLabel = match ($statusConfirmacao) {
                'confirmado' => $participaraAgape ? 'Confirmado (com ágape)' : 'Confirmado',
                'ausente' => 'Ausente',
                default => 'Sem resposta',
            };
            
            $isOpen = ($index === 0) ? 'open' : '';
            ?>
            
            <details class="group pwa-card p-0 overflow-hidden border border-white/5" <?= $isOpen ?>>
                <summary class="cursor-pointer p-4 list-none flex items-start justify-between gap-3 select-none active:bg-slate-800/30 transition-colors">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold leading-snug text-slate-100"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></h2>
                        <p class="mt-1.5 text-xs text-slate-400">
                            <?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?>
                            <?php if (!empty($sessao['tipo_sessao'])): ?> · <?= htmlspecialchars((string) $sessao['tipo_sessao']) ?><?php endif; ?>
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        <span class="pwa-badge <?= $statusBadgeClass ?> font-bold shrink-0 select-none">
                            <?= htmlspecialchars($statusBadgeLabel) ?>
                        </span>
                        <div class="transition-transform group-open:rotate-180 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </summary>
                
                <div class="p-4 pt-0 space-y-4 border-t border-white/5">
                    <div class="pt-3.5 space-y-3">
                        <?php if (!empty($sessao['resumo_publico'])): ?>
                            <div class="rounded-xl p-3 text-xs whitespace-pre-line bg-slate-950 border border-white/5 text-slate-300">
                                <?= htmlspecialchars((string) $sessao['resumo_publico']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sessao['ordem_dia'])): ?>
                            <details class="rounded-xl p-3 bg-slate-950 border border-white/5">
                                <summary class="cursor-pointer text-xs font-semibold text-slate-200 select-none">Ordem do dia</summary>
                                <div class="mt-2 text-xs whitespace-pre-line text-slate-300"><?= htmlspecialchars((string) $sessao['ordem_dia']) ?></div>
                            </details>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="/pwa/sessoes/atualizar" class="space-y-4 pt-2">
                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">

                        <div class="space-y-2 select-none">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">1. Confirmação de Presença</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button name="acao" value="confirmar_agape" class="pwa-btn-primary text-xs font-bold py-2.5">
                                    Com Ágape
                                </button>
                                <button name="acao" value="confirmar_sem_agape" class="pwa-btn-secondary text-xs font-bold py-2.5">
                                    Sem Ágape
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">2. Justificar Ausência</p>
                            <div class="pwa-card p-3 border border-white/5 space-y-3 bg-slate-950/40">
                                <textarea name="observacao" rows="2" class="pwa-textarea text-xs" placeholder="Motivo: compromisso, viagem, saúde..."><?= htmlspecialchars($observacaoAtual) ?></textarea>
                                <button name="acao" value="ausencia" class="pwa-btn-secondary text-xs font-bold py-2 border-red-500/20 bg-red-500/10 hover:bg-red-500/20 text-red-300 select-none w-full">
                                    Justificar Ausência
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-2 select-none">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">3. Limpar Resposta</p>
                            <button name="acao" value="cancelar" class="pwa-btn-secondary text-xs font-bold py-2 w-full">
                                Limpar minha resposta
                            </button>
                        </div>

                        <?php if ($isTestSession): ?>
                            <div class="text-[10px] text-slate-500">Modo teste: algumas ações podem ser bloqueadas pela configuração da Loja.</div>
                        <?php endif; ?>
                    </form>
                </div>
            </details>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center text-[10px] text-slate-500 mt-4 select-none">
        O Telegram agora é um canal de notificação. Use este app para confirmar sua presença.
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
