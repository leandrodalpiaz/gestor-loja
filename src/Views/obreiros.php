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
    <div class="metric-card"><div class="metric-label">Com Alerta</div><div class="metric-value text-warning"><?= (int) $resumoObreiros['com_alerta'] ?></div></div>
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
        <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
            <a href="/obreiros" class="btn btn-secondary">Limpar Filtros</a>
            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
        </div>
    </form>
</div>

<!-- Lista de Obreiros -->
<?php if (empty($obreiros)): ?>
    <div class="text-center py-12 bg-white/[0.01] border border-white/5 rounded-2xl">
        <span class="text-4xl block mb-3 opacity-30">👥</span>
        <h3 class="text-sm font-bold text-white mb-1">Nenhum Obreiro Encontrado</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto">Tente ajustar os filtros de busca para localizar os membros.</p>
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
                            <div class="font-semibold text-white"><?= htmlspecialchars($nomeExibicao) ?></div>
                            <div class="text-xs text-slate-400">CIM: <?= htmlspecialchars($obreiro['cim'] ?? '-') ?> | Grau: <?= htmlspecialchars($obreiro['grau'] ?? '-') ?></div>
                        </td>
                        <td><?php $label = $situacao; $type = 'info'; require __DIR__ . '/components/badge-status.php'; ?></td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <?php if (empty($cargosAtuais)): ?>
                                    <span class="text-xs text-slate-500">Nenhum</span>
                                <?php else: ?>
                                    <?php foreach ($cargosAtuais as $codigo): ?>
                                        <?php $label = Cargo::rotuloOficial($codigo); $type = 'neutral'; require __DIR__ . '/components/badge-status.php'; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <?php if ($podeGerenciarObreiros): ?>
                                    <a href="/obreiros/editar?id=<?= $obreiro['id'] ?>" class="btn btn-secondary text-xs">Editar</a>
                                    <form method="post" action="/obreiros/inativar" onsubmit="return confirm('Inativar este obreiro?');" class="inline">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                        <button type="submit" class="btn btn-secondary text-xs">Inativar</button>
                                    </form>
                                    <form method="post" action="/obreiros/excluir" onsubmit="return confirm('Excluir este obreiro da gestão? Esta ação pode ser irreversível.');" class="inline">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                        <button type="submit" class="btn btn-secondary text-xs border border-danger/30 text-danger hover:bg-danger/10">Excluir</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($podeGerarConvitesAcesso): ?>
                                    <form method="post" action="/admin/convites/gerar" onsubmit="return confirm('Gerar convite de acesso para este obreiro?');" class="inline">
                                        <input type="hidden" name="obreiro_id" value="<?= $obreiro['id'] ?>">
                                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToAtual) ?>">
                                        <button type="submit" class="btn btn-secondary text-xs border border-success/30 text-success hover:bg-success/10">Gerar Convite</button>
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
            <?php require __DIR__ . '/components/card-obreiro.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

