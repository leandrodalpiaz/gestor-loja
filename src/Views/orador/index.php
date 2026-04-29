<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$formatarData = static fn(?string $val): string => !empty(trim((string) $val)) ? (new DateTimeImmutable(trim((string) $val)))->format('d/m/Y H:i') : '-';

$tituloSessao = static function (?array $sessao): string {
    if (!$sessao) return 'Nenhuma sessão em foco';
    $titulo = trim((string) ($sessao['titulo'] ?? ''));
    return $titulo !== '' ? $titulo : trim(((string) ($sessao['tipo_sessao'] ?? 'Sessão')) . ' - ' . ((string) ($sessao['grau_sessao'] ?? '')));
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Orador';
$appShellTitle = 'Painel do Orador';
$appShellDescription = 'Pauta ritual, leitura e visitantes para a palavra a bem da ordem.';
$appShellActiveHref = '/orador';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Filtro de Sessão -->
<div class="card mb-8">
    <div class="card-body">
        <form method="get" action="/orador" class="flex flex-col sm:flex-row sm:items-end sm:gap-4">
            <div class="flex-grow">
                <label for="sessao_id" class="form-label">Sessão em foco</label>
                <select id="sessao_id" name="sessao_id" class="form-select">
                    <option value="">Usar próxima sessão publicada</option>
                    <?php foreach ($sessoes as $sessao): ?>
                        <option value="<?= (int) ($sessao['id'] ?? 0) ?>" <?= (int) ($sessaoEmFoco['id'] ?? 0) === (int) ($sessao['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tituloSessao($sessao)) ?> &middot; <?= htmlspecialchars($formatarData($sessao['data_hora_inicio'] ?? null)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-4 sm:mt-0">Atualizar Contexto</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?></h2>
                    <p class="card-description"><?= htmlspecialchars($formatarData($sessaoEmFoco['data_hora_inicio'] ?? null)) ?></p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="badge badge-secondary">Grau: <?= htmlspecialchars((string) ($sessaoEmFoco['grau_sessao'] ?? '-')) ?></span>
                    <span class="badge badge-secondary">Tipo: <?= htmlspecialchars((string) ($sessaoEmFoco['tipo_sessao'] ?? '-')) ?></span>
                    <span class="badge badge-warning">Status: <?= htmlspecialchars((string) ($sessaoEmFoco['status'] ?? '-')) ?></span>
                </div>
            </div>
            <div class="card-body">
                <h3 class="font-semibold mb-2">Resumo Ritual</h3>
                <p class="text-sm whitespace-pre-line"><?= nl2br(htmlspecialchars((string) ($sessaoEmFoco['ordem_dia'] ?? $sessaoEmFoco['resumo_publico'] ?? 'Sem resumo ritual registrado para esta sessão.'))) ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div><h2 class="card-title">Visitantes para Leitura</h2><p class="card-description">Nominata resumida para saudação nominal.</p></div>
                <span class="badge badge-primary"><?= count($visitantesResumo) ?> visitante(s)</span>
            </div>
            <div class="card-body">
                <?php if (empty($visitantesResumo)): ?>
                    <p class="text-center text-gray-500 py-4">Nenhum visitante resumido foi registrado para a sessão em foco.</p>
                <?php else: ?>
                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($visitantesResumo as $visitante): ?>
                            <div class="alert alert-warning !p-4">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></p>
                                <p class="mt-1 text-sm"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? 'Sem linha resumida.')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Cargos e Composição</h2><p class="card-description">Ocupação da sessão.</p></div>
                <div class="card-body space-y-3">
                    <?php if (empty($cargosSessao)): ?>
                        <p class="text-center text-gray-500 py-4">Sem composição de cargos capturada.</p>
                    <?php else: ?>
                        <?php foreach ($cargosSessao as $cargo): ?>
                            <div class="list-item">
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars((string) ($cargo['cargo_nome'] ?? $cargo['codigo'] ?? 'Cargo')) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars((string) ($cargo['ocupante_nome'] ?? 'Sem ocupante')) ?></p>
                                </div>
                                <span class="badge badge-secondary text-xs"><?= htmlspecialchars((string) ($cargo['tipo_ocupacao'] ?? 'regular')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Atividades Registradas</h2><p class="card-description">Eventos para menção.</p></div>
                <div class="card-body space-y-3">
                    <?php if (empty($eventosSessao)): ?>
                        <p class="text-center text-gray-500 py-4">Sem eventos registrados para esta sessão.</p>
                    <?php else: ?>
                        <?php foreach ($eventosSessao as $evento): ?>
                            <div class="list-item">
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Atividade')) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars((string) ($evento['linha'] ?? '')) ?></p>
                                </div>
                                <span class="badge badge-info text-xs uppercase"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></span>
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
            <div class="card-header"><h2 class="card-title">Lembretes do Cargo</h2></div>
            <div class="card-body space-y-3">
                <?php foreach ($lembretes as $lembrete): ?>
                    <div class="list-item-report text-sm"><?= htmlspecialchars($lembrete) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Uso do Cargo</h2></div>
            <div class="card-body">
                <ul class="list-disc space-y-2 pl-5 text-sm text-gray-500">
                    <li>Revisar a pauta resumida da sessão antes da leitura ritual.</li>
                    <li>Conferir visitantes e cargos ad hoc para menção correta em Loja.</li>
                    <li>Usar os lembretes do painel como roteiro da palavra a bem.</li>
                    <li>Consultar o miniapp quando a leitura precisar ser feita pelo Telegram.</li>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Agenda Futura</h2></div>
            <div class="card-body space-y-3">
                <?php foreach ($sessoes as $sessao): ?>
                    <a href="/orador?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="list-item-action">
                        <p class="font-semibold"><?= htmlspecialchars($tituloSessao($sessao)) ?></p>
                        <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($formatarData($sessao['data_hora_inicio'] ?? null)) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-sm text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-5; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-select { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }

    .list-item { @apply flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md text-sm; }
    .list-item-report { @apply p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg; }
    .list-item-action { @apply block bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-700 transition; }

    .alert { @apply px-4 py-3 rounded-lg; }
    .alert-success { @apply bg-green-100 dark:bg-green-900/20 border border-green-400 text-green-700 dark:text-green-300; }
    .alert-danger { @apply bg-red-100 dark:bg-red-900/20 border border-red-400 text-red-700 dark:text-red-300; }
    .alert-warning { @apply bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-400 text-yellow-700 dark:text-yellow-300; }

    .badge { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold; }
    .badge-primary { @apply bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-200; }
    .badge-warning { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-200; }
    .badge-info { @apply bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-200; }
    .badge-secondary { @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200; }
</style>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>

