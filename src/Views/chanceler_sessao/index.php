<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessoes = is_array($sessoes ?? null) ? $sessoes : [];
$confirmados = is_array($confirmados ?? null) ? $confirmados : [];
$visitantesResumo = is_array($visitantesResumo ?? null) ? $visitantesResumo : [];
$mapaPresencas = is_array($mapaPresencas ?? null) ? $mapaPresencas : [];

$sessaoEmFoco = $sessaoSelecionada ?? $proximaSessao ?? null;
$presentesEfetivos = array_values(array_filter(
    $mapaPresencas,
    static fn (array $registro): bool => !empty($registro['presente'])
));

$formatarDataHoraExibicao = static function (?string $valor): string {
    if (empty(trim((string) $valor))) return 'Data a definir';
    try {
        return (new DateTimeImmutable($valor))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d/m/Y \à\s H:i');
    } catch (\Throwable $e) {
        return (string) $valor;
    }
};

$descricaoAgape = static fn (array $sessao): string => 
    match (strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')))) {
        'gratuito' => 'Gratuito',
        'pago' => 'Pago',
        default => 'Não haverá',
    };

$descricaoModeloTesourariaAgape = static function (array $sessao): string {
    if (strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera'))) === 'nao_havera') {
        return 'Não se aplica';
    }
    return match (strtolower(trim((string) ($sessao['agape_modelo_financeiro'] ?? 'oficial_loja')))) {
        'particular' => 'Particular',
        'misto' => 'Misto',
        default => 'Oficial da Loja',
    };
};

$paramsCertificado = [];
if ($sessaoEmFoco && !empty($sessaoEmFoco['data_hora_inicio'])) {
    $paramsCertificado = [
        'data_sessao' => substr((string) $sessaoEmFoco['data_hora_inicio'], 0, 10),
        'tipo_sessao' => (string) ($sessaoEmFoco['tipo_sessao'] ?? 'Ordinaria'),
        'grau_sessao' => (string) ($sessaoEmFoco['grau_sessao'] ?? 'Mestre Macom'),
    ];
}
$urlCertificado = '/chancelaria/certificado' . ($paramsCertificado !== [] ? '?' . http_build_query($paramsCertificado) : '');

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Painel do Chanceler';
$appShellTitle = 'Controle de Sessão';
$appShellDescription = 'Realize o check-in, acompanhe a nominata e gerencie os visitantes para a sessão.';
$appShellActiveHref = '/chanceler/sessao';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?>
<div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
<div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<!-- Métricas Principais -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card-metric">
        <span class="card-metric-label">Sessão Ativa</span>
        <span class="card-metric-value truncate"><?= htmlspecialchars((string) ($sessaoEmFoco['titulo'] ?? 'N/D')) ?></span>
        <span class="card-metric-context"><?= htmlspecialchars($formatarDataHoraExibicao($sessaoEmFoco['data_hora_inicio'] ?? null)) ?></span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Frequência Efetiva</span>
        <span class="card-metric-value"><?= count($presentesEfetivos) ?></span>
        <span class="card-metric-context">Obreiros presentes</span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Visitantes</span>
        <span class="card-metric-value"><?= count($visitantesResumo) ?></span>
        <span class="card-metric-context">Leitura rápida para apoio</span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Confirmados (Ágape)</span>
        <span class="card-metric-value"><?= count($confirmados) ?></span>
        <span class="card-metric-context">Presença no ágape</span>
    </div>
</div>

