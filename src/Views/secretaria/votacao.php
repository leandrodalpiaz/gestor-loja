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

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Balaústres Em Votação -->
<div class="space-y-6">
    <?php if (empty($votacoesAbertas)): ?>
        <div class="card">
            <div class="card-body text-center">
                <p class="text-gray-500">Nenhuma votação aberta no momento.</p>
                <p class="text-xs text-gray-500 mt-2">Para o secretário: prepare o balaústre na Secretaria e marque como apto para votação.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($votacoesAbertas as $balaustre): ?>
            <?php
            $balaustreId = (int) ($balaustre['id'] ?? 0);
            $elegivel = (bool) ($elegibilidadeVoto[$balaustreId] ?? false);
            ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title"><?= htmlspecialchars($balaustre['numero_balaustre'] ?: 'Balaústre sem número') ?></h2>
                        <p class="card-description">
                            <?= htmlspecialchars($balaustre['sessao_titulo'] ?: (($balaustre['tipo_sessao'] ?? 'Sessão') . ' - ' . ($balaustre['grau_sessao'] ?? ''))) ?>
                            <span class="text-xs text-gray-500 ml-2">(Sessão em: <?= htmlspecialchars((string) ($balaustre['data_hora_inicio'] ?? '')) ?>)</span>
                        </p>
                    </div>
                    <div>
                        <?php if ($elegivel): ?>
                            <span class="badge badge-success">Apto a votar</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Apenas para acompanhamento</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($elegivel): ?>
                    <div class="card-body bg-gray-50 dark:bg-gray-900/50">
                        <form method="POST" action="/secretaria/balaustres/votar" class="flex flex-col md:flex-row gap-4 md:items-center">
                            <input type="hidden" name="balaustre_id" value="<?= $balaustreId ?>">
                            <input type="hidden" name="return_to" value="/secretaria/votacao">
                            
                            <div class="flex-grow">
                                <label for="voto-<?= $balaustreId ?>" class="sr-only">Seu voto</label>
                                <select name="voto" id="voto-<?= $balaustreId ?>" class="form-select w-full">
                                    <option value="aprovar">Aprovar</option>
                                    <option value="pedir_correcao">Pedir correção</option>
                                    <option value="rejeitar">Rejeitar</option>
                                </select>
                            </div>
                            
                            <div class="flex-grow-[2]">
                                <label for="justificativa-<?= $balaustreId ?>" class="sr-only">Justificativa</label>
                                <input type="text" name="justificativa" id="justificativa-<?= $balaustreId ?>" placeholder="Justificativa (opcional)" class="form-input w-full">
                            </div>

                            <button type="submit" class="btn btn-primary w-full md:w-auto">Registrar Voto</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="card-footer">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Seu nome não consta na base congelada de votantes desta sessão.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
