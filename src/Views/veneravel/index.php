<?php
declare(strict_types=1);

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$mes = (int) ($view['mes'] ?? (int) date('n'));
$ano = (int) ($view['ano'] ?? (int) date('Y'));
$inicioMes = $view['inicio_mes'] ?? null;
$fimMes = $view['fim_mes'] ?? null;
$sessoesMes = $view['sessoes_mes'] ?? [];
$convitesMes = $view['convites_mes'] ?? [];
$tesourariaResumo = $view['tesouraria_resumo'] ?? [];
$tesourariaSerie = $view['tesouraria_serie'] ?? [];
$tesourariaSomatorios = $view['tesouraria_somatorios'] ?? [];
$balaustresPendentes = $view['balaustres_pendentes_decisao'] ?? [];
$obreirosAtrasoFraterno = $view['obreiros_atraso_fraterno'] ?? [];

$formatCurrency = static fn (float $value): string => 'R$ ' . number_format($value, 2, ',', '.');
$formatMesAno = static fn (int $m, int $a): string => str_pad((string) $m, 2, '0', STR_PAD_LEFT) . '/' . $a;

// App shell (sem alterar a navegação lateral global do sistema)
$appShellEyebrow = 'Painel Executivo';
$appShellTitle = 'Venerável Mestre';
$appShellDescription = 'Acompanhamento executivo e governança da Loja, sem sobrepor a rotina do Tesoureiro e do Secretário.';
$appShellActiveHref = '/veneravel';
$appShellActions = [
    ['label' => 'Painel da Loja', 'href' => '/dashboard'],
    ['label' => 'Secretaria', 'href' => '/secretaria'],
    ['label' => 'Tesouraria', 'href' => '/tesouraria/caixa'],
];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <?php if ($mensagemSucesso): ?><div class="alert alert-success"><?= htmlspecialchars((string) $mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $mensagemErro) ?></div><?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Agenda do mês</h2>
                <p class="card-description">Sessões e compromissos do período (<?= htmlspecialchars($formatMesAno($mes, $ano)) ?>).</p>
            </div>
            <div class="card-body space-y-4">
                <form method="GET" action="/veneravel" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="form-label" for="mes">Mês</label>
                        <select id="mes" name="mes" class="form-select">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="ano">Ano</label>
                        <input id="ano" name="ano" type="number" min="2000" max="2100" class="form-input" value="<?= (int) $ano ?>">
                    </div>
                    <button type="submit" class="btn btn-secondary !py-2.5">Atualizar</button>
                </form>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-erp-border bg-white p-4">
                        <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Sessões no mês</p>
                        <p class="mt-2 text-2xl font-black text-erp-navy"><?= count($sessoesMes) ?></p>
                        <p class="mt-1 text-xs text-erp-muted">A agenda completa fica na Secretaria.</p>
                    </div>
                    <div class="rounded-lg border border-erp-border bg-white p-4">
                        <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Convites externos</p>
                        <p class="mt-2 text-2xl font-black text-erp-navy"><?= count($convitesMes) ?></p>
                        <p class="mt-1 text-xs text-erp-muted">Acompanhe o status sem expor contatos aqui.</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <?php if (empty($sessoesMes)): ?>
                        <p class="text-sm text-erp-muted">Nenhuma sessão registrada para este período.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($sessoesMes, 0, 8) as $sessao): ?>
                            <div class="list-item">
                                <div>
                                    <p class="font-semibold text-erp-navy"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></p>
                                    <p class="text-xs text-erp-muted"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 justify-end">
                                    <span class="badge badge-secondary"><?= htmlspecialchars((string) ($sessao['status'] ?? 'Agendada')) ?></span>
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="/veneravel/sessoes/publicar">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-secondary !py-1.5 !px-3 text-[10px] font-black uppercase tracking-widest">Publicar</button>
                                        </form>
                                        <form method="POST" action="/veneravel/sessoes/realizar">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-secondary !py-1.5 !px-3 text-[10px] font-black uppercase tracking-widest">Realizar</button>
                                        </form>
                                        <form method="POST" action="/veneravel/sessoes/reabrir">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-secondary !py-1.5 !px-3 text-[10px] font-black uppercase tracking-widest">Reabrir</button>
                                        </form>
                                        <form method="POST" action="/veneravel/sessoes/cancelar" onsubmit="return confirm('Confirmar cancelamento desta sessão?');">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-danger !py-1.5 !px-3 text-[10px] font-black uppercase tracking-widest">Cancelar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <a class="btn btn-secondary w-full" href="/secretaria">Ver agenda completa na Secretaria</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Balaústres para decisão</h2>
                <p class="card-description">Valide o texto final e encaminhe para votação.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if (empty($balaustresPendentes)): ?>
                    <p class="text-sm text-erp-muted">Nenhum Balaústre pendente de deliberação.</p>
                <?php else: ?>
                    <?php foreach (array_slice($balaustresPendentes, 0, 6) as $balaustre): ?>
                        <a href="/veneravel/balaustre/visualizar?id=<?= (int) ($balaustre['id'] ?? 0) ?>" class="list-item-action">
                            <div>
                                <p class="font-semibold text-erp-navy"><?= htmlspecialchars((string) ($balaustre['sessao_titulo'] ?? $balaustre['numero_balaustre'] ?? 'Balaústre')) ?></p>
                                <p class="text-xs text-erp-muted">Status: <?= htmlspecialchars((string) ($balaustre['status'] ?? 'rascunho')) ?></p>
                            </div>
                            <span class="badge badge-warning">Revisar</span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a class="btn btn-secondary w-full" href="/secretaria/balaustres">Abrir lista na Secretaria</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Tesouraria (resumo executivo)</h2>
                <p class="card-description">Consolidado do mês corrente, sem exposição de valores individuais.</p>
            </div>
            <div class="card-body space-y-6">
                <?php
                $fluxo = $tesourariaResumo['fluxo_atual'] ?? ['entradas' => 0, 'saidas' => 0, 'resultado' => 0];
                $saldoAtual = (float) ($tesourariaResumo['saldo_atual'] ?? 0);
                $previsao = (float) ($tesourariaResumo['previsao_fim_mes'] ?? 0);
                ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="card-metric">
                        <p class="card-metric-label">Fluxo atual</p>
                        <p class="card-metric-value"><?= htmlspecialchars($formatCurrency((float) ($fluxo['resultado'] ?? 0))) ?></p>
                        <p class="text-xs text-erp-muted mt-1">Entradas: <?= htmlspecialchars($formatCurrency((float) ($fluxo['entradas'] ?? 0))) ?> · Saídas: <?= htmlspecialchars($formatCurrency((float) ($fluxo['saidas'] ?? 0))) ?></p>
                    </div>
                    <div class="card-metric">
                        <p class="card-metric-label">Saldo atual</p>
                        <p class="card-metric-value"><?= htmlspecialchars($formatCurrency($saldoAtual)) ?></p>
                    </div>
                    <div class="card-metric">
                        <p class="card-metric-label">Previsão ao fim do mês</p>
                        <p class="card-metric-value"><?= htmlspecialchars($formatCurrency($previsao)) ?></p>
                    </div>
                </div>

                <div class="rounded-lg border border-erp-border bg-white p-4 space-y-3">
                    <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Somatórios do ano (por grupo)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="list-item">
                            <span class="text-erp-navy font-semibold">Despesas fixas</span>
                            <span><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_fixas'] ?? 0))) ?></span>
                        </div>
                        <div class="list-item">
                            <span class="text-erp-navy font-semibold">Ágape</span>
                            <span><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_agape'] ?? 0))) ?></span>
                        </div>
                        <div class="list-item">
                            <span class="text-erp-navy font-semibold">Material de expediente</span>
                            <span><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_expediente'] ?? 0))) ?></span>
                        </div>
                        <div class="list-item">
                            <span class="text-erp-navy font-semibold">Cozinha e mercado</span>
                            <span><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_cozinha_mercado'] ?? 0))) ?></span>
                        </div>
                        <div class="list-item">
                            <span class="text-erp-navy font-semibold">Entradas fixas</span>
                            <span><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['entradas_fixas'] ?? 0))) ?></span>
                        </div>
                        <div class="list-item">
                            <span class="text-erp-navy font-semibold">Contribuições mensais</span>
                            <span><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['entradas_mensalidades'] ?? 0))) ?></span>
                        </div>
                        <div class="list-item">
                            <span class="text-erp-navy font-semibold">Joias</span>
                            <span><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['entradas_joias'] ?? 0))) ?></span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-erp-border bg-white p-4 space-y-3">
                    <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Comparativo mês a mês (3 anos)</p>
                    <div class="grid grid-cols-12 gap-1 items-end" style="min-height: 120px;">
                        <?php
                        // Bar chart simples (resultado = entradas - saídas) para o ano base
                        $serieAnoBase = array_values(array_filter($tesourariaSerie, static fn (array $p): bool => (int) ($p['ano'] ?? 0) === $ano));
                        $valores = array_map(static fn (array $p): float => (float) ($p['resultado'] ?? 0), $serieAnoBase);
                        $maxAbs = max(1.0, max(array_map('abs', $valores ?: [0.0])));
                        for ($m = 1; $m <= 12; $m++):
                            $ponto = null;
                            foreach ($serieAnoBase as $linha) {
                                if ((int) ($linha['mes'] ?? 0) === $m) { $ponto = $linha; break; }
                            }
                            $resultado = (float) ($ponto['resultado'] ?? 0);
                            $altura = (int) round((abs($resultado) / $maxAbs) * 100);
                            $classe = $resultado >= 0 ? 'bg-erp-success/70' : 'bg-erp-danger/70';
                        ?>
                            <div class="flex flex-col items-center justify-end gap-1" title="<?= htmlspecialchars($formatMesAno($m, $ano)) ?>: <?= htmlspecialchars($formatCurrency($resultado)) ?>">
                                <div class="w-full rounded-sm <?= $classe ?>" style="height: <?= $altura ?>px; min-height: 6px;"></div>
                                <div class="text-[10px] text-erp-muted"><?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <p class="text-xs text-erp-muted">Leitura rápida do resultado mensal (entradas menos saídas) no ano selecionado. O detalhamento completo permanece na Tesouraria.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Acompanhamento fraterno</h2>
                <p class="card-description">Contagem e nomes (sem valores) para abordagem discreta.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if (empty($obreirosAtrasoFraterno)): ?>
                    <p class="text-sm text-erp-muted">Nenhum atraso considerado neste momento.</p>
                    <p class="text-xs text-erp-muted">Regra: só considera em atraso após o 1º dia útil do mês subsequente.</p>
                <?php else: ?>
                    <div class="rounded-lg border border-erp-border bg-white p-4">
                        <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Em atraso</p>
                        <p class="mt-2 text-2xl font-black text-erp-navy"><?= count($obreirosAtrasoFraterno) ?></p>
                    </div>
                    <?php foreach ($obreirosAtrasoFraterno as $obreiro): ?>
                        <div class="list-item">
                            <span class="font-semibold text-erp-navy"><?= htmlspecialchars((string) ($obreiro['nome'] ?? 'Obreiro')) ?></span>
                            <span class="badge badge-danger">Atraso</span>
                        </div>
                    <?php endforeach; ?>
                    <a class="btn btn-secondary w-full" href="/tesouraria/obrigacoes">Ver detalhes na Tesouraria</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Secretaria (monitoramento resumido)</h2>
            <p class="card-description">Pendências e status para acompanhamento executivo. A operação diária segue com o Secretário.</p>
        </div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg border border-erp-border bg-white p-4">
                <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Balaústres</p>
                <p class="mt-2 text-2xl font-black text-erp-navy"><?= count($balaustresPendentes) ?></p>
                <p class="text-xs text-erp-muted mt-1">Pendentes de deliberação do Venerável.</p>
                <a class="btn btn-secondary w-full mt-3" href="/secretaria/balaustres">Acompanhar</a>
            </div>
            <div class="rounded-lg border border-erp-border bg-white p-4">
                <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Convites</p>
                <p class="mt-2 text-2xl font-black text-erp-navy"><?= count($convitesMes) ?></p>
                <p class="text-xs text-erp-muted mt-1">Status e programação do período.</p>
                <a class="btn btn-secondary w-full mt-3" href="/secretaria/convites-externos">Ver detalhes</a>
            </div>
            <div class="rounded-lg border border-erp-border bg-white p-4">
                <p class="text-xs font-bold text-erp-muted uppercase tracking-widest">Sessões</p>
                <p class="mt-2 text-2xl font-black text-erp-navy"><?= count($sessoesMes) ?></p>
                <p class="text-xs text-erp-muted mt-1">Acompanhe e valide o calendário institucional.</p>
                <a class="btn btn-secondary w-full mt-3" href="/secretaria">Abrir Secretaria</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
