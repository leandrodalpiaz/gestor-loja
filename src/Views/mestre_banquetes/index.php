<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessaoEmFoco = $sessaoEmFoco ?? $proximaSessao ?? null;
$semAgape = array_values(array_filter(
    $confirmados,
    static fn (array $item): bool => empty($item['participara_agape'])
));

$tituloSessao = static function (?array $sessao): string {
    if (!$sessao) return 'N/A';
    $titulo = trim((string) ($sessao['titulo'] ?? ''));
    return $titulo !== '' ? $titulo : trim(((string) ($sessao['tipo_sessao'] ?? 'Sessão')) . ' - ' . ((string) ($sessao['grau_sessao'] ?? '')));
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Mestre de Banquetes';
$appShellTitle = 'Painel de Controle do Ágape';
$appShellDescription = 'Controle do ágape, previsão de participantes e observações logísticas por sessão.';
$appShellActiveHref = '/mestre-banquetes';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Sessão em Foco</p><p class="card-metric-value text-lg truncate" title="<?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?>"><?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Confirmados na Sessão</p><p class="card-metric-value"><?= count($confirmados) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Participantes do Ágape</p><p class="card-metric-value"><?= count($participantesAgape) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Status Operacional</p><p class="card-metric-value text-lg capitalize"><?= htmlspecialchars((string) ($operacaoBanquete['status_operacional'] ?? 'planejamento'))?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Operação do Banquete</h2><p class="card-description">Ajuste a sessão de trabalho e registre a previsão e o status logístico do ágape.</p></div>
            <div class="card-body space-y-6">
                <form method="GET" action="/mestre-banquetes" class="flex flex-col sm:flex-row sm:items-end sm:gap-4">
                    <div class="flex-grow">
                        <label for="sessao_id" class="form-label">Selecionar Sessão</label>
                        <select id="sessao_id" name="sessao_id" onchange="this.form.submit()" class="form-select">
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= !empty($sessaoEmFoco['id']) && (int) $sessaoEmFoco['id'] === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tituloSessao($sessaoOpcao)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <a href="/miniapp/mestre-banquetes" class="btn btn-outline-secondary mt-4 sm:mt-0">Abrir Mobile</a>
                </form>

                <?php if ($sessaoEmFoco): ?>
                    <div class="list-item-report">
                        <p class="text-sm">Configuração do ágape: <strong><?= htmlspecialchars((string) ($descricaoAgape ?? 'Não informado')) ?></strong></p>
                        <p class="text-sm mt-1">Modelo financeiro: <strong><?= htmlspecialchars((string) ($descricaoModeloTesourariaAgape ?? 'Não informado')) ?></strong></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/mestre-banquetes/operacao/salvar" class="space-y-4">
                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="status_operacional" class="form-label">Status Operacional</label>
                            <select name="status_operacional" id="status_operacional" class="form-select">
                                <?php $statusAtual = (string) ($operacaoBanquete['status_operacional'] ?? 'planejamento'); ?>
                                <?php foreach (['planejamento' => 'Planejamento', 'preparacao' => 'Preparação', 'abastecimento' => 'Abastecimento', 'fechado' => 'Fechado'] as $valor => $label): ?>
                                    <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><label for="previsao_participantes" class="form-label">Previsão de Participantes</label><input type="number" min="0" name="previsao_participantes" id="previsao_participantes" value="<?= htmlspecialchars((string) ($operacaoBanquete['previsao_participantes'] ?? '')) ?>" class="form-input"></div>
                    </div>
                    <div><label for="observacoes" class="form-label">Observações Logísticas</label><textarea name="observacoes" id="observacoes" rows="3" class="form-textarea" placeholder="Ex: cardápio, restrições, equipe de apoio..."><?= htmlspecialchars((string) ($operacaoBanquete['observacoes'] ?? '')) ?></textarea></div>
                    <div class="text-right"><button type="submit" class="btn btn-primary">Salvar Operação</button></div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Participantes do Ágape</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($participantesAgape)): ?>
                        <p class="text-center text-gray-500 py-4">Ainda não há participantes confirmados com ágape.</p>
                    <?php else: ?>
                        <?php foreach ($participantesAgape as $participante): ?>
                            <div class="list-item">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($participante['nome'] ?? 'Obreiro')) ?></p>
                                <p class="text-sm text-gray-500">CIM: <?= htmlspecialchars((string) ($participante['cim'] ?? '-')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Confirmados sem Ágape</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($semAgape)): ?>
                        <p class="text-center text-gray-500 py-4">Não há confirmados sem ágape.</p>
                    <?php else: ?>
                        <?php foreach ($semAgape as $confirmado): ?>
                            <div class="list-item">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></p>
                                <p class="text-sm text-gray-500">CIM: <?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Agenda Futura</h2></div>
            <div class="card-body space-y-3">
                <?php foreach ($sessoes as $sessao): ?>
                    <a href="/mestre-banquetes?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="list-item-action">
                        <p class="font-semibold"><?= htmlspecialchars($tituloSessao($sessao))?></p>
                        <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></p>
                        <div class="mt-2 flex gap-4 text-xs text-gray-500">
                            <span>Confirmados: <strong><?= (int) ($sessao['total_confirmados'] ?? 0) ?></strong></span>
                            <span>Ágape: <strong><?= (int) ($sessao['total_agape'] ?? 0) ?></strong></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-sm text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-5; }

    .card-metric { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md p-5; }
    .card-metric-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400; }
    .card-metric-value { @apply mt-1 text-3xl font-bold; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-input, .form-select, .form-textarea { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }

    .list-item { @apply flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md text-sm; }
    .list-item-report { @apply p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg; }
    .list-item-action { @apply block bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-700 transition; }

    .alert { @apply px-4 py-3 rounded-lg; }
    .alert-success { @apply bg-green-100 dark:bg-green-900/20 border border-green-400 text-green-700 dark:text-green-300; }
    .alert-danger { @apply bg-red-100 dark:bg-red-900/20 border border-red-400 text-red-700 dark:text-red-300; }
</style>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>

