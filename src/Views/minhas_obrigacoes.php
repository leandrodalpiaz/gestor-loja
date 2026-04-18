<?php
if (!isset($obreiroFinanceiro) || !$obreiroFinanceiro) {
    http_response_code(401);
    echo 'Acesso nao autorizado.';
    exit;
}

$resumoObreiro = $resumoObreiro ?? [];
$obrigacoesObreiro = $obrigacoesObreiro ?? [];
$formatCurrency = static function ($value): string {
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
};
$configuracaoFinanceira = (new \App\Models\ConfiguracaoLoja())->obter();
$mensalidadePadrao = (float) ($configuracaoFinanceira['mensalidade_valor_padrao'] ?? 150);
$pixTipo = (string) ($configuracaoFinanceira['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoFinanceira['pix_chave_valor'] ?? '');
$pixBeneficiario = (string) ($configuracaoFinanceira['pix_beneficiario'] ?? '');
$hoje = date('Y-m-d');
$mesAtualChave = date('Y-m');
$bibliotecaPorMes = [];
try {
    $dbFinanceiro = \App\Config\Database::getConnection();
    $stmtBiblioteca = $dbFinanceiro->prepare("
        SELECT mes_ref, valor_previsto
        FROM public.biblioteca_contribuintes_mensal
        WHERE ano_ref = 2026 AND obreiro_id = :obreiro_id
    ");
    $stmtBiblioteca->execute([
        'obreiro_id' => (string) ($obreiroFinanceiro['id'] ?? ''),
    ]);
    foreach ($stmtBiblioteca->fetchAll(PDO::FETCH_ASSOC) as $linhaBiblioteca) {
        $bibliotecaPorMes[(int) ($linhaBiblioteca['mes_ref'] ?? 0)] = (float) ($linhaBiblioteca['valor_previsto'] ?? 44);
    }
} catch (\Throwable $e) {
    $bibliotecaPorMes = [];
}
$nomesMeses = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Marco',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
];
$joiaResumo = null;

$parcelasPagas = [];
$parcelasAguardandoConfirmacao = [];
$parcelasProgramadas = [];
$parcelasAtrasadas = [];
$mesesFinanceiros = [];
$anoPainel = 2026;

for ($mes = 1; $mes <= 12; $mes++) {
    $chaveMes = sprintf('%04d-%02d', $anoPainel, $mes);
    $mensalidadePaga = $mes <= 3;
    $mesesFinanceiros[$chaveMes] = [
        'chave' => $chaveMes,
        'rotulo' => ($nomesMeses[$mes] ?? 'Mes') . ' ' . $anoPainel,
        'total_pago' => $mensalidadePaga ? $mensalidadePadrao : 0.0,
        'total_previsto' => $mensalidadePadrao + ($bibliotecaPorMes[$mes] ?? 0.0),
        'total_aberto' => $mensalidadePaga ? 0.0 : $mensalidadePadrao,
        'pagos' => $mensalidadePaga ? 1 : 0,
        'abertos' => $mensalidadePaga ? 0 : 1,
        'atrasados' => 0,
        'itens' => [[
            'item_base' => true,
            'obrigacao_titulo' => 'Mensalidade da Loja',
            'tipo_obrigacao' => 'mensalidade',
            'competencia_label' => sprintf('%02d/%04d', $mes, $anoPainel),
            'competencia_mes' => $mes,
            'competencia_ano' => $anoPainel,
            'vencimento' => sprintf('%04d-%02d-10', $anoPainel, $mes),
            'valor_previsto' => $mensalidadePadrao,
            'pago_presumido' => $mensalidadePaga,
            'quitado_na_exibicao' => $mensalidadePaga,
            'status_exibicao' => $mensalidadePaga ? 'quitado_ajuste' : 'pendente',
            'descricao_status' => $mensalidadePaga
                ? 'Mensalidade considerada paga no ajuste inicial de 2026.'
                : 'Mensalidade prevista para o mes.',
            'em_atraso' => false,
            'pago_em' => null,
        ]],
    ];

    if (isset($bibliotecaPorMes[$mes])) {
        $bibliotecaPaga = $mes <= 3;
        if ($bibliotecaPaga) {
            $mesesFinanceiros[$chaveMes]['total_pago'] += (float) $bibliotecaPorMes[$mes];
            $mesesFinanceiros[$chaveMes]['pagos']++;
        } else {
            $mesesFinanceiros[$chaveMes]['total_aberto'] += (float) $bibliotecaPorMes[$mes];
            $mesesFinanceiros[$chaveMes]['abertos']++;
        }
        $mesesFinanceiros[$chaveMes]['itens'][] = [
            'item_base' => true,
            'obrigacao_titulo' => 'Contribuicao Biblioteca',
            'tipo_obrigacao' => 'biblioteca',
            'competencia_label' => sprintf('%02d/%04d', $mes, $anoPainel),
            'competencia_mes' => $mes,
            'competencia_ano' => $anoPainel,
            'vencimento' => sprintf('%04d-%02d-10', $anoPainel, $mes),
            'valor_previsto' => (float) $bibliotecaPorMes[$mes],
            'pago_presumido' => $bibliotecaPaga,
            'quitado_na_exibicao' => $bibliotecaPaga,
            'status_exibicao' => $bibliotecaPaga ? 'quitado_ajuste' : 'pendente',
            'descricao_status' => $bibliotecaPaga
                ? 'Contribuicao considerada paga no ajuste inicial de 2026.'
                : 'Contribuicao da biblioteca prevista para este mes.',
            'em_atraso' => false,
            'pago_em' => null,
        ];
    }
}

foreach ($obrigacoesObreiro as $obrigacao) {
    if ((string) ($obrigacao['tipo_obrigacao'] ?? '') === 'joia') {
        $parcelasJoia = $obrigacao['parcelas'] ?? [];
        $totalJoia = 0.0;
        $totalPagoJoia = 0.0;
        $totalAbertoJoia = 0.0;
        $quantidadeParcelas = count($parcelasJoia);
        foreach ($parcelasJoia as $parcelaJoia) {
            $valorJoia = (float) ($parcelaJoia['valor_previsto'] ?? 0);
            $totalJoia += $valorJoia;
            if (!empty($parcelaJoia['quitado_na_exibicao'])) {
                $totalPagoJoia += $valorJoia;
            } else {
                $totalAbertoJoia += $valorJoia;
            }
        }
        $joiaResumo = [
            'titulo' => (string) ($obrigacao['titulo'] ?? 'Joia'),
            'total' => $totalJoia > 0 ? $totalJoia : 1621.0,
            'pago' => $totalPagoJoia,
            'aberto' => $totalAbertoJoia,
            'parcelas_total' => $quantidadeParcelas > 0 ? $quantidadeParcelas : 1,
            'valor_parcela' => $quantidadeParcelas > 0 ? ($totalJoia / max(1, $quantidadeParcelas)) : 1621.0,
            'forma' => (string) ($obrigacao['instrucoes_pagamento'] ?? 'Pagamento definido pela Tesouraria'),
        ];
    }
    foreach (($obrigacao['parcelas'] ?? []) as $parcela) {
        $parcela['obrigacao_titulo'] = (string) ($parcela['obrigacao_titulo'] ?? $obrigacao['titulo'] ?? 'Obrigacao');
        $parcela['tipo_obrigacao'] = (string) ($parcela['tipo_obrigacao'] ?? $obrigacao['tipo_obrigacao'] ?? 'outra');

        $mesCompetencia = (int) ($parcela['competencia_mes'] ?? 0);
        $anoCompetencia = (int) ($parcela['competencia_ano'] ?? 0);
        if ($mesCompetencia > 0 && $anoCompetencia > 0) {
            $chaveMes = sprintf('%04d-%02d', $anoCompetencia, $mesCompetencia);
            if ($anoCompetencia === $anoPainel && isset($mesesFinanceiros[$chaveMes])) {
                foreach ($mesesFinanceiros[$chaveMes]['itens'] as $indice => $itemExistente) {
                    if (
                        (string) ($itemExistente['tipo_obrigacao'] ?? '') === (string) ($parcela['tipo_obrigacao'] ?? '') &&
                        !empty($itemExistente['item_base'])
                    ) {
                        $valorAnterior = (float) ($mesesFinanceiros[$chaveMes]['itens'][$indice]['valor_previsto'] ?? 0);
                        $eraQuitado = !empty($mesesFinanceiros[$chaveMes]['itens'][$indice]['quitado_na_exibicao']);

                        $mesesFinanceiros[$chaveMes]['total_previsto'] -= $valorAnterior;
                        if ($eraQuitado) {
                            $mesesFinanceiros[$chaveMes]['total_pago'] -= $valorAnterior;
                            $mesesFinanceiros[$chaveMes]['pagos'] = max(0, $mesesFinanceiros[$chaveMes]['pagos'] - 1);
                        } else {
                            $mesesFinanceiros[$chaveMes]['total_aberto'] -= $valorAnterior;
                            $mesesFinanceiros[$chaveMes]['abertos'] = max(0, $mesesFinanceiros[$chaveMes]['abertos'] - 1);
                        }
                        unset($mesesFinanceiros[$chaveMes]['itens'][$indice]);
                        break;
                    }
                }
            } elseif (!isset($mesesFinanceiros[$chaveMes])) {
                $mesesFinanceiros[$chaveMes] = [
                    'chave' => $chaveMes,
                    'rotulo' => ($nomesMeses[$mesCompetencia] ?? 'Mes') . ' ' . $anoCompetencia,
                    'total_pago' => 0.0,
                    'total_previsto' => 0.0,
                    'total_aberto' => 0.0,
                    'pagos' => 0,
                    'abertos' => 0,
                    'atrasados' => 0,
                    'itens' => [],
                ];
            }

            $mesesFinanceiros[$chaveMes]['total_previsto'] += (float) ($parcela['valor_previsto'] ?? 0);
            if (!empty($parcela['quitado_na_exibicao'])) {
                $mesesFinanceiros[$chaveMes]['total_pago'] += (float) ($parcela['valor_previsto'] ?? 0);
                $mesesFinanceiros[$chaveMes]['pagos']++;
            } else {
                $mesesFinanceiros[$chaveMes]['total_aberto'] += (float) ($parcela['valor_previsto'] ?? 0);
                $mesesFinanceiros[$chaveMes]['abertos']++;
                if (!empty($parcela['em_atraso'])) {
                    $mesesFinanceiros[$chaveMes]['atrasados']++;
                }
            }
            $mesesFinanceiros[$chaveMes]['itens'][] = $parcela;
        }

    }
}

uasort($mesesFinanceiros, static function (array $a, array $b): int {
    return strcmp((string) ($a['chave'] ?? ''), (string) ($b['chave'] ?? ''));
});

foreach ($mesesFinanceiros as $mesFinanceiro) {
    foreach (($mesFinanceiro['itens'] ?? []) as $parcela) {
        if (!empty($parcela['quitado_na_exibicao'])) {
            $parcelasPagas[] = $parcela;
            continue;
        }

        if (!empty($parcela['em_atraso'])) {
            $parcelasAtrasadas[] = $parcela;
            continue;
        }

        $vencimento = (string) ($parcela['vencimento'] ?? '');
        if ($vencimento !== '' && $vencimento <= date('Y-m-d')) {
            $parcelasAguardandoConfirmacao[] = $parcela;
        } else {
            $parcelasProgramadas[] = $parcela;
        }
    }
}

usort($parcelasPagas, static function (array $a, array $b): int {
    return strcmp((string) ($b['competencia_label'] ?? ''), (string) ($a['competencia_label'] ?? ''));
});
usort($parcelasAguardandoConfirmacao, static function (array $a, array $b): int {
    return strcmp((string) ($a['vencimento'] ?? ''), (string) ($b['vencimento'] ?? ''));
});
usort($parcelasProgramadas, static function (array $a, array $b): int {
    return strcmp((string) ($a['vencimento'] ?? ''), (string) ($b['vencimento'] ?? ''));
});
usort($parcelasAtrasadas, static function (array $a, array $b): int {
    return strcmp((string) ($a['vencimento'] ?? ''), (string) ($b['vencimento'] ?? ''));
});

$pagasRecentes = array_slice($parcelasPagas, 0, 6);
$proximaObrigacao = $parcelasAguardandoConfirmacao[0] ?? $parcelasProgramadas[0] ?? null;
$proximosCompromissos = array_slice($parcelasProgramadas, 0, 6);
$nomeObreiro = (string) ($obreiroFinanceiro['nome_historico'] ?? $obreiroFinanceiro['nome'] ?? 'Irmao');
$totalPagoExibicao = array_reduce($parcelasPagas, static function (float $carry, array $parcela): float {
    return $carry + (float) ($parcela['valor_previsto'] ?? 0);
}, 0.0);
$resumoPagoTexto = count($parcelasPagas) > 0
    ? count($parcelasPagas) . ' compromissos marcados como pagos.'
    : 'Nenhum pagamento confirmado ate aqui.';
$resumoPagoDetalhe = $pagasRecentes[0]['obrigacao_titulo'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Meu Financeiro</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-[11px] {
                font-size: 0.92rem !important;
                line-height: 1.4rem !important;
            }
            .erp-readable .text-sm {
                font-size: 1.03rem !important;
                line-height: 1.58rem !important;
            }
        }
    </style>
</head>
<body class="erp-readable min-h-screen bg-[linear-gradient(180deg,#f8f3e8_0%,#f4f4f5_42%,#ffffff_100%)] text-slate-900">
<div class="mx-auto max-w-7xl px-4 py-6">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 text-stone-100 shadow-2xl">
        <div class="bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.24),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(148,163,184,0.28),transparent_28%)] px-6 py-7 sm:px-8">
            <p class="text-xs uppercase tracking-[0.32em] text-amber-300">Meu financeiro</p>
            <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-semibold"><?php echo htmlspecialchars($nomeObreiro); ?></h1>
                    <p class="mt-2 text-sm leading-6 text-slate-300">
                        Agora o painel destaca competencia por competencia, mostrando o que ja foi pago, o que ainda esta previsto e os proximos compromissos da Loja.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                    <div class="uppercase tracking-[0.18em] text-amber-200">Ajuste inicial 2026</div>
                    <div class="mt-1 text-slate-300">Janeiro, fevereiro e marco de 2026 aparecem como quitados no ajuste inicial do sistema.</div>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                    PIX <?php echo htmlspecialchars($pixTipo); ?>: <?php echo htmlspecialchars($pixValor ?: 'Nao informado'); ?>
                </div>
                <button type="button" class="rounded-2xl bg-amber-300 px-4 py-3 text-sm font-semibold text-slate-950" onclick="navigator.clipboard && navigator.clipboard.writeText('<?php echo htmlspecialchars(addslashes($pixValor)); ?>')">Copiar chave PIX</button>
                <div class="text-sm text-slate-300"><?php echo htmlspecialchars($pixBeneficiario); ?></div>
            </div>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[1.75rem] border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
            <div class="text-xs uppercase tracking-[0.18em] text-emerald-500">O que ja foi pago</div>
            <div class="mt-2 text-3xl font-semibold text-emerald-800"><?php echo $formatCurrency($totalPagoExibicao); ?></div>
            <div class="mt-2 text-sm text-emerald-700"><?php echo htmlspecialchars($resumoPagoTexto); ?></div>
            <?php if ($resumoPagoDetalhe !== ''): ?>
                <div class="mt-2 text-sm font-medium text-emerald-800"><?php echo htmlspecialchars($resumoPagoDetalhe); ?></div>
            <?php endif; ?>
        </div>
        <div class="rounded-[1.75rem] border border-amber-100 bg-amber-50 p-5 shadow-sm">
            <div class="text-xs uppercase tracking-[0.18em] text-amber-500">Proxima obrigacao</div>
            <div class="mt-2 text-3xl font-semibold text-amber-800"><?php echo $formatCurrency($proximaObrigacao['valor_previsto'] ?? 0); ?></div>
            <div class="mt-2 text-sm text-amber-700">
                <?php if ($proximaObrigacao): ?>
                    <?php echo htmlspecialchars((string) ($proximaObrigacao['obrigacao_titulo'] ?? 'Obrigacao')); ?> de <?php echo htmlspecialchars((string) ($proximaObrigacao['competencia_label'] ?? '')); ?>.
                <?php else: ?>
                    Nenhuma obrigacao pendente no momento.
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($parcelasAguardandoConfirmacao) || !empty($parcelasAtrasadas)): ?>
            <div class="rounded-[1.75rem] border border-rose-100 bg-rose-50 p-5 shadow-sm">
                <div class="text-xs uppercase tracking-[0.18em] text-rose-500">Atencao</div>
                <div class="mt-2 text-3xl font-semibold text-rose-800"><?php echo count($parcelasAguardandoConfirmacao) + count($parcelasAtrasadas); ?></div>
                <div class="mt-2 text-sm text-rose-700">Pagamentos que exigem confirmacao ou tratativa com a Tesouraria.</div>
            </div>
        <?php else: ?>
            <div class="flex items-center justify-center rounded-[1.75rem] border border-sky-100 bg-[linear-gradient(135deg,#eff6ff_0%,#dbeafe_100%)] p-5 shadow-sm">
                <div class="text-center text-xl font-semibold text-sky-900">A prumo com a tesouraria</div>
            </div>
        <?php endif; ?>
        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Proximo marco</div>
            <div class="mt-2 text-2xl font-semibold text-slate-900">
                <?php echo $proximaObrigacao && !empty($proximaObrigacao['vencimento']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $proximaObrigacao['vencimento']))) : 'Sem previsao'; ?>
            </div>
            <div class="mt-2 text-sm text-slate-700">Mensalidades seguem orientadas para o dia 10. Obrigacoes futuras ficam apenas programadas ate se aproximarem do pagamento.</div>
        </div>
        <?php if ($joiaResumo !== null): ?>
            <div class="rounded-[1.75rem] border border-violet-100 bg-violet-50 p-5 shadow-sm sm:col-span-2 xl:col-span-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-[0.18em] text-violet-500">Joia</div>
                        <div class="mt-2 text-3xl font-semibold text-violet-800"><?php echo $formatCurrency($joiaResumo['total']); ?></div>
                        <div class="mt-2 text-sm text-violet-700"><?php echo htmlspecialchars($joiaResumo['titulo']); ?></div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.14em] text-slate-400">Pago</div>
                            <div class="mt-1 text-lg font-semibold text-emerald-700"><?php echo $formatCurrency($joiaResumo['pago']); ?></div>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.14em] text-slate-400">Em aberto</div>
                            <div class="mt-1 text-lg font-semibold text-amber-700"><?php echo $formatCurrency($joiaResumo['aberto']); ?></div>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3">
                            <div class="text-xs uppercase tracking-[0.14em] text-slate-400">Parcelamento</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900"><?php echo $joiaResumo['parcelas_total']; ?> x <?php echo $formatCurrency($joiaResumo['valor_parcela']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-sm text-violet-700"><?php echo htmlspecialchars($joiaResumo['forma']); ?></div>
                <div class="mt-2 text-xs text-violet-600">Referencia do salario minimo nacional em 2026: R$ 1.621,00.</div>
            </div>
        <?php endif; ?>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Painel mensal</p>
                        <h2 class="mt-1 text-2xl font-semibold">Mes a mes da Loja</h2>
                    </div>
                    <div class="text-sm text-slate-700">Visualmente aparente: total pago, total previsto e lista de obrigacoes dentro de cada competencia.</div>
                </div>

                <div class="mt-5 space-y-4">
                    <?php foreach ($mesesFinanceiros as $mesFinanceiro): ?>
                        <?php
                        $mostrarPixMes = false;
                        $mesPago = (int) ($mesFinanceiro['abertos'] ?? 0) === 0 && (float) ($mesFinanceiro['total_pago'] ?? 0) > 0;
                        $mesAtrasado = (int) ($mesFinanceiro['atrasados'] ?? 0) > 0;
                        $cardMesClass = 'border-stone-200 bg-stone-50/70';
                        if ($mesPago) {
                            $cardMesClass = 'border-emerald-100 bg-emerald-50';
                        } elseif ($mesAtrasado) {
                            $cardMesClass = 'border-rose-200 bg-rose-50';
                        }
                        foreach (($mesFinanceiro['itens'] ?? []) as $itemMes) {
                            $vencimentoItem = (string) ($itemMes['vencimento'] ?? '');
                            $chaveItem = substr($vencimentoItem, 0, 7);
                            if (empty($itemMes['quitado_na_exibicao']) && ($vencimentoItem !== '' && ($vencimentoItem <= $hoje || $chaveItem === $mesAtualChave))) {
                                $mostrarPixMes = true;
                                break;
                            }
                        }
                        ?>
                        <article class="rounded-[1.75rem] border p-5 <?php echo $cardMesClass; ?>">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Competencia</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900"><?php echo htmlspecialchars((string) $mesFinanceiro['rotulo']); ?></h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">Resumo consolidado do mes para voce enxergar rapidamente o que ja entrou e o que ainda depende de pagamento.</p>
                                    <?php if ($mostrarPixMes): ?>
                                        <button
                                            type="button"
                                            class="mt-4 rounded-2xl bg-amber-300 px-4 py-2.5 text-sm font-semibold text-slate-950"
                                            onclick="navigator.clipboard && navigator.clipboard.writeText('<?php echo htmlspecialchars(addslashes($pixValor)); ?>')"
                                        >
                                            Fazer pagamento via PIX • <?php echo $formatCurrency($mesFinanceiro['total_aberto']); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div class="grid gap-2 rounded-[1.5rem] bg-white p-4 text-sm text-slate-700 shadow-sm sm:grid-cols-3 xl:min-w-[380px]">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Pago</div>
                                        <div class="mt-1 text-lg font-semibold text-emerald-700"><?php echo $formatCurrency($mesFinanceiro['total_pago']); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Aguardando confirmacao</div>
                                        <div class="mt-1 text-lg font-semibold text-amber-700"><?php echo $formatCurrency($mesFinanceiro['total_aberto']); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Movimento</div>
                                        <div class="mt-1 text-lg font-semibold text-slate-900"><?php echo $mesFinanceiro['pagos']; ?> pagos • <?php echo $mesFinanceiro['abertos']; ?> abertos</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 lg:grid-cols-2">
                                <?php if ($mesFinanceiro['itens'] === []): ?>
                                    <div class="lg:col-span-2 rounded-[1.35rem] border border-dashed border-stone-300 bg-white px-4 py-5 text-sm text-slate-700">
                                        Nenhuma obrigacao lancada neste mes ainda. Quando houver mensalidade, biblioteca, joia ou outra cobranca, ela aparecera aqui.
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($mesFinanceiro['itens'] as $parcela): ?>
                                    <?php
                                    $badgeClass = 'bg-amber-100 text-amber-700';
                                    $badgeLabel = 'Programada';
                                    if (!empty($parcela['pago_presumido'])) {
                                        $badgeClass = 'bg-sky-100 text-sky-700';
                                        $badgeLabel = 'Quitado no ajuste inicial';
                                    } elseif (($parcela['status_exibicao'] ?? '') === 'pago') {
                                        $badgeClass = 'bg-emerald-100 text-emerald-700';
                                        $badgeLabel = 'Pago';
                                    } elseif (!empty($parcela['em_atraso'])) {
                                        $badgeClass = 'bg-rose-100 text-rose-700';
                                        $badgeLabel = 'Atencao';
                                    } elseif (!empty($parcela['vencimento']) && (string) $parcela['vencimento'] <= date('Y-m-d')) {
                                        $badgeClass = 'bg-amber-100 text-amber-700';
                                        $badgeLabel = 'Aguardando confirmacao';
                                    }
                                    ?>
                                    <div class="rounded-[1.35rem] border border-stone-200 bg-white px-4 py-4 shadow-sm">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars((string) $parcela['obrigacao_titulo']); ?></div>
                                                <div class="mt-1 text-sm text-slate-700">
                                                    <?php echo htmlspecialchars((string) ($parcela['competencia_label'] ?? '-')); ?> • vencimento <?php echo !empty($parcela['vencimento']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $parcela['vencimento']))) : '-'; ?>
                                                    <?php if (!empty($parcela['pago_em'])): ?>
                                                        • pago em <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $parcela['pago_em']))); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                                <div class="text-lg font-semibold text-slate-900"><?php echo $formatCurrency($parcela['valor_previsto'] ?? 0); ?></div>
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                                            </div>
                                        </div>
                                        <div class="mt-3 text-sm text-slate-700"><?php echo htmlspecialchars((string) ($parcela['descricao_status'] ?? '')); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Pagamentos</p>
                <h2 class="mt-1 text-2xl font-semibold">Historico recente</h2>
                <div class="mt-4 space-y-3">
                    <?php if ($pagasRecentes === []): ?>
                        <div class="rounded-[1.5rem] border border-dashed border-stone-300 bg-stone-50 p-5 text-sm text-slate-700">Ainda nao ha compromissos reconhecidos como pagos.</div>
                    <?php endif; ?>
                    <?php foreach ($pagasRecentes as $parcela): ?>
                        <div class="rounded-[1.4rem] border border-stone-200 bg-stone-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars((string) $parcela['obrigacao_titulo']); ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars((string) ($parcela['competencia_label'] ?? '-')); ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-emerald-700"><?php echo $formatCurrency($parcela['valor_previsto'] ?? 0); ?></div>
                                    <div class="mt-1 text-xs text-slate-700"><?php echo !empty($parcela['pago_presumido']) ? 'Quitado no ajuste inicial' : 'Pago'; ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Programado</p>
                <h2 class="mt-1 text-2xl font-semibold">Acompanhar agora</h2>
                <div class="mt-4 space-y-3">
                    <?php if ($proximosCompromissos === []): ?>
                        <div class="rounded-[1.5rem] border border-dashed border-stone-300 bg-stone-50 p-5 text-sm text-slate-700">Nenhuma obrigacao futura programada no momento.</div>
                    <?php endif; ?>
                    <?php foreach ($proximosCompromissos as $parcela): ?>
                        <div class="rounded-[1.4rem] border border-stone-200 bg-stone-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars((string) $parcela['obrigacao_titulo']); ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars((string) ($parcela['competencia_label'] ?? '-')); ?> • programada para <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $parcela['vencimento']))); ?></div>
                                </div>
                                <div class="text-right text-sm font-semibold text-slate-900"><?php echo $formatCurrency($parcela['valor_previsto'] ?? 0); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="rounded-[2rem] border border-rose-100 bg-rose-50 p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.22em] text-rose-500">Quando procurar a Tesouraria</p>
                <h2 class="mt-1 text-2xl font-semibold text-rose-900">Pendencias que pedem ajuste</h2>
                <div class="mt-4 space-y-3">
                    <?php if ($parcelasAtrasadas === []): ?>
                        <div class="rounded-[1.5rem] border border-rose-100 bg-white/80 p-5 text-sm text-rose-700">Nenhuma pendencia em atraso no momento.</div>
                    <?php endif; ?>
                    <?php foreach ($parcelasAtrasadas as $parcela): ?>
                        <div class="rounded-[1.4rem] border border-rose-200 bg-white px-4 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars((string) $parcela['obrigacao_titulo']); ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars((string) ($parcela['competencia_label'] ?? '-')); ?> • previsto para <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $parcela['vencimento']))); ?></div>
                                </div>
                                <div class="text-right text-sm font-semibold text-rose-700"><?php echo $formatCurrency($parcela['valor_previsto'] ?? 0); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</div>
</body>
</html>

