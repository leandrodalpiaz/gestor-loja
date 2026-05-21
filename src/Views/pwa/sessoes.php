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

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($mensagemSucesso): ?>
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(52,211,153,0.15);color:#6ee7b7;border:1px solid rgba(52,211,153,0.25)">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25)">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($sessoesFuturas)): ?>
        <div class="p-5 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-radius:1rem;">
            <div class="text-lg font-semibold" style="color:#f1f5f9">Nenhuma sessão futura</div>
            <p class="mt-1 text-sm" style="color:#94a3b8">Não há sessões publicadas para a Loja no momento.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
        <?php foreach ($sessoesFuturas as $index => $sessao): ?>
            <?php
            $resposta = $respostas[$sessao['id']] ?? null;
            $statusConfirmacao = (string) ($resposta['status_confirmacao'] ?? '');
            $participaraAgape = (bool) ($resposta['participara_agape'] ?? false);
            $observacaoAtual = trim((string) ($resposta['observacao'] ?? ''));

            $statusBadgeStyle = match ($statusConfirmacao) {
                'confirmado' => $participaraAgape
                    ? 'background:rgba(52,211,153,0.15);color:#6ee7b7;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;'
                    : 'background:rgba(52,211,153,0.15);color:#6ee7b7;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;',
                'ausente' => 'background:rgba(248,113,113,0.12);color:#fca5a5;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;',
                default => 'background:rgba(148,163,184,0.15);color:#94a3b8;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;',
            };
            $statusBadgeLabel = match ($statusConfirmacao) {
                'confirmado' => $participaraAgape ? 'Confirmado (com ágape)' : 'Confirmado',
                'ausente' => 'Ausente',
                default => 'Sem resposta',
            };
            
            // Primeira sessão aparece expandida, as demais aparecem fechadas
            $isOpen = ($index === 0) ? 'open' : '';
            ?>
            
            <details class="group rounded-2xl shadow-sm overflow-hidden" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-radius:1rem;" <?= $isOpen ?>>
                <summary class="cursor-pointer p-5 list-none flex items-start justify-between gap-3 transition-colors hover:bg-white/5">
                    <div>
                        <h2 class="text-xl font-bold leading-tight" style="color:#f1f5f9"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></h2>
                        <p class="mt-1 text-sm" style="color:#94a3b8">
                            <?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?>
                            <?php if (!empty($sessao['tipo_sessao'])): ?> · <?= htmlspecialchars((string) $sessao['tipo_sessao']) ?><?php endif; ?>
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="inline-flex items-center uppercase tracking-wider" style="<?= htmlspecialchars($statusBadgeStyle) ?>">
                            <?= htmlspecialchars($statusBadgeLabel) ?>
                        </span>
                        <div class="transition-transform group-open:rotate-180" style="color:#94a3b8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </summary>
                
                <div class="p-5 pt-0 mt-2 space-y-4" style="border-top:1px solid rgba(255,255,255,0.09)">
                    <div class="pt-4">
                        <?php if (!empty($sessao['resumo_publico'])): ?>
                            <div class="rounded-lg p-3 text-sm whitespace-pre-line" style="background:rgba(255,255,255,0.03);color:#e2e8f0">
                                <?= htmlspecialchars((string) $sessao['resumo_publico']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sessao['ordem_dia'])): ?>
                            <details class="rounded-lg p-3 mt-3" style="background:rgba(255,255,255,0.03)">
                                <summary class="cursor-pointer text-sm font-semibold" style="color:#f1f5f9">Ordem do dia</summary>
                                <div class="mt-2 text-sm whitespace-pre-line" style="color:#e2e8f0"><?= htmlspecialchars((string) $sessao['ordem_dia']) ?></div>
                            </details>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="/pwa/sessoes/atualizar" class="space-y-4 pt-2">
                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">

                        <div class="space-y-3">
                            <p class="text-sm font-semibold" style="color:#f1f5f9">1. Confirmação de Presença</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button name="acao" value="confirmar_agape" class="w-full rounded-lg px-4 py-3 text-sm font-semibold transition hover:opacity-90" style="background:#1e3a5f;color:#f1f5f9">
                                    Confirmar com Ágape
                                </button>
                                <button name="acao" value="confirmar_sem_agape" class="w-full rounded-lg px-4 py-3 text-sm font-semibold transition hover:bg-white/10" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);color:#f1f5f9">
                                    Confirmar sem Ágape
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-sm font-semibold" style="color:#f1f5f9">2. Justificar Ausência</p>
                            <div class="rounded-lg p-3 space-y-3" style="border:1px solid rgba(255,255,255,0.09)">
                                <textarea name="observacao" rows="2" class="w-full focus:outline-none focus:ring-1 focus:ring-white/20" placeholder="Motivo: compromisso, viagem, saúde..." style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;"><?= htmlspecialchars($observacaoAtual) ?></textarea>
                                <button name="acao" value="ausencia" class="w-full rounded-lg px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700" style="background:rgba(248,113,113,0.20)">
                                    Informar Ausência
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <p class="text-sm font-semibold" style="color:#f1f5f9">3. Limpar Resposta</p>
                            <button name="acao" value="cancelar" class="w-full rounded-lg px-4 py-3 text-sm font-semibold transition hover:border-rose-500 hover:text-rose-400" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);color:#94a3b8">
                                Limpar minha resposta
                            </button>
                        </div>

                        <?php if ($isTestSession): ?>
                            <div class="text-xs" style="color:#94a3b8">Modo teste: algumas ações podem ser bloqueadas pela configuração da Loja.</div>
                        <?php endif; ?>
                    </form>
                </div>
            </details>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center text-xs" style="color:#94a3b8">
        O Telegram agora é um canal de notificação. Use este app para confirmar sua presença.
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
