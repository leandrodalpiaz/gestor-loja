<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

// ConfiguraÃ§Ã£o do App Shell
$appShellEyebrow = 'Painel EstratÃ©gico';
$appShellTitle = 'VenerÃ¡vel Mestre';
$appShellDescription = 'VisÃ£o analÃ­tica e de governanÃ§a da Loja. Acompanhe os indicadores chave e tome aÃ§Ãµes de gestÃ£o.';
$appShellActiveHref = '/veneravel';
$appShellActions = [
    ['label' => 'Painel da Loja', 'href' => '/dashboard'],
    ['label' => 'Painel de VotaÃ§Ã£o', 'href' => '/secretaria/votacao', 'primary' => true],
];

// Os dados para o sidebar sÃ£o passados diretamente para o erp_shell_open
$appShellSidebarSections = [
    [
        'title' => 'GovernanÃ§a',
        'items' => [
            ['label' => 'Painel EstratÃ©gico', 'href' => '/veneravel', 'active' => true],
            ['label' => 'BalaÃºstres / VotaÃ§Ã£o', 'href' => '/secretaria/votacao'],
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

    <!-- MÃ©tricas Principais -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card-metric"><p class="card-metric-label">Obreiros Ativos</p><p class="card-metric-value"><?= (int) ($indicadores['obreiros_ativos'] ?? 0) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">Quadro Total</p><p class="card-metric-value"><?= (int) ($indicadores['quadro_total'] ?? 0) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">FrequÃªncia MÃ©dia</p><p class="card-metric-value"><?= htmlspecialchars((string) ($indicadores['frequencia_media'] ?? '0%')) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">InadimplÃªncia</p><p class="card-metric-value"><?= htmlspecialchars((string) ($indicadores['inadimplencia_percentual'] ?? '0%')) ?></p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Coluna Esquerda -->
        <div class="lg:col-span-2 space-y-8">
            <!-- BalaÃºstres e VotaÃ§Ãµes -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">BalaÃºstres e VotaÃ§Ãµes</h2>
                    <p class="card-description">GestÃ£o de propostas e deliberaÃ§Ãµes da Loja.</p>
                </div>
                <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300">Aptos para VotaÃ§Ã£o</h3>
                        <div class="mt-3 space-y-3">
                            <?php if (empty($balaustresAptos)): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum balaÃºstre apto no momento.</p>
                            <?php else: ?>
                                <?php foreach ($balaustresAptos as $balaustre): ?>
                                    <div class="list-item">
                                        <span class="font-medium"><?= htmlspecialchars((string) ($balaustre['titulo'] ?? 'BalaÃºstre')) ?></span>
                                        <span class="badge badge-secondary"><?= htmlspecialchars((string) ($balaustre['status'] ?? 'Apto')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($balaustresAptos[0]['id'])): ?>
                                    <form method="POST" action="/veneravel/balaustres/abrir-votacao" class="pt-2">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustresAptos[0]['id'] ?>">
                                        <button type="submit" class="btn btn-primary w-full">Abrir VotaÃ§Ã£o do PrÃ³ximo</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300">Em VotaÃ§Ã£o</h3>
                        <div class="mt-3 space-y-3">
                            <?php if (empty($balaustresEmVotacao)): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma votaÃ§Ã£o em andamento.</p>
                            <?php else: ?>
                                <?php foreach ($balaustresEmVotacao as $balaustre): ?>
                                    <div class="list-item">
                                        <span class="font-medium"><?= htmlspecialchars((string) ($balaustre['titulo'] ?? 'BalaÃºstre')) ?></span>
                                        <span class="badge badge-warning"><?= htmlspecialchars((string) ($balaustre['status'] ?? 'Em VotaÃ§Ã£o')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($balaustresEmVotacao[0]['id'])): ?>
                                    <form method="POST" action="/veneravel/balaustres/encerrar-votacao" class="pt-2">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustresEmVotacao[0]['id'] ?>">
                                        <button type="submit" class="btn btn-danger w-full">Encerrar VotaÃ§Ã£o</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PrÃ³ximas SessÃµes -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Agenda da Loja</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($sessoes)): ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Nenhuma sessÃ£o futura na agenda.</p>
                    <?php else: ?>
                        <?php foreach ($sessoes as $sessao): ?>
                            <div class="list-item">
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'SessÃ£o')) ?></p>
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
                <div class="card-header"><h2 class="card-title">Aniversariantes do MÃªs</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($aniversariantes)): ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Nenhum aniversariante este mÃªs.</p>
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

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>


