<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

// Configuração do App Shell
$appShellEyebrow = 'Painel Estratégico';
$appShellTitle = 'Venerável Mestre';
$appShellDescription = 'Visão analítica e de governança da Loja. Acompanhe os indicadores chave e tome ações de gestão.';
$appShellActiveHref = '/veneravel';
$appShellActions = [
    ['label' => 'Painel da Loja', 'href' => '/dashboard'],
    ['label' => 'Painel de Votação', 'href' => '/secretaria/votacao', 'primary' => true],
];

// Os dados para o sidebar são passados diretamente para o erp_shell_open
$appShellSidebarSections = [
    [
        'title' => 'Governança',
        'items' => [
            ['label' => 'Painel Estratégico', 'href' => '/veneravel', 'active' => true],
            ['label' => 'Balaústres / Votação', 'href' => '/secretaria/votacao'],
        ],
    ],
    [
        'title' => 'Administrativo',
        'items' => [
            ['label' => 'Secretaria', 'href' => '/secretaria'],
            ['label' => 'Tesouraria', 'href' => '/tesouraria/caixa'],
            ['label' => 'Obreiros', 'href' => '/obreiros'],
        ],
    ],
    [
        'title' => 'Geral',
        'items' => [
            ['label' => 'Meu Painel', 'href' => '/dashboard'],
        ],
    ],
];

require_once __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Mensagens de Feedback -->
    <?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

    <!-- Métricas Principais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card-metric"><p class="card-metric-label">Obreiros Ativos</p><p class="card-metric-value"><?= (int) ($indicadores['obreiros_ativos'] ?? 0) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">Quadro Total</p><p class="card-metric-value"><?= (int) ($indicadores['quadro_total'] ?? 0) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">Frequência Média</p><p class="card-metric-value"><?= htmlspecialchars((string) ($indicadores['frequencia_media'] ?? '0%')) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">Inadimplência</p><p class="card-metric-value"><?= htmlspecialchars((string) ($indicadores['inadimplencia_percentual'] ?? '0%')) ?></p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Coluna Esquerda -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Balaústres e Votações -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Balaústres e Votações</h2>
                    <p class="card-description">Gestão de propostas e deliberações da Loja.</p>
                </div>
                <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300">Aptos para Votação</h3>
                        <div class="mt-3 space-y-3">
                            <?php if (empty($balaustresAptos)): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum balaústre apto no momento.</p>
                            <?php else: ?>
                                <?php foreach ($balaustresAptos as $balaustre): ?>
                                    <div class="list-item">
                                        <span class="font-medium"><?= htmlspecialchars((string) ($balaustre['titulo'] ?? 'Balaústre')) ?></span>
                                        <span class="badge badge-secondary"><?= htmlspecialchars((string) ($balaustre['status'] ?? 'Apto')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($balaustresAptos[0]['id'])): ?>
                                    <form method="POST" action="/veneravel/balaustres/abrir-votacao" class="pt-2">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustresAptos[0]['id'] ?>">
                                        <button type="submit" class="btn btn-primary w-full">Abrir Votação do Próximo</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300">Em Votação</h3>
                        <div class="mt-3 space-y-3">
                            <?php if (empty($balaustresEmVotacao)): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma votação em andamento.</p>
                            <?php else: ?>
                                <?php foreach ($balaustresEmVotacao as $balaustre): ?>
                                    <div class="list-item">
                                        <span class="font-medium"><?= htmlspecialchars((string) ($balaustre['titulo'] ?? 'Balaústre')) ?></span>
                                        <span class="badge badge-warning"><?= htmlspecialchars((string) ($balaustre['status'] ?? 'Em Votação')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($balaustresEmVotacao[0]['id'])): ?>
                                    <form method="POST" action="/veneravel/balaustres/encerrar-votacao" class="pt-2">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustresEmVotacao[0]['id'] ?>">
                                        <button type="submit" class="btn btn-danger w-full">Encerrar Votação</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Próximas Sessões -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Agenda da Loja</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($sessoes)): ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Nenhuma sessão futura na agenda.</p>
                    <?php else: ?>
                        <?php foreach ($sessoes as $sessao): ?>
                            <div class="list-item">
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></p>
                                </div>
                                <span class="badge badge-secondary"><?= htmlspecialchars((string) ($sessao['status'] ?? 'Agendada')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Coluna Direita -->
        <div class="space-y-8">
            <!-- Aniversariantes -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Aniversariantes do Mês</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($aniversariantes)): ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Nenhum aniversariante este mês.</p>
                    <?php else: ?>
                        <?php foreach ($aniversariantes as $aniversariante): ?>
                            <div class="list-item">
                                <span class="font-medium"><?= htmlspecialchars((string) ($aniversariante['nome'] ?? 'Obreiro')) ?></span>
                                <span class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars((string) ($aniversariante['data_nascimento_formatada'] ?? '')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Obreiros em Atraso -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Obreiros em Atraso</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($obreirosEmAtraso)): ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Nenhum obreiro em atraso.</p>
                    <?php else: ?>
                        <?php foreach ($obreirosEmAtraso as $obreiro): ?>
                            <div class="list-item">
                                <span class="font-medium"><?= htmlspecialchars((string) ($obreiro['nome'] ?? 'Obreiro')) ?></span>
                                <span class="badge badge-danger">Atrasado</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-6 border-b border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-xl font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-sm text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-6; }
    .card-metric { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md p-5; }
    .card-metric-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400; }
    .card-metric-value { @apply mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100; }
    .list-item { @apply flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md text-sm; }
</style>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>

