<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$usuarioLogado = is_array($_SESSION['usuario_logado'] ?? null) ? $_SESSION['usuario_logado'] : [];
$usuarioNomeCompleto = trim((string) ($usuarioLogado['nome_completo'] ?? ''));
$usuarioNomeHistorico = trim((string) ($usuarioLogado['nome_historico'] ?? ''));
if (in_array(strtolower(trim((string) $usuarioNome)), ['admin', 'administrador'], true)) {
    $usuarioNome = ($usuarioNomeCompleto !== '' && stripos($usuarioNomeCompleto, 'acesso temporario') === false)
        ? $usuarioNomeCompleto
        : (($usuarioNomeHistorico !== '' && !in_array(strtolower($usuarioNomeHistorico), ['admin', 'administrador'], true)) ? $usuarioNomeHistorico : 'Irmão');
}

$isSystemAdmin = (bool) ($_SESSION['is_system_admin'] ?? false);
$isTestSession = isset($_SESSION['usuario_id']) && (string) $_SESSION['usuario_id'] === '0';
$allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'false', FILTER_VALIDATE_BOOL);
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;

$dashboardPermissions = isset($dashboardPermissions) && is_array($dashboardPermissions) ? $dashboardPermissions : [];
$dashboardCan = static fn (string $p): bool => $showAllPanels || (bool) ($dashboardPermissions[$p] ?? false);

$secoes = \App\Core\DashboardSections::build($dashboardCan, $isSystemAdmin);

$dashboardConfiguracaoLoja = isset($dashboardConfiguracaoLoja) && is_array($dashboardConfiguracaoLoja) ? $dashboardConfiguracaoLoja : [];
$dashboardNomeLoja = trim((string) ($dashboardConfiguracaoLoja['nome_loja'] ?? ($_SESSION['tenant_name'] ?? 'Loja Maçônica')));
if (!empty($dashboardConfiguracaoLoja['numero_loja'])) {
    $dashboardNomeLoja .= ' nº ' . $dashboardConfiguracaoLoja['numero_loja'];
}

$dashboardSessoes = isset($dashboardSessoes) && is_array($dashboardSessoes) ? $dashboardSessoes : [];
$dashboardRecados = isset($dashboardRecados) && is_array($dashboardRecados) ? $dashboardRecados : [];
$dashboardPalavraIrmao = trim((string) ($dashboardPalavraIrmao ?? ''));
$dashboardOutrasLojas = isset($dashboardOutrasLojas) && is_array($dashboardOutrasLojas) ? $dashboardOutrasLojas : [];

$formatarDataHoraPainel = static function (?string $valor): string {
    if (empty(trim((string) $valor))) return 'Data a definir';
    try {
        return (new DateTimeImmutable($valor))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y \à\s H:i');
    } catch (\Throwable) {
        return (string) $valor;
    }
};

$resumirTexto = static fn (?string $texto, int $limite = 180): string => function_exists('mb_strimwidth')
    ? mb_strimwidth(trim(strip_tags((string) $texto)), 0, $limite, '...')
    : substr(trim(strip_tags((string) $texto)), 0, $limite - 3) . '...';

