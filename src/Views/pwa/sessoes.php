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
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($sessoesFuturas)): ?>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 text-center">
            <div class="text-lg font-semibold text-erpNavy">Nenhuma sessão futura</div>
            <p class="mt-1 text-sm text-erpMuted">Não há sessões publicadas para a Loja no momento.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
        <?php foreach ($sessoesFuturas as $index => $sessao): ?>
            <?php
            $resposta = $respostas[$sessao['id']] ?? null;
            $statusConfirmacao = (string) ($resposta['status_confirmacao'] ?? '');
            $participaraAgape = (bool) ($resposta['participara_agape'] ?? false);
            $observacaoAtual = trim((string) ($resposta['observacao'] ?? ''));

            $statusBadge = match ($statusConfirmacao) {
                'confirmado' => $participaraAgape ? ['Confirmado (com ágape)', 'bg-emerald-100 text-emerald-800 border-emerald-200'] : ['Confirmado', 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                'ausente' => ['Ausente', 'bg-rose-100 text-rose-800 border-rose-200'],
                default => ['Sem resposta', 'bg-slate-100 text-slate-700 border-slate-200'],
            };
            
            // Primeira sessão aparece expandida, as demais aparecem fechadas
            $isOpen = ($index === 0) ? 'open' : '';
            ?>
            
            <details class="group rounded-2xl border border-erpBorder bg-erpSurface shadow-sm overflow-hidden" <?= $isOpen ?>>
                <summary class="cursor-pointer p-5 list-none flex items-start justify-between gap-3 bg-erpSurface transition-colors hover:bg-erpBg/50">
                    <div>
                        <h2 class="text-xl font-bold text-erpNavy leading-tight"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></h2>
                        <p class="mt-1 text-sm text-erpMuted">
                            <?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?>
                            <?php if (!empty($sessao['tipo_sessao'])): ?> · <?= htmlspecialchars((string) $sessao['tipo_sessao']) ?><?php endif; ?>
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[0.65rem] uppercase tracking-wider font-semibold <?= htmlspecialchars($statusBadge[1]) ?>">
                            <?= htmlspecialchars($statusBadge[0]) ?>
                        </span>
                        <div class="text-erpMuted transition-transform group-open:rotate-180">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </summary>
                
                <div class="p-5 pt-0 border-t border-erpBorder mt-2 space-y-4">
                    <div class="pt-4">
                        <?php if (!empty($sessao['resumo_publico'])): ?>
                            <div class="rounded-lg bg-erpBg p-3 text-sm text-erpText whitespace-pre-line">
                                <?= htmlspecialchars((string) $sessao['resumo_publico']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sessao['ordem_dia'])): ?>
                            <details class="rounded-lg bg-erpBg p-3 mt-3">
                                <summary class="cursor-pointer text-sm font-semibold text-erpNavy">Ordem do dia</summary>
                                <div class="mt-2 text-sm text-erpText whitespace-pre-line"><?= htmlspecialchars((string) $sessao['ordem_dia']) ?></div>
                            </details>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="/pwa/sessoes/atualizar" class="space-y-4 pt-2">
                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">

                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-erpNavy">1. Confirmação de Presença</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button name="acao" value="confirmar_agape" class="w-full rounded-lg bg-erpNavy px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                                    Confirmar com Ágape
                                </button>
                                <button name="acao" value="confirmar_sem_agape" class="w-full rounded-lg border border-erpBorder bg-erpSurface px-4 py-3 text-sm font-semibold text-erpNavy transition hover:bg-erpBg">
                                    Confirmar sem Ágape
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-erpNavy">2. Justificar Ausência</p>
                            <div class="rounded-lg border border-erpBorder p-3 space-y-3">
                                <textarea name="observacao" rows="2" class="w-full rounded-lg border border-erpBorder bg-erpBg px-3 py-2 text-sm placeholder-erpMuted focus:border-erpNavy focus:outline-none" placeholder="Motivo: compromisso, viagem, saúde..."><?= htmlspecialchars($observacaoAtual) ?></textarea>
                                <button name="acao" value="ausencia" class="w-full rounded-lg bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                                    Informar Ausência
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-erpNavy">3. Limpar Resposta</p>
                            <button name="acao" value="cancelar" class="w-full rounded-lg border border-erpBorder bg-erpSurface px-4 py-3 text-sm font-semibold text-erpMuted transition hover:border-rose-500 hover:text-rose-600">
                                Limpar minha resposta
                            </button>
                        </div>

                        <?php if ($isTestSession): ?>
                            <div class="text-xs text-erpMuted">Modo teste: algumas ações podem ser bloqueadas pela configuração da Loja.</div>
                        <?php endif; ?>
                    </form>
                </div>
            </details>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center text-xs text-erpMuted">
        O Telegram agora é um canal de notificação. Use este app para confirmar sua presença.
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
