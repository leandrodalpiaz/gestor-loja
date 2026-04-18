<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
$sessaoEmFormulario = is_array($sessaoRascunho ?? null)
    ? $sessaoRascunho
    : (is_array($sessaoEdicao ?? null) ? $sessaoEdicao : []);
$sessaoDraft = $sessaoEmFormulario;
$modoEdicaoSessao = !is_array($sessaoRascunho ?? null) && is_array($sessaoEdicao ?? null);
$sessaoIdFormulario = (int) ($sessaoDraft['id'] ?? 0);
$labelFormularioSessao = $modoEdicaoSessao ? 'Editar sessao existente' : 'Nova sessao';
$acaoPrimariaSessao = $modoEdicaoSessao ? 'Revisar atualizacao da sessao' : 'Continuar para revisao';
$historicoSessao = is_array($historicoSessao ?? null) ? $historicoSessao : [];
$draftInicio = '';
if (!empty($sessaoDraft['data_hora_inicio'])) {
    try {
        $draftInicio = (new DateTimeImmutable((string) $sessaoDraft['data_hora_inicio']))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d\TH:i');
    } catch (\Throwable $e) {
        $draftInicio = '';
    }
}
$draftFim = '';
if (!empty($sessaoDraft['data_hora_fim'])) {
    try {
        $draftFim = (new DateTimeImmutable((string) $sessaoDraft['data_hora_fim']))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('Y-m-d\TH:i');
    } catch (\Throwable $e) {
        $draftFim = '';
    }
}
$historiaLoja = trim((string) ($configuracaoLoja['historia_loja'] ?? ''));
if ($historiaLoja !== '') {
    if (function_exists('mb_strimwidth')) {
        $historiaLoja = mb_strimwidth($historiaLoja, 0, 2200, '...');
    } else {
        $historiaLoja = strlen($historiaLoja) > 2200 ? substr($historiaLoja, 0, 2197) . '...' : $historiaLoja;
    }
}

$formatarDataAgenda = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return 'Data a definir';
    }

    try {
        return (new DateTimeImmutable($valor))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d/m/Y \à\s H:i');
    } catch (\Throwable $e) {
        return $valor;
    }
};