<!-- Seleção de Sessão e Detalhes -->
<div class="card mb-8">
    <div class="card-header">
        <h2 class="card-title">Contexto da Sessão</h2>
        <p class="card-subtitle">Troque a sessão em foco para visualizar os dados correspondentes.</p>
    </div>
    <div class="card-body">
        <form method="GET" action="/chanceler/sessao" class="flex flex-col md:flex-row md:items-end md:gap-4">
            <div class="flex-grow">
                <label for="sessao_id" class="form-label">Selecionar Sessão</label>
                <select id="sessao_id" name="sessao_id" onchange="this.form.submit()" class="form-select">
                    <?php if (empty($sessoes)): ?>
                        <option disabled selected>Nenhuma sessão disponível</option>
                    <?php else: ?>
                        <?php foreach ($sessoes as $sessaoOpcao): ?>
                            <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= !empty($sessaoEmFoco['id']) && (int) $sessaoEmFoco['id'] === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) (($sessaoOpcao['titulo'] ?? '') ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                (<?= htmlspecialchars($formatarDataHoraExibicao($sessaoOpcao['data_hora_inicio'] ?? null)) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <a href="<?= htmlspecialchars($urlCertificado) ?>" class="btn btn-secondary mt-4 md:mt-0">Emitir Certificado de Visitante</a>
        </form>

        <?php if ($sessaoEmFoco): ?>
            <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 text-sm">
                <div class="info-badge"><span>Nominata Prevista</span><strong><?= count($mapaPresencas) ?></strong></div>
                <div class="info-badge"><span>Presentes Efetivos</span><strong><?= count($presentesEfetivos) ?></strong></div>
                <div class="info-badge"><span>Visitantes</span><strong><?= count($visitantesResumo) ?></strong></div>
                <div class="info-badge"><span>Ágape</span><strong><?= htmlspecialchars($descricaoAgape($sessaoEmFoco)) ?></strong></div>
                <div class="info-badge"><span>Modelo Financeiro</span><strong><?= htmlspecialchars($descricaoModeloTesourariaAgape($sessaoEmFoco)) ?></strong></div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-6">Nenhuma sessão futura cadastrada.</div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
    <!-- Coluna de Check-in -->
    <div class="xl:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Check-in do Quadro da Loja</h2>
                <p class="card-subtitle">Marque os presentes para compor a lista final da sessão.</p>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($mapaPresencas)): ?>
                    <?php foreach ($mapaPresencas as $registro): ?>
                        <form method="POST" action="/chanceler/sessao/presenca" class="checkin-card">
                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($registro['id'] ?? '')) ?>">
                            <div>
                                <div class="font-bold text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($registro['nome'] ?? 'Obreiro')) ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    CIM: <?= htmlspecialchars((string) ($registro['cim'] ?? '-')) ?> &middot; Grau: <?= htmlspecialchars((string) ($registro['grau'] ?? '-')) ?>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-3">
                                <button type="submit" name="presente" value="1" class="btn-checkin <?= !empty($registro['presente']) ? 'presente' : '' ?>">Presente</button>
                                <button type="submit" name="presente" value="0" class="btn-checkin <?= empty($registro['presente']) ? 'ausente' : '' ?>">Ausente</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info md:col-span-2">Nenhuma nominata prevista para esta sessão.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Lista Final de Presentes (<?= count($presentesEfetivos) ?>)</h2>
                <p class="card-subtitle">Conferência rápida da nominata efetiva da sessão.</p>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($presentesEfetivos)): ?>
                    <?php foreach ($presentesEfetivos as $presente): ?>
                        <div class="list-item-condensed">
                            <div class="font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($presente['nome'] ?? 'Obreiro')) ?></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                CIM: <?= htmlspecialchars((string) ($presente['cim'] ?? '-')) ?> &middot; Grau: <?= htmlspecialchars((string) ($presente['grau'] ?? '-')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info md:col-span-2">Ainda não há presentes efetivos marcados.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna de Visitantes e Confirmados -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Visitantes Resumidos (<?= count($visitantesResumo) ?>)</h2>
                <p class="card-subtitle">Apoio para Secretaria e Orador.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if (!empty($visitantesResumo)): ?>
                    <?php foreach ($visitantesResumo as $visitante): ?>
                        <div class="list-item-condensed">
                            <div class="font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? '')) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Nenhum visitante registrado para esta sessão.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Confirmados para o Ágape (<?= count($confirmados) ?>)</h2>
                <p class="card-subtitle">Lista de presença no ágape.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if (!empty($confirmados)): ?>
                    <?php foreach ($confirmados as $confirmado): ?>
                        <div class="list-item-condensed">
                            <div class="font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400"><?= !empty($confirmado['participara_agape']) ? 'Confirmado com ágape' : 'Confirmado sem ágape' ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Nenhum irmão confirmado para o ágape.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-subtitle { @apply text-sm text-gray-500 dark:text-gray-400 mt-1; }
    .card-body { @apply p-5; }

    .card-metric { @apply bg-white dark:bg-gray-800 p-5 rounded-lg shadow-md flex flex-col; }
    .card-metric-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400; }
    .card-metric-value { @apply text-2xl font-bold text-gray-900 dark:text-white mt-1; }
    .card-metric-context { @apply text-sm text-gray-500 dark:text-gray-400 mt-1; }

    .alert { @apply p-4 rounded-md text-sm; }
    .alert-success { @apply bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300; }
    .alert-danger { @apply bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300; }
    .alert-info { @apply bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-select { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }

    .btn { @apply inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-900; }
    .btn-secondary { @apply bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500; }

    .info-badge { @apply bg-gray-100 dark:bg-gray-700 p-3 rounded-md flex flex-col items-start; }
    .info-badge span { @apply text-xs text-gray-500 dark:text-gray-400; }
    .info-badge strong { @apply text-base font-bold text-gray-900 dark:text-white; }

    .checkin-card { @apply bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-between; }
    .btn-checkin { @apply px-3 py-1.5 text-sm rounded-md border transition-colors; }
    .btn-checkin.presente { @apply bg-green-600 text-white border-green-600; }
    .btn-checkin:not(.presente) { @apply bg-transparent text-green-700 border-green-300 hover:bg-green-50 dark:text-green-300 dark:border-green-700 dark:hover:bg-green-900/30; }
    .btn-checkin.ausente { @apply bg-gray-600 text-white border-gray-600; }
    .btn-checkin:not(.ausente) { @apply bg-transparent text-gray-700 border-gray-300 hover:bg-gray-200 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700; }

    .list-item-condensed { @apply bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md; }
</style>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

