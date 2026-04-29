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
$appShellTitle = 'Balaústres e Votação';
$appShellDescription = 'Acompanhe e registre votos nos balaústres das sessões publicadas.';
$appShellActiveHref = '/secretaria/votacao';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Votações Abertas -->
<div class="space-y-6">
    <?php if (empty($votacoesAbertas)): ?>
        <div class="card">
            <div class="card-body text-center">
                <p class="text-gray-500">No momento, não há votações abertas para o seu perfil.</p>
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

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4; }
    .card-footer { @apply p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-sm text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-5; }

    .form-input, .form-select { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }

    .alert { @apply px-4 py-3 rounded-lg; }
    .alert-success { @apply bg-green-100 dark:bg-green-900/20 border border-green-400 text-green-700 dark:text-green-300; }
    .alert-danger { @apply bg-red-100 dark:bg-red-900/20 border border-red-400 text-red-700 dark:text-red-300; }

    .badge { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold; }
    .badge-success { @apply bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-200; }
    .badge-warning { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-200; }
</style>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>