$badgeStatusSessao = static function (?string $status): string {
    return match (strtolower(trim((string) $status))) {
        'publicada', 'confirmada', 'ativa', 'alterada' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'planejada' => 'border-amber-200 bg-amber-50 text-amber-800',
        'cancelada' => 'border-rose-200 bg-rose-50 text-rose-800',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };
};
?>
<?php
$erpPageTitle = 'Secretaria - Gestor de Loja';
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Centro operacional do Secretario';
$appShellDescription = 'Sessões, publicações, trabalhos da ordem do dia e gestão cadastral dos membros em um fluxo único de secretaria.';
$appShellActiveHref = '/secretaria';
$appShellActions = [
    ['label' => 'Cadastros dos membros', 'href' => '/obreiros'],
    ['label' => 'Votacao', 'href' => '/secretaria/votacao'],
    ['label' => 'Relatorio anual', 'href' => '/secretaria/relatorio-anual'],
];
$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Painel da Secretaria', 'href' => '/secretaria'],
            ['label' => 'Votacao de balaustre', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatorio anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
        ],
    ],
    [
        'title' => 'Navegacao',
        'items' => [
            ['label' => 'Dashboard', 'href' => '/dashboard'],
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
        ],
    ],
];
require __DIR__ . '/../partials/erp_head.php';
?>
<?php require __DIR__ . '/../partials/erp_shell_open.php'; ?>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <div class="grid gap-4 md:grid-cols-5 mb-8">
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-700">Obreiros ativos</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) $resumo['obreiros_ativos'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-700">Sessoes futuras</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) $resumo['sessoes_futuras'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-700">Trabalhos pendentes</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) $resumo['trabalhos_pendentes'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-700">Publicacoes em rascunho</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) $resumo['publicacoes_rascunho'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-700">Balaustres aptos</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) $resumo['balaustres_aptos'] ?></div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr] mb-8">
            <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.24em] text-erp-gold">Cadastros</div>
                        <h2 class="font-sans text-xl text-erp-navy mt-2">Saude cadastral da Secretaria</h2>
                        <p class="text-sm text-slate-700 mt-2">Resumo rapido para saneamento do quadro e preparo dos relatorios.</p>
                    </div>
                    <a href="/obreiros" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">Abrir central de obreiros</a>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-700">Total</div>
                        <div class="mt-1 text-2xl font-semibold text-erp-navy"><?= (int) ($resumoCadastros['total'] ?? 0) ?></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-700">No quadro</div>
                        <div class="mt-1 text-2xl font-semibold text-erp-navy"><?= (int) ($resumoCadastros['ativos'] ?? 0) ?></div>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-amber-800">Com alerta</div>
                        <div class="mt-1 text-2xl font-semibold text-amber-900"><?= (int) ($resumoCadastros['com_alerta'] ?? 0) ?></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs uppercase tracking-wide text-slate-700">Com bot</div>
                        <div class="mt-1 text-2xl font-semibold text-erp-navy"><?= (int) ($resumoCadastros['com_telegram'] ?? 0) ?></div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="/obreiros?alerta=cadastro" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100">Ver alertas cadastrais</a>
                    <a href="/obreiros/novo" class="rounded-lg border border-erp-navy px-4 py-2 text-sm font-medium text-erp-navy hover:bg-erp-navy hover:text-white">Novo obreiro</a>
                </div>
            </section>

            <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.24em] text-erp-gold">Sessao em foco</div>
                        <h2 class="font-sans text-xl text-erp-navy mt-2">Resumo operacional</h2>
                        <p class="text-sm text-slate-700 mt-2">Confirmados, ausencias e agape consolidados na mesma visao da Secretaria.</p>
                    </div>
                </div>

                <form method="GET" action="/secretaria" class="mt-4">
                    <label class="block text-sm font-medium mb-1">Sessao para acompanhamento</label>
                    <div class="flex gap-2">
                        <select name="sessao_resumo" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= (int) ($sessaoResumo['id'] ?? 0) === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-sm font-medium text-white">Atualizar</button>
                    </div>
                </form>

                <?php if (!empty($sessaoResumo)): ?>
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="font-semibold text-erp-navy"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?: (($sessaoResumo['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessaoResumo['grau_sessao'] ?? '')))) ?></div>
                        <div class="mt-1 text-sm text-slate-700">
                            <?= htmlspecialchars((string) ($sessaoResumo['data_hora_inicio'] ?? '')) ?>
                            ·
                            Status: <?= htmlspecialchars((string) ($sessaoResumo['status'] ?? '')) ?>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs uppercase tracking-wide text-slate-700">Confirmados</div>
                            <div class="mt-1 text-2xl font-semibold text-erp-navy"><?= (int) ($sessaoResumo['total_confirmados'] ?? 0) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs uppercase tracking-wide text-slate-700">Ausentes</div>
                            <div class="mt-1 text-2xl font-semibold text-erp-navy"><?= (int) ($sessaoResumo['total_ausentes'] ?? 0) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-xs uppercase tracking-wide text-slate-700">Agape</div>
                            <div class="mt-1 text-2xl font-semibold text-erp-navy"><?= (int) ($sessaoResumo['total_agape'] ?? 0) ?></div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-sm font-semibold text-slate-700">Confirmados da sessao</div>
                            <div class="mt-3 space-y-2">
                                <?php foreach ($confirmadosSessaoResumo as $confirmado): ?>
                                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                        <span><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Irmao')) ?></span>
                                        <span class="text-slate-700"><?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($confirmadosSessaoResumo === []): ?>
                                    <div class="rounded-lg border border-dashed border-slate-300 px-3 py-3 text-sm text-slate-700">Sem confirmados nesta sessao.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="text-sm font-semibold text-slate-700">Participantes do agape</div>
                            <div class="mt-3 space-y-2">
                                <?php foreach ($participantesAgapeResumo as $participanteAgape): ?>
                                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                        <span><?= htmlspecialchars((string) ($participanteAgape['nome'] ?? 'Irmao')) ?></span>
                                        <span class="text-slate-700"><?= htmlspecialchars((string) ($participanteAgape['cim'] ?? '-')) ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($participantesAgapeResumo === []): ?>
                                    <div class="rounded-lg border border-dashed border-slate-300 px-3 py-3 text-sm text-slate-700">Sem participantes confirmados para o agape.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Nenhuma sessao disponivel para resumo operacional.</div>
                <?php endif; ?>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr] mb-8">
            <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.24em] text-erp-gold">Identidade da Loja</div>
                        <h2 class="font-sans text-2xl text-erp-navy mt-2">
                            <?= htmlspecialchars(trim((string) (($configuracaoLoja['nome_loja'] ?? '') . ' Nº ' . ($configuracaoLoja['numero_loja'] ?? '')), " Nº")) ?>
                        </h2>
                        <p class="text-sm text-slate-700 mt-2">Base institucional para relatórios, Secretaria e leitura histórica da oficina.</p>
                    </div>
                    <?php if (!empty($configuracaoLoja['potencia_sigla']) || !empty($configuracaoLoja['potencia_nome'])): ?>
                        <div class="rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-amber-800">
                            <?= htmlspecialchars((string) (($configuracaoLoja['potencia_sigla'] ?? '') !== '' ? $configuracaoLoja['potencia_sigla'] : ($configuracaoLoja['potencia_nome'] ?? ''))) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Oriente: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracaoLoja['oriente'] ?? '-')) ?></strong></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Rito: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracaoLoja['rito'] ?? '-')) ?></strong></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Fundação: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracaoLoja['data_fundacao'] ?? '-')) ?></strong></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Instalação: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracaoLoja['data_instalacao'] ?? '-')) ?></strong></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Templo: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracaoLoja['nome_templo'] ?? '-')) ?></strong></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Reuniões: <strong class="text-slate-800"><?= htmlspecialchars(trim((string) (($configuracaoLoja['dia_semana_reuniao'] ?? '') . ' • ' . ($configuracaoLoja['horario_reuniao'] ?? '') . ' • ' . ($configuracaoLoja['periodicidade_reuniao'] ?? '')), ' •')) ?></strong></div>
                </div>
            </section>

            <section class="rounded-2xl bg-[linear-gradient(180deg,#fffdf7,#f4efe4)] border border-amber-200 shadow-sm p-6">
                <div class="text-xs uppercase tracking-[0.24em] text-erp-gold">História da Loja</div>
                <h2 class="font-sans text-2xl text-erp-navy mt-2">Renascença em perspectiva</h2>
                <p class="mt-4 text-sm leading-7 text-slate-700 whitespace-pre-line">
                    <?= htmlspecialchars($historiaLoja) ?>
                </p>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <section class="space-y-6">
                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-sans text-xl text-erp-navy">Proxima sessao e agenda</h2>
                            <p class="text-sm text-slate-700">Base operacional das sessoes sob responsabilidade da Secretaria.</p>
                        </div>
                    </div>

                    <?php if ($proximaSessao): ?>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 mb-4">
                            <div class="text-sm text-slate-700">Proxima sessao oficial</div>
                            <div class="mt-1 font-semibold text-erp-navy"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></div>
                            <div class="text-sm text-slate-700 mt-1"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($resumoRascunhoSessao): ?>
                        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.24em] text-erp-gold">Revisao final</div>
                                    <h3 class="mt-2 font-sans text-lg text-erp-navy">Resumo pronto para publicacao</h3>
                                </div>
                                <?php if ($sessaoDuplicada): ?>
                                    <div class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                                        Sessao semelhante encontrada no mesmo dia/horario
                                    </div>
                                <?php endif; ?>
                            </div>
                            <pre class="mt-4 whitespace-pre-wrap rounded-xl bg-white p-4 text-sm leading-6 text-slate-700"><?= htmlspecialchars($resumoRascunhoSessao) ?></pre>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <?php foreach ($acoesConfirmacaoRascunho as $acaoRascunho): ?>
                                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs text-slate-700"><?= htmlspecialchars((string) ($acaoRascunho['label'] ?? '')) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="/secretaria/sessoes/publicar-rascunho">
                                    <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-sm font-medium text-white">Confirmar publicacao</button>
                                </form>
                                <form method="POST" action="/secretaria/sessoes/cancelar-rascunho">
                                    <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cancelar rascunho</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/secretaria/sessoes/salvar" class="grid gap-4 md:grid-cols-2">
                        <?php if ($sessaoIdFormulario > 0): ?>
                            <input type="hidden" name="sessao_id" value="<?= $sessaoIdFormulario ?>">
                        <?php endif; ?>
                        <div class="md:col-span-2 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="text-xs uppercase tracking-[0.24em] text-erp-gold"><?= htmlspecialchars($labelFormularioSessao) ?></div>
                                <div class="text-sm text-slate-700">
                                    <?= $modoEdicaoSessao ? 'Os dados abaixo foram carregados de uma sessao existente. Revise e confirme a atualizacao.' : 'Preencha os dados da nova sessao e siga para a revisao final.' ?>
                                </div>
                            </div>
                            <?php if ($modoEdicaoSessao): ?>
                                <a href="/secretaria" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700">Cancelar edicao</a>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Titulo da sessao</label>
                            <input type="text" name="titulo" required value="<?= htmlspecialchars((string) ($sessaoDraft['titulo'] ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Data e hora</label>
                            <input type="datetime-local" name="data_hora_inicio" required value="<?= htmlspecialchars($draftInicio) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Encerramento previsto</label>
                            <input type="datetime-local" name="data_hora_fim" value="<?= htmlspecialchars($draftFim) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Grau da sessao</label>
                            <select name="grau_sessao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Outro'] as $grauOpcao): ?>
                                    <option value="<?= htmlspecialchars($grauOpcao) ?>" <?= (($sessaoDraft['grau_personalizado'] ?? null) && ($sessaoDraft['grau_sessao'] ?? '') === $sessaoDraft['grau_personalizado'] && $grauOpcao === 'Outro') || (($sessaoDraft['grau_sessao'] ?? '') === $grauOpcao) ? 'selected' : '' ?>><?= htmlspecialchars($grauOpcao) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Grau livre, se necessario</label>
                            <input type="text" name="grau_personalizado" value="<?= htmlspecialchars((string) ($sessaoDraft['grau_personalizado'] ?? '')) ?>" placeholder="Somente se o grau for Outro" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo principal</label>
                            <select name="tipo_sessao_principal" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="economica" <?= (($sessaoDraft['tipo_sessao_principal'] ?? 'economica') === 'economica') ? 'selected' : '' ?>>Economica</option>
                                <option value="magna" <?= (($sessaoDraft['tipo_sessao_principal'] ?? '') === 'magna') ? 'selected' : '' ?>>Magna</option>
                                <option value="outra" <?= (($sessaoDraft['tipo_sessao_principal'] ?? '') === 'outra') ? 'selected' : '' ?>>Outra</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Subtipo</label>
                            <select name="tipo_sessao_subtipo" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <?php
                                $subtiposSessao = [
                                    'economica_1' => 'Economica de 1 Grau',
                                    'economica_2' => 'Economica de 2 Grau',
                                    'economica_3' => 'Economica de 3 Grau',
                                    'magna_iniciacao' => 'Magna de Iniciacao',
                                    'magna_elevacao' => 'Magna de Elevacao',
                                    'magna_exaltacao' => 'Magna de Exaltacao',
                                    'magna_instalacao' => 'Magna de Instalacao',
                                    'outra' => 'Outra',
                                ];
                                foreach ($subtiposSessao as $valorSubtipo => $labelSubtipo):
                                ?>
                                    <option value="<?= htmlspecialchars($valorSubtipo) ?>" <?= (($sessaoDraft['tipo_sessao_subtipo'] ?? '') === $valorSubtipo) ? 'selected' : '' ?>><?= htmlspecialchars($labelSubtipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Tipo livre, se necessario</label>
                            <input type="text" name="tipo_sessao_personalizado" value="<?= htmlspecialchars((string) ($sessaoDraft['tipo_sessao_personalizado'] ?? '')) ?>" placeholder="Usar quando o tipo ou subtipo nao estiver na lista" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Traje</label>
                            <select name="traje_tipo" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="maconico" <?= (($sessaoDraft['traje_tipo'] ?? 'maconico') === 'maconico') ? 'selected' : '' ?>>Maconico</option>
                                <option value="livre" <?= (($sessaoDraft['traje_tipo'] ?? '') === 'livre') ? 'selected' : '' ?>>Livre</option>
                                <option value="outro" <?= (($sessaoDraft['traje_tipo'] ?? '') === 'outro') ? 'selected' : '' ?>>Outro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Traje livre, se necessario</label>
                            <input type="text" name="traje_personalizado" value="<?= htmlspecialchars((string) ($sessaoDraft['traje_personalizado'] ?? '')) ?>" placeholder="Somente se o traje for Outro" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Agape</label>
                            <select name="agape_modalidade" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="nao_havera" <?= (($sessaoDraft['agape_modalidade'] ?? 'nao_havera') === 'nao_havera') ? 'selected' : '' ?>>Nao havera</option>
                                <option value="gratuito" <?= (($sessaoDraft['agape_modalidade'] ?? '') === 'gratuito') ? 'selected' : '' ?>>Sim (gratuito)</option>
                                <option value="pago" <?= (($sessaoDraft['agape_modalidade'] ?? '') === 'pago') ? 'selected' : '' ?>>Sim (pago)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Modelo financeiro do agape</label>
                            <select name="agape_modelo_financeiro" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="oficial_loja" <?= (($sessaoDraft['agape_modelo_financeiro'] ?? 'oficial_loja') === 'oficial_loja') ? 'selected' : '' ?>>Oficial da Loja</option>
                                <option value="particular" <?= (($sessaoDraft['agape_modelo_financeiro'] ?? '') === 'particular') ? 'selected' : '' ?>>Particular entre participantes</option>
                                <option value="misto" <?= (($sessaoDraft['agape_modelo_financeiro'] ?? '') === 'misto') ? 'selected' : '' ?>>Misto (Loja + particular)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Valor de referencia do agape (opcional)</label>
                            <input type="text" name="agape_valor" value="<?= htmlspecialchars((string) ($sessaoDraft['agape_valor'] ?? '')) ?>" placeholder="Pode ficar em branco e ser definido depois pelo Mestre de Banquetes" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Gestao de referencia</label>
                            <input type="text" name="gestao_referencia" value="<?= htmlspecialchars((string) ($sessaoDraft['gestao_referencia'] ?? '')) ?>" placeholder="Ex.: 2026/2027" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Natureza da sessao</label>
                            <select name="natureza_sessao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="ordinaria" <?= (($sessaoDraft['natureza_sessao'] ?? 'ordinaria') === 'ordinaria') ? 'selected' : '' ?>>Ordinaria</option>
                                <option value="extraordinaria" <?= (($sessaoDraft['natureza_sessao'] ?? '') === 'extraordinaria') ? 'selected' : '' ?>>Extraordinaria</option>
                                <option value="magna" <?= (($sessaoDraft['natureza_sessao'] ?? '') === 'magna') ? 'selected' : '' ?>>Magna</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Formato</label>
                            <select name="formato_sessao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="templo" <?= (($sessaoDraft['formato_sessao'] ?? 'templo') === 'templo') ? 'selected' : '' ?>>Templo</option>
                                <option value="a_campo" <?= (($sessaoDraft['formato_sessao'] ?? '') === 'a_campo') ? 'selected' : '' ?>>A campo</option>
                                <option value="publica" <?= (($sessaoDraft['formato_sessao'] ?? '') === 'publica') ? 'selected' : '' ?>>Publica</option>
                                <option value="branca" <?= (($sessaoDraft['formato_sessao'] ?? '') === 'branca') ? 'selected' : '' ?>>Branca</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Finalidade ritual</label>
                            <select name="finalidade_ritual" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="economica" <?= (($sessaoDraft['finalidade_ritual'] ?? 'economica') === 'economica') ? 'selected' : '' ?>>Economica</option>
                                <option value="iniciacao" <?= (($sessaoDraft['finalidade_ritual'] ?? '') === 'iniciacao') ? 'selected' : '' ?>>Iniciacao</option>
                                <option value="elevacao" <?= (($sessaoDraft['finalidade_ritual'] ?? '') === 'elevacao') ? 'selected' : '' ?>>Elevacao</option>
                                <option value="exaltacao" <?= (($sessaoDraft['finalidade_ritual'] ?? '') === 'exaltacao') ? 'selected' : '' ?>>Exaltacao</option>
                                <option value="instalacao" <?= (($sessaoDraft['finalidade_ritual'] ?? '') === 'instalacao') ? 'selected' : '' ?>>Instalacao</option>
                                <option value="outra" <?= (($sessaoDraft['finalidade_ritual'] ?? '') === 'outra') ? 'selected' : '' ?>>Outra</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Templo ou local</label>
                            <input type="text" name="templo_local" value="<?= htmlspecialchars((string) ($sessaoDraft['templo_local'] ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Ordem do dia / observacoes</label>
                            <textarea name="ordem_dia" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= htmlspecialchars((string) ($sessaoDraft['ordem_dia'] ?? '')) ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Observacao interna</label>
                            <textarea name="observacao_interna" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= htmlspecialchars((string) ($sessaoDraft['observacao_interna'] ?? '')) ?></textarea>
                        </div>
                        <label class="md:col-span-2 inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="sessao_branca" value="1" <?= !empty($sessaoDraft['sessao_branca']) ? 'checked' : '' ?>>
                            Sessao branca / festiva
                        </label>
                        <label class="md:col-span-2 inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="sessao_a_campo" value="1" <?= !empty($sessaoDraft['sessao_a_campo']) ? 'checked' : '' ?>>
                            Sessao a campo
                        </label>
                        <label class="md:col-span-2 inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="conta_relatorio_potencia" value="1" <?= !array_key_exists('conta_relatorio_potencia', $sessaoDraft) || !empty($sessaoDraft['conta_relatorio_potencia']) ? 'checked' : '' ?>>
                            Conta no relatorio oficial da potencia
                        </label>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Observacao para relatorio</label>
                            <textarea name="observacao_relatorio" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= htmlspecialchars((string) ($sessaoDraft['observacao_relatorio'] ?? '')) ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-white font-medium"><?= htmlspecialchars($acaoPrimariaSessao) ?></button>
                        </div>
                    </form>

                    <div class="mt-6">
                        <div class="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <?php foreach ($sessoes as $sessao): ?>
                                <?php
                                $statusSessao = (string) ($sessao['status'] ?? '');
                                $tituloSessao = (string) ($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? '')));
                                ?>
                                <article class="rounded-2xl border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#f8fafc)] p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-erp-gold">Sessao oficial</div>
                                            <h3 class="mt-2 text-base font-semibold text-erp-navy"><?= htmlspecialchars($tituloSessao) ?></h3>
                                        </div>
                                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold <?= $badgeStatusSessao($statusSessao) ?>">
                                            <?= htmlspecialchars($statusSessao !== '' ? $statusSessao : 'planejada') ?>
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                            <div class="text-xs uppercase tracking-wide text-slate-700">Data</div>
                                            <div class="mt-1 text-sm font-medium text-slate-800"><?= htmlspecialchars($formatarDataAgenda((string) ($sessao['data_hora_inicio'] ?? ''))) ?></div>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                            <div class="text-xs uppercase tracking-wide text-slate-700">Confirmados</div>
                                            <div class="mt-1 text-sm font-medium text-slate-800">
                                                <?= (int) ($sessao['total_confirmados'] ?? 0) ?> irmão(s)
                                                <?php if ((int) ($sessao['total_agape'] ?? 0) > 0): ?>
                                                    · <?= (int) ($sessao['total_agape'] ?? 0) ?> com ágape
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="/secretaria?editar_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="inline-flex rounded-lg border border-erp-navy px-3 py-2 text-xs font-medium text-erp-navy hover:bg-erp-navy hover:text-white">
                                            Editar
                                        </a>
                                        <?php if (in_array($statusSessao, ['planejada', 'alterada'], true)): ?>
                                            <form method="POST" action="/secretaria/sessoes/publicar" onsubmit="return confirm('Deseja publicar esta sessao agora?');">
                                                <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                                <button type="submit" class="inline-flex rounded-lg border border-amber-300 px-3 py-2 text-xs font-medium text-amber-800 hover:bg-amber-50">
                                                    Publicar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($statusSessao !== 'cancelada'): ?>
                                            <form method="POST" action="/secretaria/sessoes/cancelar" onsubmit="return confirm('Deseja cancelar esta sessao?');">
                                                <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                                <button type="submit" class="inline-flex rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                                    Cancelar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="/secretaria/sessoes/reabrir" onsubmit="return confirm('Deseja reabrir esta sessao?');">
                                                <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                                <button type="submit" class="inline-flex rounded-lg border border-emerald-300 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                                    Reabrir
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="/secretaria?historico_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="inline-flex rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                            Historico
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>

                            <?php if ($sessoes === []): ?>
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-700 md:col-span-2 xl:col-span-3">
                                    Nenhuma sessao futura cadastrada. Use o formulario acima para publicar a agenda oficial da Loja.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="hidden overflow-x-auto xl:block">
                        <table class="w-full text-sm">
                            <thead class="text-left text-slate-700">
                                <tr>
                                    <th class="py-2">Sessao</th>
                                    <th class="py-2">Data</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessoes as $sessao): ?>
                                    <?php $statusSessao = (string) ($sessao['status'] ?? ''); ?>
                                    <tr class="border-t border-slate-100">
                                        <td class="py-2"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></td>
                                        <td class="py-2"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></td>
                                        <td class="py-2"><?= htmlspecialchars($statusSessao) ?></td>
                                        <td class="py-2">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="/secretaria?editar_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="inline-flex rounded-md border border-erp-navy px-3 py-1 text-xs font-medium text-erp-navy hover:bg-erp-navy hover:text-white">
                                                    Editar
                                                </a>
                                                <?php if (in_array($statusSessao, ['planejada', 'alterada'], true)): ?>
                                                    <form method="POST" action="/secretaria/sessoes/publicar" onsubmit="return confirm('Deseja publicar esta sessao agora?');">
                                                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                                        <button type="submit" class="inline-flex rounded-md border border-amber-300 px-3 py-1 text-xs font-medium text-amber-800 hover:bg-amber-50">
                                                            Publicar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($statusSessao !== 'cancelada'): ?>
                                                    <form method="POST" action="/secretaria/sessoes/cancelar" onsubmit="return confirm('Deseja cancelar esta sessao?');">
                                                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                                        <button type="submit" class="inline-flex rounded-md border border-rose-300 px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                                            Cancelar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="/secretaria/sessoes/reabrir" onsubmit="return confirm('Deseja reabrir esta sessao?');">
                                                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                                        <button type="submit" class="inline-flex rounded-md border border-emerald-300 px-3 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                                            Reabrir
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="/secretaria?historico_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="inline-flex rounded-md border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                    Historico
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <?php if (!empty($sessaoHistorico)): ?>
                        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.24em] text-erp-gold">Historico operacional</div>
                                    <h3 class="mt-1 text-lg font-semibold text-erp-navy">
                                        <?= htmlspecialchars((string) ($sessaoHistorico['titulo'] ?: (($sessaoHistorico['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessaoHistorico['grau_sessao'] ?? '')))) ?>
                                    </h3>
                                </div>
                                <a href="/secretaria" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700">Fechar historico</a>
                            </div>

                            <div class="mt-4 space-y-3">
                                <?php foreach ($historicoSessao as $itemHistorico): ?>
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                            <div class="font-medium text-slate-800"><?= htmlspecialchars((string) ($itemHistorico['acao'] ?? 'acao')) ?></div>
                                            <div class="text-xs text-slate-700">
                                                <?= htmlspecialchars((string) ($itemHistorico['autor_nome'] ?? 'Sistema')) ?>
                                                ·
                                                <?= htmlspecialchars((string) ($itemHistorico['created_at'] ?? '')) ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($itemHistorico['observacao'])): ?>
                                            <p class="mt-2 text-sm text-slate-700"><?= htmlspecialchars((string) $itemHistorico['observacao']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($historicoSessao === []): ?>
                                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Ainda nao ha historico registrado para esta sessao.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-sans text-xl text-erp-navy">Trabalhos e pecas de arquitetura</h2>
                    <p class="text-sm text-slate-700 mb-4">Registro dos trabalhos apresentados em ordem do dia, com controle do envio em PDF para a Potencia e acervo futuro da Loja.</p>
                    <form method="POST" action="/secretaria/trabalhos/salvar" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Sessao</label>
                            <select name="sessao_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecione</option>
                                <?php foreach ($sessoes as $sessao): ?>
                                    <option value="<?= (int) $sessao['id'] ?>"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo</label>
                            <select name="tipo_trabalho" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="peca_arquitetura">Peca de arquitetura</option>
                                <option value="trabalho">Trabalho</option>
                                <option value="prancha">Prancha</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Titulo</label>
                            <input type="text" name="titulo" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Autor do quadro</label>
                            <select name="autor_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecionar membro</option>
                                <?php foreach ($obreiros as $obreiro): ?>
                                    <option value="<?= htmlspecialchars($obreiro['id']) ?>"><?= htmlspecialchars($obreiro['nome_historico'] ?: $obreiro['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Autor livre</label>
                            <input type="text" name="autor_nome_livre" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Local do PDF</label>
                            <input type="text" name="arquivo_pdf_path" placeholder="Ex.: /documentos/trabalhos/arquivo.pdf" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Status do envio</label>
                            <select name="status_envio_potencia" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="pendente">Pendente</option>
                                <option value="enviado">Enviado</option>
                                <option value="dispensado">Dispensado</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Observacao</label>
                            <textarea name="observacao" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-white font-medium">Registrar trabalho</button>
                        </div>
                    </form>

                    <div class="mt-6 space-y-3">
                        <?php foreach ($trabalhos as $trabalho): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="font-medium text-erp-navy"><?= htmlspecialchars($trabalho['titulo']) ?></div>
                                <div class="text-sm text-slate-700 mt-1">
                                    <?= htmlspecialchars($trabalho['sessao_titulo'] ?: (string) ($trabalho['data_hora_inicio'] ?? '')) ?>
                                    · <?= htmlspecialchars($trabalho['autor_nome'] ?: ($trabalho['autor_nome_livre'] ?? 'Autor nao informado')) ?>
                                </div>
                                <div class="text-xs text-slate-700 mt-1">Status Potencia: <?= htmlspecialchars((string) $trabalho['status_envio_potencia']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-sans text-xl text-erp-navy">Balaustre e votacao</h2>
                    <p class="text-sm text-slate-700 mb-4">
                        O Secretario prepara o balaustre e deixa apto para votacao. A abertura e o encerramento da votacao ficam sob atribuicao do Veneravel Mestre.
                    </p>

                    <?php if ($podeOperarSecretaria): ?>
                    <form method="POST" action="/secretaria/balaustres/salvar" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Sessao</label>
                            <select name="sessao_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecione</option>
                                <?php foreach ($sessoes as $sessao): ?>
                                    <option value="<?= (int) $sessao['id'] ?>"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Numero do balaustre</label>
                            <input type="text" name="numero_balaustre" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Texto final revisado</label>
                            <textarea name="texto_final" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Palavra a bem da ordem (visitantes)</h3>
                            <p class="text-xs text-slate-700 mb-3">
                                Use as lojas frequentes como apoio de preenchimento. Registre as apresentacoes e agradecimentos dos visitantes.
                            </p>
                            <div class="mb-3">
                                <label class="block text-xs font-medium mb-1">Lojas frequentes (uma por linha ou separadas por virgula)</label>
                                <textarea name="lojas_visitantes_frequentes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= htmlspecialchars(implode("\n", $lojasVisitantesFrequentes ?? [])) ?></textarea>
                            </div>
                            <datalist id="lojas-frequentes-sugestoes">
                                <?php foreach (($lojasVisitantesFrequentes ?? []) as $lojaSugestao): ?>
                                    <option value="<?= htmlspecialchars((string) $lojaSugestao) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <div class="space-y-2">
                                <?php for ($linhaVisitante = 0; $linhaVisitante < 4; $linhaVisitante++): ?>
                                    <div class="grid gap-2 md:grid-cols-6">
                                        <input type="text" name="palavra_visitante_nome[]" placeholder="Nome do visitante" class="rounded-lg border border-slate-300 px-2 py-2 text-sm md:col-span-2">
                                        <input type="text" name="palavra_visitante_loja[]" placeholder="Loja de origem" list="lojas-frequentes-sugestoes" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_oriente[]" placeholder="Oriente" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_potencia[]" placeholder="Potencia" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_grau[]" placeholder="Grau" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_dia_reuniao[]" placeholder="Dia da reuniao" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_fala[]" placeholder="Resumo da fala/impressao" class="rounded-lg border border-slate-300 px-2 py-2 text-sm md:col-span-6">
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Nominata de cargos da sessao</h3>
                            <p class="text-xs text-slate-700 mb-3">
                                O sistema assume <strong>regular</strong> quando o ocupante bate com o titular oficial da gestao. Se divergir, salva automaticamente como <strong>ad hoc</strong>.
                            </p>
                            <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                <?php foreach (($cargosSessaoBase ?? []) as $cargoSessao): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <input type="hidden" name="cargo_sessao_codigo[]" value="<?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?>">
                                        <input type="hidden" name="cargo_sessao_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['label'] ?? '')) ?>">
                                        <div class="md:col-span-3 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700">
                                            <div class="font-semibold"><?= htmlspecialchars((string) ($cargoSessao['label'] ?? 'Cargo')) ?></div>
                                            <div class="text-slate-700"><?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?></div>
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="text" name="cargo_sessao_titular_oficial[]" value="<?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '')) ?>" placeholder="Titular oficial" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-4">
                                            <input type="text" name="cargo_sessao_ocupante_nome[]" placeholder="Quem ocupou na sessao" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="cargo_sessao_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Saco de propostas: visitas a outras Lojas</h3>
                            <p class="text-xs text-slate-700 mb-3">
                                Registre aqui quando algum membro do quadro da Loja informar visita realizada a outra Loja.
                            </p>
                            <div class="space-y-2">
                                <?php for ($linhaVisita = 0; $linhaVisita < 4; $linhaVisita++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-4">
                                            <select name="visita_externa_obreiro_id[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                                <option value="">Selecione o membro do quadro</option>
                                                <?php foreach ($obreiros as $obreiro): ?>
                                                    <option value="<?= htmlspecialchars((string) $obreiro['id']) ?>"><?= htmlspecialchars($obreiro['nome_historico'] ?: $obreiro['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="visita_externa_obreiro_nome[]" value="">
                                        </div>
                                        <div class="md:col-span-4">
                                            <input type="text" name="visita_externa_loja[]" placeholder="Loja visitada" list="lojas-frequentes-sugestoes" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="visita_externa_potencia[]" placeholder="Potencia" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="visita_externa_oriente[]" placeholder="Oriente" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="date" name="visita_externa_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="visita_externa_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Congressos realizados</h3>
                            <div class="space-y-2">
                                <?php for ($linhaCongresso = 0; $linhaCongresso < 3; $linhaCongresso++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-5">
                                            <input type="text" name="congresso_titulo[]" placeholder="Titulo do congresso" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="text" name="congresso_promotor[]" placeholder="Promotor/organizacao" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="date" name="congresso_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="congresso_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Palestras realizadas</h3>
                            <div class="space-y-2">
                                <?php for ($linhaPalestra = 0; $linhaPalestra < 4; $linhaPalestra++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-5">
                                            <input type="text" name="palestra_titulo[]" placeholder="Tema ou titulo da palestra" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="text" name="palestra_palestrante[]" placeholder="Palestrante" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="date" name="palestra_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="palestra_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Eventos promovidos pela Loja</h3>
                            <div class="space-y-2">
                                <?php for ($i = 0; $i < 3; $i++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-4"><input type="text" name="evento_promovido_titulo[]" placeholder="Titulo" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="date" name="evento_promovido_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="evento_promovido_local[]" placeholder="Local" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="evento_promovido_loja[]" placeholder="Loja" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="evento_promovido_oriente[]" placeholder="Oriente" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-3"><input type="text" name="evento_promovido_promotor[]" placeholder="Promotor" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-5"><input type="text" name="evento_promovido_descricao[]" placeholder="Descricao" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-4"><input type="text" name="evento_promovido_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Eventos em que a Loja participou</h3>
                            <div class="space-y-2">
                                <?php for ($i = 0; $i < 3; $i++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-4"><input type="text" name="evento_participado_titulo[]" placeholder="Titulo" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="date" name="evento_participado_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="evento_participado_local[]" placeholder="Local" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="evento_participado_loja[]" placeholder="Loja" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="evento_participado_oriente[]" placeholder="Oriente" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-3"><input type="text" name="evento_participado_promotor[]" placeholder="Promotor" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-5"><input type="text" name="evento_participado_descricao[]" placeholder="Descricao" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-4"><input type="text" name="evento_participado_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Atividades sociais</h3>
                            <div class="space-y-2">
                                <?php for ($i = 0; $i < 3; $i++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-4"><input type="text" name="atividade_social_titulo[]" placeholder="Titulo" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="date" name="atividade_social_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="atividade_social_local[]" placeholder="Local" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="atividade_social_loja[]" placeholder="Instituicao/Loja" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-2"><input type="text" name="atividade_social_oriente[]" placeholder="Oriente" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-3"><input type="text" name="atividade_social_promotor[]" placeholder="Responsavel" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-5"><input type="text" name="atividade_social_descricao[]" placeholder="Descricao" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                        <div class="md:col-span-4"><input type="text" name="atividade_social_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm"></div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Observacoes livres da secretaria</label>
                            <textarea name="observacoes_secretaria" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Dados capturados adicionais (JSON opcional)</label>
                            <textarea name="dados_capturados" rows="2" placeholder='{"outros":"campos complementares"}' class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-white font-medium">Salvar balaustre</button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div class="mt-6 space-y-3">
                        <?php foreach ($balaustres as $balaustre): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="font-medium text-erp-navy">
                                    <?= htmlspecialchars($balaustre['numero_balaustre'] ?: 'Sem numero') ?>
                                    · <?= htmlspecialchars($balaustre['sessao_titulo'] ?: (string) ($balaustre['data_hora_inicio'] ?? '')) ?>
                                </div>
                                <div class="text-sm text-slate-700 mt-1">Status: <?= htmlspecialchars((string) ($balaustre['status'] ?? '')) ?></div>
                                <div class="text-xs text-slate-700 mt-1">
                                    Palavra a bem da ordem: <?= (int) ($balaustre['resumo_palavra_bem_ordem'] ?? 0) ?> registro(s)
                                    · Cargos ad hoc: <?= (int) ($balaustre['resumo_cargos_ad_hoc'] ?? 0) ?>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <?php if ($podeOperarSecretaria && (($balaustre['status'] ?? '') !== 'em_votacao')): ?>
                                    <form method="POST" action="/secretaria/balaustres/apto">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <button type="submit" class="rounded-md border border-erp-navy px-3 py-1.5 text-sm text-erp-navy">Deixar apto para votacao</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($podeAbrirVotacao && (($balaustre['status'] ?? '') === 'apto_votacao')): ?>
                                    <form method="POST" action="/secretaria/balaustres/abrir-votacao">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <button type="submit" class="rounded-md bg-erp-navy px-3 py-1.5 text-sm text-white">Abrir votacao (Veneravel Mestre)</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (($balaustre['status'] ?? '') === 'em_votacao' && (($elegibilidadeVoto[(int) $balaustre['id']] ?? false) === true)): ?>
                                    <form method="POST" action="/secretaria/balaustres/votar" class="flex flex-wrap gap-2 items-center">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <select name="voto" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                            <option value="aprovar">aprovar</option>
                                            <option value="pedir_correcao">pedir correcao</option>
                                            <option value="rejeitar">rejeitar</option>
                                        </select>
                                        <input type="text" name="justificativa" placeholder="Justificativa (opcional)" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                        <button type="submit" class="rounded-md border border-erp-navy px-3 py-1.5 text-sm text-erp-navy">Votar</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($podeAbrirVotacao && (($balaustre['status'] ?? '') === 'em_votacao')): ?>
                                    <form method="POST" action="/secretaria/balaustres/encerrar-votacao">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <button type="submit" class="rounded-md bg-slate-700 px-3 py-1.5 text-sm text-white">Encerrar votacao (Veneravel Mestre)</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-sans text-xl text-erp-navy">Publicacoes oficiais</h2>
                    <p class="text-sm text-slate-700 mb-4">Informativos das Potencias, agenda, proxima sessao e convites externos sob rastreio da Secretaria.</p>
                    <form method="POST" action="/secretaria/publicacoes/salvar" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo de publicacao</label>
                            <select name="tipo_publicacao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="agenda_oficial">Agenda oficial</option>
                                <option value="proxima_sessao">Proxima sessao</option>
                                <option value="informativo_potencia">Informativo da Potencia</option>
                                <option value="convite_externo">Convite externo</option>
                                <option value="comunicado">Comunicado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Titulo</label>
                            <input type="text" name="titulo" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Origem</label>
                            <input type="text" name="origem" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Canal</label>
                            <select name="canal_destino" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="grupo">Grupo</option>
                                <option value="web">Web</option>
                                <option value="interno">Interno</option>
                                <option value="misto">Misto</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Status</label>
                            <select name="status_publicacao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="rascunho">Rascunho</option>
                                <option value="publicado">Publicado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Conteudo</label>
                            <textarea name="conteudo" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Observacao</label>
                            <textarea name="observacao" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-white font-medium">Registrar publicacao</button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-sans text-xl text-erp-navy">Ultimos registros</h2>
                    <div class="space-y-3 mt-4">
                        <?php foreach ($publicacoes as $publicacao): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="font-medium text-erp-navy"><?= htmlspecialchars($publicacao['titulo']) ?></div>
                                <div class="text-sm text-slate-700 mt-1"><?= htmlspecialchars((string) $publicacao['tipo_publicacao']) ?> · <?= htmlspecialchars((string) $publicacao['status_publicacao']) ?></div>
                                <?php if (!empty($publicacao['origem'])): ?>
                                    <div class="text-xs text-slate-700 mt-1">Origem: <?= htmlspecialchars((string) $publicacao['origem']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-sans text-xl text-erp-navy">Responsabilidades consolidadas</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700 list-disc pl-5">
                        <li>Cadastro e atualizacao dos membros, inclusive grau e acesso a plataformas externas.</li>
                        <li>Operacao central das sessoes, publicacoes e fluxo documental da Loja.</li>
                        <li>Registro dos trabalhos da ordem do dia e preservacao do acervo em PDF.</li>
                        <li>Preparacao dos insumos do balaustre em ambiente web, com fechamento posterior.</li>
                    </ul>
                </div>
            </section>
        </div>
<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