$statusClasses = static fn (?string $status): string => match (strtolower(trim((string) $status))) {
    'publicada', 'confirmada', 'confirmado', 'ativa', 'agendada', 'programada' => 'badge-status-success',
    'cancelada', 'cancelado', 'encerrada' => 'badge-status-danger',
    'rascunho', 'pendente' => 'badge-status-warning',
    default => 'badge-status-neutral',
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Painel de Comando';
$appShellTitle = $dashboardNomeLoja;
$appShellDescription = 'Abertura operacional da Loja com agenda, recados e acessos prioritários.';
$appShellActiveHref = '/dashboard';
$appShellUserLabel = $usuarioNome;
$appShellActions = [['label' => 'Sair', 'href' => '/logout']];
$appShellSidebarSections = array_merge(
    [['title' => 'Geral', 'items' => [['label' => 'Painel', 'href' => '/dashboard']]]],
    array_map(
        static fn(array $secao): array => ['title' => (string) ($secao['titulo'] ?? 'Secao'), 'items' => $secao['itens'] ?? []],
        $secoes
    )
);

require __DIR__ . '/partials/erp_shell_open.php';

?>

<?php if (isset($dashboardMensagemSucesso)): ?>
<div class="alert alert-success mb-6"><?= htmlspecialchars($dashboardMensagemSucesso) ?></div>
<?php endif; ?>
<?php if (isset($dashboardMensagemErro)): ?>
<div class="alert alert-danger mb-6"><?= htmlspecialchars($dashboardMensagemErro) ?></div>
<?php endif; ?>

<!-- Métricas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card-metric">
        <span class="card-metric-label">Próxima Sessão</span>
        <span class="card-metric-value truncate"><?= htmlspecialchars($dashboardSessoes[0]['titulo'] ?? 'A definir') ?></span>
        <span class="card-metric-context"><?= htmlspecialchars($formatarDataHoraPainel($dashboardSessoes[0]['data_hora_inicio'] ?? null)) ?></span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Perfil Ativo</span>
        <span class="card-metric-value"><?= htmlspecialchars($usuarioCargo ?: 'Operação Geral') ?></span>
        <span class="card-metric-context">Leitura personalizada</span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Áreas Visíveis</span>
        <span class="card-metric-value"><?= count($secoes) ?></span>
        <span class="card-metric-context">Módulos liberados</span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Reunião Semanal</span>
        <span class="card-metric-value"><?= htmlspecialchars($dashboardConfiguracaoLoja['dia_semana_reuniao'] ?? 'A definir') ?></span>
        <span class="card-metric-context"><?= htmlspecialchars($dashboardConfiguracaoLoja['horario_reuniao'] ?? 'Horário a definir') ?></span>
    </div>
</div>

<!-- Sessões e Recados -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <div class="lg:col-span-2 space-y-8">
        <!-- Sessões da Loja -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Próximas Sessões da Loja</h2>
                <p class="card-subtitle">Confirme sua presença e acesse os detalhes da sessão.</p>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php if (!empty($dashboardSessoes)): ?>
                    <?php foreach (array_slice($dashboardSessoes, 0, 2) as $sessao): ?>
                        <div class="flex flex-col justify-between rounded-lg bg-gray-50 dark:bg-gray-700/50 p-5 border border-gray-200 dark:border-gray-700">
                            <div>
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100"><?= htmlspecialchars($sessao['titulo'] ?? 'Sessão') ?></h3>
                                    <span class="<?= $statusClasses($sessao['status'] ?? null) ?>"><?= htmlspecialchars($sessao['status'] ?? 'Agendada') ?></span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= htmlspecialchars($formatarDataHoraPainel($sessao['data_hora_inicio'] ?? null)) ?></p>
                                <div class="mt-4 space-y-2 text-sm">
                                    <div class="flex justify-between"><span>Tipo:</span> <strong><?= htmlspecialchars($sessao['tipo_sessao'] ?? 'N/D') ?></strong></div>
                                    <div class="flex justify-between"><span>Grau:</span> <strong><?= htmlspecialchars($sessao['grau_sessao'] ?? 'N/D') ?></strong></div>
                                    <div class="flex justify-between"><span>Confirmados:</span> <strong><?= (int)($sessao['total_confirmados'] ?? 0) ?></strong></div>
                                </div>
                            </div>
                            <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-600">
                                <form method="POST" action="/dashboard#sessoes-loja" class="grid grid-cols-2 gap-3">
                                    <input type="hidden" name="dashboard_action" value="sessao_confirmacao">
                                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                    <button type="submit" name="acao" value="confirmar" class="btn btn-primary text-xs">Confirmar</button>
                                    <button type="submit" name="acao" value="cancelar" class="btn btn-secondary text-xs">Cancelar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info md:col-span-2">Nenhuma sessão programada.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="space-y-8">
        <!-- Recados -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Recados da Loja</h2></div>
            <div class="card-body space-y-4">
                <?php if (!empty($dashboardRecados)): ?>
                    <?php foreach (array_slice($dashboardRecados, 0, 2) as $recado): ?>
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($recado['titulo'] ?? 'Recado') ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= htmlspecialchars($resumirTexto($recado['conteudo'] ?? '', 100)) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Nenhum recado recente.</div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Palavra do Irmão -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Palavra do Dia</h2></div>
            <div class="card-body">
                <?php if ($dashboardPalavraIrmao): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed"><?= nl2br(htmlspecialchars($resumirTexto($dashboardPalavraIrmao, 220))) ?></p>
                    <a href="/chancelaria/efemerides" class="btn btn-secondary mt-4 text-sm">Ver Efemérides</a>
                <?php else: ?>
                    <div class="alert alert-info">Nenhuma efeméride para hoje.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Menus por Cargo -->
<div class="card mb-8">
    <div class="card-header">
        <h2 class="card-title">Menus por Cargo</h2>
        <p class="card-subtitle">Acesso rápido às rotinas de cada ofício.</p>
    </div>
    <div class="card-body grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($secoes as $secao): ?>
            <div class="group">
                <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100 mb-3"><?= htmlspecialchars($secao['titulo']) ?></h3>
                <div class="space-y-2">
                    <?php foreach ($secao['itens'] as $item): ?>
                        <a href="<?= htmlspecialchars($item['href']) ?>" class="list-item-action">
                            <span><?= htmlspecialchars($item['label']) ?></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Sessões de Outras Lojas -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Sessões de Outras Lojas</h2>
        <p class="card-subtitle">Convites e compromissos de Lojas co-irmãs.</p>
    </div>
    <div class="card-body grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (!empty($dashboardOutrasLojas)): ?>
            <?php foreach ($dashboardOutrasLojas as $sessaoExterna): ?>
                <div class="list-item-condensed">
                    <div class="font-semibold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($sessaoExterna['loja'] ?? 'Outra Loja') ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= htmlspecialchars($sessaoExterna['titulo'] ?? 'Sessão') ?></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2"><?= htmlspecialchars($formatarDataHoraPainel($sessaoExterna['data_hora_inicio'] ?? null)) ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info md:col-span-full">Nenhuma sessão externa registrada.</div>
        <?php endif; ?>
    </div>
</div>

<style>
    .card { background: var(--erp-surface); border: 1px solid var(--erp-border); border-radius: 10px; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
    .card-header { padding: 16px; border-bottom: 1px solid var(--erp-border); }
    .card-title { font-size: 18px; font-weight: 700; color: var(--erp-text); margin: 0; }
    .card-subtitle { font-size: 13px; color: var(--erp-muted); margin-top: 4px; }
    .card-body { padding: 16px; }

    .card-metric { background: var(--erp-surface); border: 1px solid var(--erp-border); border-radius: 10px; padding: 16px; display: flex; flex-direction: column; }
    .card-metric-label { font-size: 13px; color: var(--erp-muted); font-weight: 600; }
    .card-metric-value { font-size: 22px; line-height: 1.2; color: var(--erp-text); font-weight: 700; margin-top: 4px; }
    .card-metric-context { font-size: 13px; color: var(--erp-muted); margin-top: 4px; }

    .alert { padding: 12px; border-radius: 8px; font-size: 13px; border: 1px solid var(--erp-border); }
    .alert-success { background: #ecfdf3; color: #166534; border-color: #86efac; }
    .alert-danger { background: #fff1f2; color: #9f1239; border-color: #fecdd3; }
    .alert-info { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }

    .btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; border: 1px solid transparent; text-decoration: none; cursor: pointer; }
    .btn-primary { background: var(--erp-brand); color: #fff; }
    .btn-secondary { background: var(--erp-surface-2); color: var(--erp-text); border-color: var(--erp-border); }

    .badge-status-success, .badge-status-danger, .badge-status-warning, .badge-status-neutral { display: inline-block; padding: 3px 8px; font-size: 11px; font-weight: 700; border-radius: 999px; }
    .badge-status-success { color: #166534; background: #dcfce7; }
    .badge-status-danger { color: #991b1b; background: #fee2e2; }
    .badge-status-warning { color: #92400e; background: #fef3c7; }
    .badge-status-neutral { color: #374151; background: #e5e7eb; }

    .list-item-action { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--erp-text); background: var(--erp-surface-2); text-decoration: none; border: 1px solid var(--erp-border); }
    .list-item-action:hover { filter: brightness(0.98); }
    .list-item-action svg { width: 16px; height: 16px; min-width: 16px; flex: 0 0 16px; display: block; }
    .list-item-condensed { background: var(--erp-surface-2); padding: 12px; border-radius: 10px; border: 1px solid var(--erp-border); }
</style>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

