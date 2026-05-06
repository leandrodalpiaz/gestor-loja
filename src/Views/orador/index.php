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
$appShellActions = [
    ['label' => 'Painel', 'href' => '/dashboard'],
    ['label' => 'Chancelaria', 'href' => !empty($sessaoEmFoco['id']) ? '/chanceler/sessao?sessao_id=' . (int) $sessaoEmFoco['id'] : '/chanceler/sessao'],
];

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
                <div>
                    <h2 class="card-title">Visitantes para leitura</h2>
                    <p class="card-description">Dados sincronizados do check-in do Chanceler para apoiar a saudação nominal.</p>
                </div>
                <div class="flex flex-col items-start gap-2 sm:items-end">
                    <span class="badge badge-primary"><?= count($visitantesResumo) ?> visitante(s)</span>
                    <span class="badge badge-secondary text-xs">Atualizado pela Chancelaria</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($visitantesResumo)): ?>
                    <p class="text-center text-gray-500 py-4">Nenhum visitante resumido foi registrado para a sessão em foco.</p>
                <?php else: ?>
                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($visitantesResumo as $visitante): ?>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></p>
                                <dl class="mt-3 grid grid-cols-1 gap-2 text-sm">
                                    <div><dt class="font-semibold text-amber-800">Grau</dt><dd><?= htmlspecialchars((string) ($visitante['grau'] ?? '-')) ?></dd></div>
                                    <div><dt class="font-semibold text-amber-800">Loja</dt><dd><?= htmlspecialchars(trim((string) (($visitante['loja'] ?? '') . (!empty($visitante['numero_loja']) ? ' n. ' . $visitante['numero_loja'] : ''))) ?: '-') ?></dd></div>
                                    <div><dt class="font-semibold text-amber-800">Oriente</dt><dd><?= htmlspecialchars((string) ($visitante['oriente'] ?? '-')) ?></dd></div>
                                    <div><dt class="font-semibold text-amber-800">Potência</dt><dd><?= htmlspecialchars((string) ($visitante['potencia'] ?? '-')) ?></dd></div>
                                </dl>
                                <p class="mt-3 text-sm font-medium"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? 'Sem linha resumida.')) ?></p>
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

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
