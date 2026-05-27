<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Balaústres em Votação';
$appShellDescription = 'Acompanhe votações abertas e registre votos (quando elegível).';
$appShellActiveHref = '/secretaria/votacao';
require __DIR__ . '/_sidebar.php';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-8 depth-1"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-8 depth-1"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Balaústres Em Votação -->
<div class="space-y-8">
    <?php if (empty($votacoesAbertas)): ?>
        <div class="md:col-span-2 lg:col-span-3 text-center py-24 glass-surface rounded-[40px] border-dashed">
            <div class="text-6xl mb-6 opacity-20">🗳️</div>
            <p class="text-sm text-erp-muted font-bold uppercase tracking-widest opacity-60">Nenhuma votação aberta no momento</p>
            <p class="text-xs text-erp-muted mt-4 font-medium max-w-md mx-auto">Prepare o balaústre na Secretaria e marque como apto para votação para iniciar o processo.</p>
        </div>
    <?php else: ?>
        <?php foreach ($votacoesAbertas as $balaustre): ?>
            <?php
            $balaustreId = (int) ($balaustre['id'] ?? 0);
            $elegivel = (bool) ($elegibilidadeVoto[$balaustreId] ?? false);
            ?>
            <div class="card depth-1 hover-lift overflow-hidden border-l-4 <?= $elegivel ? 'border-l-erp-success' : 'border-l-erp-gold' ?>">
                <div class="card-header border-b border-erp-border/50 p-8 flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h2 class="text-2xl font-black text-white tracking-tight"><?= htmlspecialchars($balaustre['numero_balaustre'] ?: 'Balaústre sem número') ?></h2>
                        <div class="flex items-center gap-3 mt-2">
                            <p class="text-sm font-bold text-slate-300">
                                <?= htmlspecialchars($balaustre['sessao_titulo'] ?: (($balaustre['tipo_sessao'] ?? 'Sessão') . ' - ' . ($balaustre['grau_sessao'] ?? ''))) ?>
                            </p>
                            <span class="w-1 h-1 rounded-full bg-erp-border/50"></span>
                            <span class="text-xs font-bold text-erp-muted uppercase tracking-widest">Realizada em: <?= htmlspecialchars((string) ($balaustre['data_hora_inicio'] ?? '')) ?></span>
                        </div>
                    </div>
                    <div>
                        <?php if ($elegivel): ?>
                            <span class="badge bg-erp-success text-white px-4 py-2 text-[10px] font-black uppercase tracking-widest shadow-lg shadow-erp-success/10">Apto a votar</span>
                        <?php else: ?>
                            <span class="badge bg-erp-gold text-erp-navy px-4 py-2 text-[10px] font-black uppercase tracking-widest shadow-lg shadow-erp-gold/10">Apenas Acompanhamento</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($elegivel): ?>
                    <div class="card-body p-8 bg-erp-surface-2 border-b border-erp-border/30">
                        <form method="POST" action="/secretaria/balaustres/votar" class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-end">
                            <input type="hidden" name="balaustre_id" value="<?= $balaustreId ?>">
                            <input type="hidden" name="return_to" value="/secretaria/votacao">
                            
                            <div class="lg:col-span-1">
                                <label for="voto-<?= $balaustreId ?>" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Sua Decisão</label>
                                <select name="voto" id="voto-<?= $balaustreId ?>" class="form-select shadow-sm">
                                    <option value="aprovar">Aprovar</option>
                                    <option value="pedir_correcao">Pedir correção</option>
                                    <option value="rejeitar">Rejeitar</option>
                                </select>
                            </div>
                            
                            <div class="lg:col-span-2">
                                <label for="justificativa-<?= $balaustreId ?>" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Justificativa (Opcional)</label>
                                <input type="text" name="justificativa" id="justificativa-<?= $balaustreId ?>" placeholder="Destaque pontos necessários..." class="form-input shadow-sm">
                            </div>

                            <div class="lg:col-span-1">
                                <button type="submit" class="btn btn-primary w-full py-3.5 shadow-xl shadow-erp-navy/10 hover-lift">Registrar Voto</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <div class="card-footer p-6 bg-erp-surface-2/40 border-t border-erp-border/30 flex items-center justify-between">
                    <p class="text-xs text-erp-muted font-medium">
                        <?php if ($elegivel): ?>
                            O seu voto será contabilizado na base congelada de votantes desta sessão.
                        <?php else: ?>
                            Seu nome não consta na base congelada de votantes desta sessão (pode ser devido a ausência ou grau).
                        <?php endif; ?>
                    </p>
                    <a href="/secretaria/balaustres/visualizar?id=<?= $balaustreId ?>" class="btn btn-secondary !py-2 !px-4 text-[10px] font-black uppercase tracking-widest hover-lift shadow-sm">Ver Balaústre</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
