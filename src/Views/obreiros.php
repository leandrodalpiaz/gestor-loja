<?php
declare(strict_types=1);

use App\Models\Cargo;

// #############################################################################
// SEGURANÇA E PREPARAÇÃO
// #############################################################################

if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$filtrosObreiros = $filtrosObreiros ?? ['busca' => '', 'situacao' => '', 'grau' => '', 'alerta' => '', 'cargo_codigo' => '', 'ordenacao' => 'nome'];
$resumoObreiros = $resumoObreiros ?? ['total' => 0, 'ativos' => 0, 'com_alerta' => 0, 'com_telegram' => 0, 'mestres' => 0];
$podeGerenciarObreiros = (bool) ($podeGerenciarObreiros ?? false);
$podeGerarConvitesAcesso = (bool) ($podeGerarConvitesAcesso ?? false);
$returnToAtual = (string) ($_SERVER['REQUEST_URI'] ?? '/obreiros');

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
$conviteGeradoLink = $_SESSION['convite_gerado_link'] ?? null;
$conviteGeradoExpiraEm = $_SESSION['convite_gerado_expira_em'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro'], $_SESSION['convite_gerado_link'], $_SESSION['convite_gerado_expira_em']);

$rotulosAlerta = [
    'sem_nascimento' => 'Nascimento ausente',
    'sem_escolaridade' => 'Escolaridade ausente',
    'sem_profissao' => 'Profissão ausente',
    'sem_situacao' => 'Situação do quadro ausente',
    'sem_data_ingresso' => 'Data de ingresso ausente',
    'sem_potencia' => 'Potência ausente',
];

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Central de Obreiros';
$appShellDescription = 'Registro administrativo, filtros operacionais e organização do quadro da Loja.';
$appShellActiveHref = '/obreiros';
$appShellActions = [['label' => 'Somente com Alertas', 'href' => '/obreiros?alerta=cadastro']];
if ($podeGerenciarObreiros) {
    $appShellActions[] = ['label' => 'Registrar Novo Obreiro', 'href' => '/obreiros/novo', 'primary' => true];
}

require __DIR__ . '/partials/erp_shell_open.php';
?>

<!-- Notificações -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>
<?php if ($conviteGeradoLink): ?>
    <div class="alert alert-info mb-6">
        <h4 class="font-bold">Convite de acesso gerado!</h4>
        <p class="text-sm">Envie o link abaixo para o Obreiro. Expira em: <?= htmlspecialchars($conviteGeradoExpiraEm ?: 'N/A') ?></p>
        <div class="mt-2 flex flex-wrap gap-2">
            <input id="convite-link" readonly value="<?= htmlspecialchars($conviteGeradoLink) ?>" class="form-input flex-grow">
            <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($conviteGeradoLink) ?>')">Copiar</button>
        </div>
    </div>
<?php endif; ?>

<!-- Métricas -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
    <div class="metric-card"><div class="metric-label">Total Filtrado</div><div class="metric-value"><?= (int) $resumoObreiros['total'] ?></div></div>
    <div class="metric-card"><div class="metric-label">Regulares</div><div class="metric-value"><?= (int) $resumoObreiros['ativos'] ?></div></div>
    <div class="metric-card"><div class="metric-label">Com Alerta</div><div class="metric-value text-yellow-600 dark:text-yellow-400"><?= (int) $resumoObreiros['com_alerta'] ?></div></div>
    <div class="metric-card"><div class="metric-label">Bot Vinculado</div><div class="metric-value"><?= (int) $resumoObreiros['com_telegram'] ?></div></div>
    <div class="metric-card"><div class="metric-label">Mestres</div><div class="metric-value"><?= (int) $resumoObreiros['mestres'] ?></div></div>
</div>

<!-- Filtros -->
<div class="card mb-6">
    <form method="GET" action="/obreiros" class="card-body space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="md:col-span-2 lg:col-span-2">
                <label for="filtro-busca" class="form-label">Buscar Obreiro</label>
                <input id="filtro-busca" type="text" name="busca" value="<?= htmlspecialchars($filtrosObreiros['busca']) ?>" class="form-input" placeholder="Nome, CIM, cargo...">
            </div>
            <div>
                <label for="filtro-situacao" class="form-label">Situação</label>
                <select id="filtro-situacao" name="situacao" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach (\App\Models\Obreiro::SITUACOES_QUADRO as $situacao): ?>
                        <option value="<?= htmlspecialchars($situacao) ?>" <?= ($filtrosObreiros['situacao'] ?? '') === $situacao ? 'selected' : '' ?>><?= htmlspecialchars($situacao) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtro-grau" class="form-label">Grau</label>
                <select id="filtro-grau" name="grau" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $grau): ?>
                        <option value="<?= htmlspecialchars($grau) ?>" <?= ($filtrosObreiros['grau'] ?? '') === $grau ? 'selected' : '' ?>><?= htmlspecialchars($grau) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtro-cargo" class="form-label">Cargo Oficial</label>
                <select id="filtro-cargo" name="cargo_codigo" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($cargosFiltros as $cargo): ?>
                        <option value="<?= htmlspecialchars($cargo['codigo']) ?>" <?= ($filtrosObreiros['cargo_codigo'] ?? '') === $cargo['codigo'] ? 'selected' : '' ?>><?= htmlspecialchars(Cargo::rotuloOficial($cargo['codigo'], $cargo['nome_exibicao'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filtro-alerta" class="form-label">Alerta</label>
                <select id="filtro-alerta" name="alerta" class="form-select">
                    <option value="">Todos</option>
                    <option value="cadastro" <?= ($filtrosObreiros['alerta'] ?? '') === 'cadastro' ? 'selected' : '' ?>>Com alerta de registro</option>
                </select>
            </div>
            <div>
                <label for="filtro-ordenacao" class="form-label">Ordenar por</label>
                <select id="filtro-ordenacao" name="ordenacao" class="form-select">
                    <option value="nome" <?= ($filtrosObreiros['ordenacao'] ?? '') === 'nome' ? 'selected' : '' ?>>Nome</option>
                    <option value="grau" <?= ($filtrosObreiros['ordenacao'] ?? '') === 'grau' ? 'selected' : '' ?>>Grau</option>
                    <option value="situacao" <?= ($filtrosObreiros['ordenacao'] ?? '') === 'situacao' ? 'selected' : '' ?>>Situação</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="/obreiros" class="btn btn-secondary">Limpar Filtros</a>
            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
        </div>
    </form>
</div>

<!-- Lista de Obreiros -->
<?php if (empty($obreiros)): ?>
    <div class="card text-center py-12">
        <h3 class="text-lg font-semibold">Nenhum Obreiro Encontrado</h3>
        <p class="text-gray-500 mt-1">Tente ajustar os filtros para encontrar resultados.</p>
    </div>
<?php else: ?>
    <!-- Tabela para Desktop -->
    <div class="hidden lg:block card overflow-hidden">
        <table class="table-base">
            <thead>
                <tr>
                    <th>Obreiro</th>
                    <th>Situação</th>
                    <th>Cargos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($obreiros as $obreiro): ?>
                    <?php
                    $nomeExibicao = $obreiro['nome_historico'] ?: $obreiro['nome'];
                    $situacao = $obreiro['situacao_quadro'] ?? 'Regular';
                    $cargosAtuais = $obreiro['cargos_codigos'] ?? [];
                    ?>
                    <tr>
                        <td>
                            <div class="font-semibold"><?= htmlspecialchars($nomeExibicao) ?></div>
                            <div class="text-xs text-gray-500">CIM: <?= htmlspecialchars($obreiro['cim'] ?? '-') ?> | Grau: <?= htmlspecialchars($obreiro['grau'] ?? '-') ?></div>
                        </td>
                        <td><span class="badge-status badge-status-info"><?= htmlspecialchars($situacao) ?></span></td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <?php if (empty($cargosAtuais)): ?>
                                    <span class="text-xs text-gray-500">Nenhum</span>
                                <?php else: ?>
                                    <?php foreach ($cargosAtuais as $codigo): ?>
                                        <span class="badge-status badge-status-neutral"><?= htmlspecialchars(Cargo::rotuloOficial($codigo)) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <?php if ($podeGerenciarObreiros): ?>
                                    <a href="/obreiros/editar?id=<?= $obreiro['id'] ?>" class="btn btn-secondary text-xs">Editar</a>
                                <?php endif; ?>
                                <?php if ($podeGerarConvitesAcesso): ?>
                                    <form method="post" action="/admin/convites/gerar" onsubmit="return confirm('Gerar convite de acesso para este obreiro?');">
                                        <input type="hidden" name="obreiro_id" value="<?= $obreiro['id'] ?>">
                                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToAtual) ?>">
                                        <button type="submit" class="btn btn-secondary text-xs bg-green-100 text-green-800 hover:bg-green-200">Gerar Convite</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cards para Mobile -->
    <div class="lg:hidden space-y-4">
        <?php foreach ($obreiros as $obreiro): ?>
            <?php
            $nomeExibicao = $obreiro['nome_historico'] ?: $obreiro['nome'];
            $situacao = $obreiro['situacao_quadro'] ?? 'Regular';
            $cargosAtuais = $obreiro['cargos_codigos'] ?? [];
            $alertas = $obreiro['alertas_cadastro'] ?? [];
            ?>
            <div class="card">
                <div class="card-body">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg"><?= htmlspecialchars($nomeExibicao) ?></h3>
                            <p class="text-sm text-gray-500">CIM: <?= htmlspecialchars($obreiro['cim'] ?? '-') ?> | Grau: <?= htmlspecialchars($obreiro['grau'] ?? '-') ?></p>
                        </div>
                        <span class="badge-status badge-status-info"><?= htmlspecialchars($situacao) ?></span>
                    </div>
                    <?php if (!empty($alertas)): ?>
                        <div class="mt-2"><span class="badge-status badge-status-warning"><?= count($alertas) ?> Alerta(s) de Cadastro</span></div>
                    <?php endif; ?>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold mb-2">Cargos</h4>
                        <div class="flex flex-wrap gap-1">
                            <?php if (empty($cargosAtuais)): ?>
                                <span class="text-xs text-gray-500">Nenhum cargo oficial em exercício.</span>
                            <?php else: ?>
                                <?php foreach ($cargosAtuais as $codigo): ?>
                                    <span class="badge-status badge-status-neutral"><?= htmlspecialchars(Cargo::rotuloOficial($codigo)) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
                        <?php if ($podeGerenciarObreiros): ?>
                            <a href="/obreiros/editar?id=<?= $obreiro['id'] ?>" class="btn btn-primary text-sm">Editar Obreiro</a>
                        <?php endif; ?>
                        <?php if ($podeGerarConvitesAcesso): ?>
                            <form method="post" action="/admin/convites/gerar" onsubmit="return confirm('Gerar convite de acesso para este obreiro?');" class="flex-grow">
                                <input type="hidden" name="obreiro_id" value="<?= $obreiro['id'] ?>">
                                <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToAtual) ?>">
                                <button type="submit" class="btn btn-secondary text-sm w-full">Gerar Convite</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm; }
    .card-body { @apply p-5; }

    .metric-card { @apply bg-white dark:bg-gray-800 p-5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700; }
    .metric-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400; }
    .metric-value { @apply text-3xl font-bold mt-1 text-gray-800 dark:text-gray-100; }

    .alert { @apply p-4 rounded-md text-sm; }
    .alert-success { @apply bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300; }
    .alert-danger { @apply bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300; }
    .alert-info { @apply bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-input, .form-select { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }

    .btn { @apply inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-900 transition-colors; }
    .btn-primary { @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500; }
    .btn-secondary { @apply bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500; }

    .table-base { @apply min-w-full divide-y divide-gray-200 dark:divide-gray-700; }
    .table-base thead th { @apply px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-700/50; }
    .table-base tbody tr:hover { @apply bg-gray-50 dark:bg-gray-900/20; }
    .table-base tbody td { @apply px-4 py-4 text-sm text-gray-700 dark:text-gray-300 align-top; }

    .badge-status { @apply inline-block px-2 py-0.5 text-xs font-semibold rounded-full; }
    .badge-status-info { @apply bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300; }
    .badge-status-warning { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300; }
    .badge-status-neutral { @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300; }
</style>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

