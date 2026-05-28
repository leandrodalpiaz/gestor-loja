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
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<!-- Filtro de Sessão -->
<div class="card depth-1 mb-8 p-6">
    <div class="card-body">
        <form method="get" action="/orador" class="flex flex-col sm:flex-row sm:items-end sm:gap-4">
            <div class="flex-grow">
                <label for="sessao_id" class="form-label">Sessão em foco</label>
                <select id="sessao_id" name="sessao_id" class="form-select w-full">
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
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-4 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="card-title text-white"><?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?></h2>
                    <p class="card-subtitle mt-1"><?= htmlspecialchars($formatarData($sessaoEmFoco['data_hora_inicio'] ?? null)) ?></p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="badge-status badge-status-secondary">Grau: <?= htmlspecialchars((string) ($sessaoEmFoco['grau_sessao'] ?? '-')) ?></span>
                    <span class="badge-status badge-status-secondary">Tipo: <?= htmlspecialchars((string) ($sessaoEmFoco['tipo_sessao'] ?? '-')) ?></span>
                    <span class="badge-status badge-status-warning">Status: <?= htmlspecialchars((string) ($sessaoEmFoco['status'] ?? '-')) ?></span>
                </div>
            </div>
            <div class="card-body">
                <h3 class="font-bold text-erp-gold uppercase tracking-wider text-xs mb-2">Resumo Ritual</h3>
                <p class="text-sm text-slate-300 whitespace-pre-line leading-relaxed"><?= nl2br(htmlspecialchars((string) ($sessaoEmFoco['ordem_dia'] ?? $sessaoEmFoco['resumo_publico'] ?? 'Sem resumo ritual registrado para esta sessão.'))) ?></p>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-4 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="card-title text-white">Visitantes para leitura</h2>
                    <p class="card-subtitle mt-1">Dados do check-in do Chanceler para apoiar a saudação nominal.</p>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="badge-status badge-status-primary"><?= count($visitantesResumo) ?> visitante(s)</span>
                    <span class="badge-status badge-status-secondary text-[10px]">Atualizado pela Chancelaria</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($visitantesResumo)): ?>
                    <p class="text-center text-slate-400 py-6 text-sm">Nenhum visitante resumido foi registrado para a sessão em foco.</p>
                <?php else: ?>
                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($visitantesResumo as $visitante): ?>
                            <div class="rounded-xl border border-warning/20 bg-warning/5 p-5 text-warning transition hover:border-warning/30 hover:bg-warning/[0.08] shadow-md shadow-warning/5">
                                <p class="font-black text-lg text-white tracking-wide border-b border-warning/10 pb-2"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></p>
                                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs font-sans text-slate-300">
                                    <div>
                                        <dt class="font-bold text-erp-gold uppercase tracking-wider">Grau</dt>
                                        <dd class="text-sm font-semibold mt-0.5 text-white"><?= htmlspecialchars((string) ($visitante['grau'] ?? '-')) ?></dd>
                                    </div>
                                    <div>
                                        <dt class="font-bold text-erp-gold uppercase tracking-wider">Potência</dt>
                                        <dd class="text-sm font-semibold mt-0.5 text-white"><?= htmlspecialchars((string) ($visitante['potencia'] ?? '-')) ?></dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="font-bold text-erp-gold uppercase tracking-wider">Loja</dt>
                                        <dd class="text-sm font-semibold mt-0.5 text-white"><?= htmlspecialchars(trim((string) (($visitante['loja'] ?? '') . (!empty($visitante['numero_loja']) ? ' Nº ' . $visitante['numero_loja'] : ''))) ?: '-') ?></dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="font-bold text-erp-gold uppercase tracking-wider">Oriente</dt>
                                        <dd class="text-sm font-semibold mt-0.5 text-white"><?= htmlspecialchars((string) ($visitante['oriente'] ?? '-')) ?></dd>
                                    </div>
                                </dl>
                                <div class="mt-4 p-3 rounded-lg bg-black/20 border border-warning/10">
                                    <p class="text-[10px] font-bold text-erp-gold uppercase tracking-wider">Apresentação recomendada</p>
                                    <p class="mt-1 text-sm font-medium text-slate-200 italic">"<?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? 'Sem linha resumida.')) ?>"</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card depth-1 p-6">
                <div class="card-header border-b border-white/5 pb-3 mb-4">
                    <h2 class="card-title text-white">Cargos e Composição</h2>
                    <p class="card-subtitle">Ocupação oficial das colunas da sessão.</p>
                </div>
                <div class="card-body space-y-3">
                    <?php if (empty($cargosSessao)): ?>
                        <p class="text-center text-slate-400 py-4 text-sm">Sem composição de cargos capturada.</p>
                    <?php else: ?>
                        <?php foreach ($cargosSessao as $cargo): ?>
                            <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-white truncate"><?= htmlspecialchars((string) ($cargo['cargo_nome'] ?? $cargo['codigo'] ?? 'Cargo')) ?></p>
                                    <p class="text-xs text-slate-400 mt-0.5 truncate"><?= htmlspecialchars((string) ($cargo['ocupante_nome'] ?? 'Sem ocupante')) ?></p>
                                </div>
                                <span class="badge-status badge-status-secondary text-xs flex-shrink-0"><?= htmlspecialchars((string) ($cargo['tipo_ocupacao'] ?? 'regular')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card depth-1 p-6">
                <div class="card-header border-b border-white/5 pb-3 mb-4">
                    <h2 class="card-title text-white">Atividades Registradas</h2>
                    <p class="card-subtitle">Eventos rituais e comunicados para menção.</p>
                </div>
                <div class="card-body space-y-3">
                    <?php if (empty($eventosSessao)): ?>
                        <p class="text-center text-slate-400 py-4 text-sm">Sem eventos registrados para esta sessão.</p>
                    <?php else: ?>
                        <?php foreach ($eventosSessao as $evento): ?>
                            <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-white truncate"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Atividade')) ?></p>
                                    <p class="text-xs text-slate-400 mt-0.5 truncate"><?= htmlspecialchars((string) ($evento['linha'] ?? '')) ?></p>
                                </div>
                                <span class="badge-status badge-status-info text-xs uppercase flex-shrink-0"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Lembretes do Cargo</h2>
            </div>
            <div class="card-body space-y-3">
                <?php foreach ($lembretes as $lembrete): ?>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-xs text-slate-300 leading-relaxed"><?= htmlspecialchars($lembrete) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Uso do Cargo</h2>
            </div>
            <div class="card-body">
                <ul class="list-disc space-y-2 pl-4 text-xs text-slate-400">
                    <li>Revisar a pauta resumida da sessão antes da leitura ritual.</li>
                    <li>Conferir visitantes e cargos ad hoc para menção correta em Loja.</li>
                    <li>Usar os lembretes do painel como roteiro da palavra a bem.</li>
                    <li>Consultar o miniapp quando a leitura precisar ser feita pelo Telegram.</li>
                </ul>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Agenda Futura</h2>
            </div>
            <div class="card-body space-y-3">
                <?php foreach ($sessoes as $sessao): ?>
                    <a href="/orador?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="flex flex-col rounded-xl border border-white/5 bg-white/[0.02] p-4 hover:bg-white/5 transition text-sm">
                        <p class="font-semibold text-white"><?= htmlspecialchars($tituloSessao($sessao)) ?></p>
                        <p class="mt-1 text-xs text-slate-400"><?= htmlspecialchars($formatarData($sessao['data_hora_inicio'] ?? null)) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
