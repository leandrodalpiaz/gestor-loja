<?php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}

$selectedObreiroId = (string) ($selectedObreiroId ?? '');
$selectedObreiroNome = (string) ($selectedObreiroNome ?? 'Selecione um obreiro');
$resumoObreiro = $resumoObreiro ?? [];
$obrigacoesObreiro = $obrigacoesObreiro ?? [];
$obreirosPainel = $obreirosPainel ?? [];
$categoriasEntrada = $categoriasEntrada ?? [];
$configuracaoLoja = $configuracaoLoja ?? [];
$mensalidadePadrao = (float) ($configuracaoLoja['mensalidade_valor_padrao'] ?? 150);
$bibliotecaPadrao = (float) ($configuracaoLoja['contribuicao_biblioteca_valor_padrao'] ?? 44);
$salarioMinimoPadrao = 1621.00;
$pixTipo = (string) ($configuracaoLoja['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoLoja['pix_chave_valor'] ?? '');
$formatCurrency = static function ($value): string {
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
};

$hoje = new DateTimeImmutable('today');
$mesAtual = (int) $hoje->format('n');
$anoAtual = (int) $hoje->format('Y');
$competenciaAtual = sprintf('%02d/%04d', $mesAtual, $anoAtual);
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Marco', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];
$tituloMesAtual = ($meses[$mesAtual] ?? 'Mes') . ' ' . $anoAtual;

$db = \App\Config\Database::getConnection();
$stmtHistorico = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN (ano_ref < :ano OR (ano_ref = :ano AND mes_ref < :mes)) THEN valor ELSE 0 END), 0) AS total_passado,
        COALESCE(SUM(CASE WHEN ano_ref = :ano AND mes_ref = :mes THEN valor ELSE 0 END), 0) AS total_mes
    FROM public.lancamentos_financeiros
    WHERE tipo = 'entrada'
");
$stmtHistorico->execute(['ano' => $anoAtual, 'mes' => $mesAtual]);
$historicoGeral = $stmtHistorico->fetch(PDO::FETCH_ASSOC) ?: ['total_passado' => 0, 'total_mes' => 0];

$stmtMesAtual = $db->prepare("
    SELECT
        o.id AS obreiro_id,
        COALESCE(o.nome_historico, o.nome) AS nome,
        ofp.id AS parcela_id,
        ofi.titulo,
        ofi.tipo_obrigacao,
        ofp.valor_previsto,
        ofp.vencimento,
        ofp.status,
        ofp.pago_em
    FROM public.obrigacao_financeira_parcelas ofp
    JOIN public.obrigacoes_financeiras ofi ON ofi.id = ofp.obrigacao_id
    JOIN public.obreiros o ON o.id = ofi.obreiro_id
    WHERE ofp.competencia_mes = :mes
      AND ofp.competencia_ano = :ano
      AND o.ativo = TRUE
    ORDER BY COALESCE(o.nome_historico, o.nome) ASC, ofp.vencimento ASC, ofp.id ASC
");
$stmtMesAtual->execute(['mes' => $mesAtual, 'ano' => $anoAtual]);
$linhasMesAtual = $stmtMesAtual->fetchAll(PDO::FETCH_ASSOC);

$painelGeral = [];
$recebidoMes = 0.0;
$faltanteMes = 0.0;
$emAtrasoGeral = 0;

foreach ($linhasMesAtual as $linha) {
    $obreiroIdLinha = (string) ($linha['obreiro_id'] ?? '');
    if ($obreiroIdLinha === '') {
        continue;
    }
    if (!isset($painelGeral[$obreiroIdLinha])) {
        $painelGeral[$obreiroIdLinha] = [
            'obreiro_id' => $obreiroIdLinha,
            'nome' => (string) ($linha['nome'] ?? 'Obreiro'),
            'pago' => 0.0,
            'aberto' => 0.0,
            'atrasado' => 0.0,
            'vencidos' => 0,
            'itens' => [],
        ];
    }

    $valor = (float) ($linha['valor_previsto'] ?? 0);
    $vencimento = (string) ($linha['vencimento'] ?? '');
    $estaPago = (string) ($linha['status'] ?? '') === 'pago';
    $estaVencido = !$estaPago && $vencimento !== '' && $vencimento < $hoje->format('Y-m-d');

    if ($estaPago) {
        $painelGeral[$obreiroIdLinha]['pago'] += $valor;
        $recebidoMes += $valor;
    } else {
        $painelGeral[$obreiroIdLinha]['aberto'] += $valor;
        $faltanteMes += $valor;
        if ($estaVencido) {
            $painelGeral[$obreiroIdLinha]['atrasado'] += $valor;
            $painelGeral[$obreiroIdLinha]['vencidos']++;
            $emAtrasoGeral++;
        }
    }

    $painelGeral[$obreiroIdLinha]['itens'][] = $linha + ['esta_vencido' => $estaVencido];
}

// Garante consistÃªncia do indicador "falta no mÃªs": quando nÃ£o existir mensalidade
// lanÃ§ada no mÃªs vigente para o obreiro, considera a mensalidade padrÃ£o como pendente.
foreach ($obreirosPainel as $obreiroBase) {
    $obreiroIdBase = (string) ($obreiroBase['id'] ?? '');
    if ($obreiroIdBase === '') {
        continue;
    }

    if (!isset($painelGeral[$obreiroIdBase])) {
        $painelGeral[$obreiroIdBase] = [
            'obreiro_id' => $obreiroIdBase,
            'nome' => (string) ($obreiroBase['nome'] ?? 'Obreiro'),
            'pago' => 0.0,
            'aberto' => 0.0,
            'atrasado' => 0.0,
            'vencidos' => 0,
            'itens' => [],
        ];
    }

    $temMensalidadeMes = false;
    foreach ($painelGeral[$obreiroIdBase]['itens'] as $itemBase) {
        if (strtolower((string) ($itemBase['tipo_obrigacao'] ?? '')) === 'mensalidade') {
            $temMensalidadeMes = true;
            break;
        }
    }

    if (!$temMensalidadeMes) {
        $vencimentoPadrao = sprintf('%04d-%02d-%02d', $anoAtual, $mesAtual, min(28, max(1, 10)));
        $estaVencidoPadrao = $vencimentoPadrao < $hoje->format('Y-m-d');
        $painelGeral[$obreiroIdBase]['aberto'] += $mensalidadePadrao;
        if ($estaVencidoPadrao) {
            $painelGeral[$obreiroIdBase]['atrasado'] += $mensalidadePadrao;
            $painelGeral[$obreiroIdBase]['vencidos']++;
            $emAtrasoGeral++;
        }
        $painelGeral[$obreiroIdBase]['itens'][] = [
            'parcela_id' => 0,
            'titulo' => 'Contribuicao mensal da Loja',
            'tipo_obrigacao' => 'mensalidade',
            'valor_previsto' => $mensalidadePadrao,
            'vencimento' => $vencimentoPadrao,
            'status' => 'pendente',
            'pago_em' => null,
            'esta_vencido' => $estaVencidoPadrao,
        ];
        $faltanteMes += $mensalidadePadrao;
    }
}

$irmaosAPrumo = 0;
foreach ($painelGeral as $registro) {
    if ((float) ($registro['aberto'] ?? 0) <= 0.0 && (float) ($registro['pago'] ?? 0) > 0.0) {
        $irmaosAPrumo++;
    }
}

uasort($painelGeral, static function (array $a, array $b): int {
    $cmpAtraso = ((int) ($b['vencidos'] ?? 0)) <=> ((int) ($a['vencidos'] ?? 0));
    if ($cmpAtraso !== 0) {
        return $cmpAtraso;
    }
    $cmpAberto = ((float) ($b['aberto'] ?? 0)) <=> ((float) ($a['aberto'] ?? 0));
    if ($cmpAberto !== 0) {
        return $cmpAberto;
    }
    return strcmp((string) ($a['nome'] ?? ''), (string) ($b['nome'] ?? ''));
});

$registroSelecionado = $selectedObreiroId !== '' ? ($painelGeral[$selectedObreiroId] ?? null) : null;
$itensSelecionados = $registroSelecionado['itens'] ?? [];
$itensSelecionadosPago = array_values(array_filter($itensSelecionados, static function (array $item): bool {
    return (string) ($item['status'] ?? '') === 'pago';
}));
$itensSelecionadosAberto = array_values(array_filter($itensSelecionados, static function (array $item): bool {
    return (string) ($item['status'] ?? '') !== 'pago';
}));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento Tesouraria - Tesouraria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[11px\] {
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
<body class="erp-readable min-h-screen bg-[linear-gradient(180deg,#f7f3ea_0%,#f5f5f4_40%,#ffffff_100%)] text-slate-900">
<div class="mx-auto max-w-7xl px-4 py-8">
    <section class="rounded-[2rem] bg-slate-950 px-6 py-7 text-stone-100 shadow-2xl">
        <p class="text-xs uppercase tracking-[0.28em] text-amber-300">Tesouraria</p>
        <div class="mt-3 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <h1 class="text-3xl font-semibold">Acompanhamento financeiro</h1>
                <p class="mt-2 text-sm text-slate-300">Panorama geral do passado e do mes vigente, com leitura intuitiva de quem esta a prumo e quem ja tem pendencia vencida.</p>
            </div>
            <div class="flex flex-wrap gap-3 text-sm">
                <a href="/dashboard" class="rounded-full border border-white/15 px-4 py-2 hover:bg-white/10">Painel</a>
                <a href="/tesouraria/caixa" class="rounded-full border border-white/15 px-4 py-2 hover:bg-white/10">Caixa da Loja</a>
                <a href="/tesouraria/comprovantes" class="rounded-full border border-white/15 px-4 py-2 hover:bg-white/10">Comprovantes PIX</a>
            </div>
        </div>
    </section>

    <section class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Resumo do passado</div>
            <div class="mt-2 text-3xl font-semibold"><?php echo $formatCurrency($historicoGeral['total_passado'] ?? 0); ?></div>
            <div class="mt-2 text-sm text-slate-700">Entradas registradas antes de <?php echo htmlspecialchars($tituloMesAtual); ?>.</div>
        </div>
        <div class="rounded-[1.5rem] bg-emerald-50 p-5 shadow-sm">
            <div class="text-xs uppercase tracking-[0.16em] text-emerald-500">Pago no mÃªs vigente</div>
            <div class="mt-2 text-3xl font-semibold text-emerald-700"><?php echo $formatCurrency($recebidoMes); ?></div>
            <div class="mt-2 text-sm text-emerald-700"><?php echo htmlspecialchars($competenciaAtual); ?>.</div>
        </div>
        <div class="rounded-[1.5rem] bg-amber-50 p-5 shadow-sm">
            <div class="text-xs uppercase tracking-[0.16em] text-amber-500">Ainda falta no mÃªs</div>
            <div class="mt-2 text-3xl font-semibold text-amber-700"><?php echo $formatCurrency($faltanteMes); ?></div>
            <div class="mt-2 text-sm text-amber-700">ObrigaÃ§Ãµes abertas do mÃªs vigente.</div>
        </div>
        <div class="rounded-[1.5rem] bg-sky-50 p-5 shadow-sm">
            <div class="text-xs uppercase tracking-[0.16em] text-sky-500">A prumo com a tesouraria</div>
            <div class="mt-2 text-3xl font-semibold text-sky-800"><?php echo (int) $irmaosAPrumo; ?></div>
            <div class="mt-2 text-sm text-sky-700">IrmÃ£os sem pendÃªncia vencida no mÃªs.</div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <aside class="space-y-6">
            <section class="rounded-[2rem] bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Panorama geral dos obreiros</h2>
                <form method="get" action="/tesouraria/obrigacoes" class="mt-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Selecionar obreiro</label>
                    <select name="obreiro_id" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm">
                        <option value="">VisÃ£o geral (sem detalhe individual)</option>
                        <?php foreach ($painelGeral as $registroOpcao): ?>
                            <option value="<?php echo htmlspecialchars((string) $registroOpcao['obreiro_id']); ?>" <?php echo $selectedObreiroId === (string) $registroOpcao['obreiro_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $registroOpcao['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="mt-3 w-full rounded-2xl border border-stone-300 px-4 py-2.5 text-sm hover:bg-stone-50">Abrir selecionado</button>
                </form>

                <div class="mt-4 max-h-[620px] space-y-3 overflow-y-auto pr-1">
                    <?php foreach ($painelGeral as $registro): ?>
                        <?php
                        $isSelected = $selectedObreiroId === (string) $registro['obreiro_id'];
                        $classeCard = 'border-stone-200 bg-stone-50';
                        $classeNome = 'text-slate-900';
                        if ((int) ($registro['vencidos'] ?? 0) > 0) {
                            $classeCard = 'border-rose-200 bg-rose-50';
                            $classeNome = 'text-rose-800';
                        } elseif ((float) ($registro['aberto'] ?? 0) <= 0.0 && (float) ($registro['pago'] ?? 0) > 0.0) {
                            $classeCard = 'border-emerald-100 bg-emerald-50';
                            $classeNome = 'text-emerald-800';
                        }
                        $classeSelecionado = $isSelected ? 'ring-2 ring-slate-900' : '';
                        ?>
                        <a href="/tesouraria/obrigacoes?obreiro_id=<?php echo urlencode((string) $registro['obreiro_id']); ?>" class="block rounded-[1.35rem] border px-4 py-4 <?php echo $classeCard . ' ' . $classeSelecionado; ?>">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-semibold <?php echo $classeNome; ?>"><?php echo htmlspecialchars((string) $registro['nome']); ?></div>
                                    <div class="mt-1 text-xs text-slate-700">
                                        <?php echo $formatCurrency($registro['pago']); ?> pago â€¢ <?php echo $formatCurrency($registro['aberto']); ?> em aberto
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php if ((int) ($registro['vencidos'] ?? 0) > 0): ?>
                                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-rose-600">Em atraso</div>
                                    <?php elseif ((float) ($registro['aberto'] ?? 0) <= 0.0 && (float) ($registro['pago'] ?? 0) > 0.0): ?>
                                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-600">A prumo</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="rounded-[2rem] bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">ParÃ¢metros da Loja</h2>
                <div class="mt-4 space-y-3 text-sm text-slate-700">
                    <div class="rounded-2xl bg-stone-50 px-4 py-3">Contribuicao mensal: <span class="font-semibold text-slate-900"><?php echo $formatCurrency($mensalidadePadrao); ?></span></div>
                    <div class="rounded-2xl bg-stone-50 px-4 py-3">Biblioteca: <span class="font-semibold text-slate-900"><?php echo $formatCurrency($bibliotecaPadrao); ?></span></div>
                    <div class="rounded-2xl bg-stone-50 px-4 py-3">PIX <?php echo htmlspecialchars($pixTipo); ?>: <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($pixValor ?: 'NÃ£o informado'); ?></span></div>
                </div>
            </section>
        </aside>

        <main class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">MÃªs vigente</p>
                        <h2 class="mt-1 text-2xl font-semibold"><?php echo htmlspecialchars($tituloMesAtual); ?></h2>
                        <p class="mt-2 text-sm text-slate-700">Panorama do mÃªs atual com foco no que jÃ¡ entrou e no que ainda falta confirmar.</p>
                    </div>
                    <div class="rounded-[1.3rem] bg-stone-50 px-4 py-3 text-sm text-slate-700">
                        <?php echo count($painelGeral); ?> obreiros com obrigacoes do mes
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <section class="rounded-[1.6rem] border border-emerald-100 bg-emerald-50 p-5">
                        <p class="text-xs uppercase tracking-[0.16em] text-emerald-500">Quem jÃ¡ pagou</p>
                        <div class="mt-2 text-3xl font-semibold text-emerald-800"><?php echo $formatCurrency($recebidoMes); ?></div>
                        <div class="mt-4 grid gap-3">
                            <?php
                            $pagosMes = array_values(array_filter($painelGeral, static function (array $registro): bool {
                                return (float) ($registro['pago'] ?? 0) > 0;
                            }));
                            if ($pagosMes === []):
                            ?>
                                <div class="rounded-2xl bg-white/80 px-4 py-4 text-sm text-emerald-700">Nenhum recebimento confirmado no mÃªs vigente.</div>
                            <?php endif; ?>
                            <?php foreach ($pagosMes as $registroPago): ?>
                                <div class="rounded-2xl bg-white px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <a href="/tesouraria/obrigacoes?obreiro_id=<?php echo urlencode((string) ($registroPago['obreiro_id'] ?? '')); ?>#detalhe-individual" class="font-medium text-slate-900 underline decoration-dotted underline-offset-4 hover:text-slate-700">
                                            <?php echo htmlspecialchars((string) $registroPago['nome']); ?>
                                        </a>
                                        <div class="font-semibold text-emerald-700"><?php echo $formatCurrency($registroPago['pago']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="rounded-[1.6rem] border border-amber-100 bg-amber-50 p-5">
                        <p class="text-xs uppercase tracking-[0.16em] text-amber-500">Quem ainda falta</p>
                        <div class="mt-2 text-3xl font-semibold text-amber-800"><?php echo $formatCurrency($faltanteMes); ?></div>
                        <div class="mt-4 grid gap-3">
                            <?php
                            $faltantesMes = array_values(array_filter($painelGeral, static function (array $registro): bool {
                                return (float) ($registro['aberto'] ?? 0) > 0;
                            }));
                            if ($faltantesMes === []):
                            ?>
                                <div class="rounded-2xl bg-white/80 px-4 py-4 text-sm text-amber-700">NÃ£o hÃ¡ faltantes no mÃªs vigente.</div>
                            <?php endif; ?>
                            <?php foreach ($faltantesMes as $registroAberto): ?>
                                <div class="rounded-2xl bg-white px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <a href="/tesouraria/obrigacoes?obreiro_id=<?php echo urlencode((string) ($registroAberto['obreiro_id'] ?? '')); ?>#detalhe-individual" class="font-medium text-slate-900 underline decoration-dotted underline-offset-4 hover:text-slate-700">
                                            <?php echo htmlspecialchars((string) $registroAberto['nome']); ?>
                                        </a>
                                        <div class="font-semibold text-amber-700"><?php echo $formatCurrency($registroAberto['aberto']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </section>

            <?php if ($selectedObreiroId !== '' && $registroSelecionado): ?>
                <section id="detalhe-individual" class="rounded-[2rem] bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Detalhe individual</p>
                            <h3 class="mt-1 text-2xl font-semibold"><?php echo htmlspecialchars($selectedObreiroNome); ?></h3>
                        </div>
                        <div class="text-sm text-slate-700">Abrir o detalhe individual e opcional para a operaÃ§Ã£o do tesoureiro.</div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.3rem] bg-emerald-50 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-emerald-500">JÃ¡ pago</div>
                            <div class="mt-1 text-2xl font-semibold text-emerald-700"><?php echo $formatCurrency($registroSelecionado['pago']); ?></div>
                        </div>
                        <div class="rounded-[1.3rem] bg-amber-50 p-4">
                            <div class="text-xs uppercase tracking-[0.16em] text-amber-500">Falta no mÃªs</div>
                            <div class="mt-1 text-2xl font-semibold text-amber-700"><?php echo $formatCurrency($registroSelecionado['aberto']); ?></div>
                        </div>
                        <?php if ((int) ($registroSelecionado['vencidos'] ?? 0) > 0): ?>
                            <div class="rounded-[1.3rem] bg-rose-50 p-4">
                                <div class="text-xs uppercase tracking-[0.16em] text-rose-400">AtenÃ§Ã£o</div>
                                <div class="mt-1 text-2xl font-semibold text-rose-700"><?php echo (int) $registroSelecionado['vencidos']; ?></div>
                                <div class="mt-2 text-sm text-rose-700">PendÃªncias jÃ¡ vencidas.</div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center justify-center rounded-[1.3rem] bg-[linear-gradient(135deg,#eff6ff_0%,#dbeafe_100%)] p-4">
                                <div class="text-center text-lg font-semibold text-sky-900">A prumo com a tesouraria</div>
                            </div>
                        <?php endif; ?>
                        <div class="rounded-[1.3rem] bg-white p-4 shadow-sm">
                            <div class="text-xs uppercase tracking-[0.16em] text-slate-400">PIX</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($pixTipo); ?></div>
                            <div class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($pixValor ?: 'NÃ£o informado'); ?></div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                        <section class="rounded-[1.6rem] border border-emerald-100 bg-emerald-50 p-5">
                            <p class="text-xs uppercase tracking-[0.16em] text-emerald-500">JÃ¡ pago no mÃªs</p>
                            <div class="mt-4 space-y-3">
                                <?php if ($itensSelecionadosPago === []): ?>
                                    <div class="rounded-2xl bg-white/80 px-4 py-4 text-sm text-emerald-700">Nenhum pagamento confirmado para este obreiro no mÃªs vigente.</div>
                                <?php endif; ?>
                                <?php foreach ($itensSelecionadosPago as $linha): ?>
                                    <article class="rounded-2xl bg-white px-4 py-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="font-medium text-slate-900"><?php echo htmlspecialchars((string) ($linha['titulo'] ?? 'Obrigacao')); ?></div>
                                                <div class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($competenciaAtual); ?> â€¢ pago em <?php echo !empty($linha['pago_em']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $linha['pago_em']))) : 'data nÃ£o informada'; ?></div>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-semibold text-emerald-700"><?php echo $formatCurrency($linha['valor_previsto'] ?? 0); ?></div>
                                                <a href="/tesouraria/obrigacoes/parcela/recibo?id=<?php echo (int) ($linha['parcela_id'] ?? 0); ?>" target="_blank" class="mt-1 inline-block text-xs font-medium text-slate-700 underline">Emitir recibo</a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="rounded-[1.6rem] border border-amber-100 bg-amber-50 p-5">
                            <p class="text-xs uppercase tracking-[0.16em] text-amber-500">Falta confirmar no mÃªs</p>
                            <div class="mt-4 space-y-3">
                                <?php if ($itensSelecionadosAberto === []): ?>
                                    <div class="rounded-2xl bg-white/80 px-4 py-4 text-sm text-amber-700">Nada pendente para este obreiro no mÃªs vigente.</div>
                                <?php endif; ?>
                                <?php foreach ($itensSelecionadosAberto as $linha): ?>
                                    <?php
                                    $linhaAtrasada = !empty($linha['esta_vencido']);
                                    $linhaClasse = $linhaAtrasada ? 'border-rose-200 bg-rose-50' : 'border-stone-200 bg-white';
                                    ?>
                                    <article class="rounded-2xl border px-4 py-4 <?php echo $linhaClasse; ?>">
                                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                            <div>
                                                <div class="font-medium text-slate-900"><?php echo htmlspecialchars((string) ($linha['titulo'] ?? 'Obrigacao')); ?></div>
                                                <div class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($competenciaAtual); ?> â€¢ vence em <?php echo !empty($linha['vencimento']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $linha['vencimento']))) : '-'; ?></div>
                                                <?php if ($linhaAtrasada): ?>
                                                    <div class="mt-2 inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-rose-700">Em atraso</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="xl:min-w-[420px]">
                                                <div class="space-y-2 rounded-2xl bg-white p-3 shadow-sm">
                                                    <form action="/tesouraria/obrigacoes/parcela/quitar" method="post" class="grid gap-2 sm:grid-cols-[1fr_1fr_auto_auto]">
                                                        <input type="hidden" name="parcela_id" value="<?php echo (int) ($linha['parcela_id'] ?? 0); ?>">
                                                        <input type="hidden" name="obreiro_id" value="<?php echo htmlspecialchars($selectedObreiroId); ?>">
                                                        <input type="date" name="pago_em" value="<?php echo date('Y-m-d'); ?>" class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                                                        <input type="number" name="valor_pago" step="0.01" min="0.01" value="<?php echo htmlspecialchars((string) ($linha['valor_previsto'] ?? 0)); ?>" class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                                                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Confirmar pagto</button>
                                                        <button type="button" class="rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700" onclick="this.closest('.space-y-2').querySelector('.edicao-parcela').classList.toggle('hidden')">Alterar</button>
                                                    </form>
                                                    <form action="/tesouraria/obrigacoes/parcela/atualizar" method="post" class="edicao-parcela hidden grid gap-2 border-t border-stone-200 pt-3 sm:grid-cols-4">
                                                        <input type="hidden" name="parcela_id" value="<?php echo (int) ($linha['parcela_id'] ?? 0); ?>">
                                                        <input type="hidden" name="obreiro_id" value="<?php echo htmlspecialchars($selectedObreiroId); ?>">
                                                        <input type="number" name="competencia_mes" min="1" max="12" value="<?php echo $mesAtual; ?>" class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                                                        <input type="number" name="competencia_ano" min="2020" value="<?php echo $anoAtual; ?>" class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                                                        <input type="date" name="vencimento" value="<?php echo htmlspecialchars((string) ($linha['vencimento'] ?? '')); ?>" class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                                                        <input type="number" name="valor_previsto" step="0.01" min="0.01" value="<?php echo htmlspecialchars((string) ($linha['valor_previsto'] ?? 0)); ?>" class="rounded-xl border border-stone-300 px-3 py-2 text-sm">
                                                        <textarea name="observacao" rows="2" class="sm:col-span-4 rounded-xl border border-stone-300 px-3 py-2 text-sm" placeholder="Motivo do ajuste"></textarea>
                                                        <button type="submit" class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white">Salvar ajuste</button>
                                                    </form>
                                                    <form action="/tesouraria/obrigacoes/parcela/excluir" method="post" onsubmit="return confirm('Excluir esta cobranca?');">
                                                        <input type="hidden" name="parcela_id" value="<?php echo (int) ($linha['parcela_id'] ?? 0); ?>">
                                                        <input type="hidden" name="obreiro_id" value="<?php echo htmlspecialchars($selectedObreiroId); ?>">
                                                        <button type="submit" class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700">Excluir</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </section>
            <?php endif; ?>

            <section class="rounded-[2rem] bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Nova obrigaÃ§Ã£o</h3>
                        <p class="mt-2 text-sm text-slate-700">LanÃ§amento guiado para o tesoureiro registrar mensalidade, contribuiÃ§Ã£o e joia com o contexto financeiro correto desde a data de inÃ­cio.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="/tesouraria/obrigacoes" class="rounded-2xl border border-stone-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-stone-50">Cancelar</a>
                        <a href="/dashboard" class="rounded-2xl border border-stone-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-stone-50">Voltar ao menu principal</a>
                    </div>
                </div>
                <form action="/tesouraria/obrigacoes/criar" method="post" class="mt-5 grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <select name="obreiro_id" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm" required>
                            <option value="">Selecione o obreiro</option>
                            <?php foreach ($obreirosPainel as $obreiro): ?>
                                <option value="<?php echo htmlspecialchars((string) $obreiro['id']); ?>" <?php echo $selectedObreiroId === (string) $obreiro['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $obreiro['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label class="space-y-2">
                        <span class="block text-sm font-medium text-slate-700">Tipo da obrigaÃ§Ã£o</span>
                        <select name="tipo_obrigacao" id="tipo_obrigacao" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm">
                        <option value="mensalidade">Contribuicao mensal</option>
                        <option value="biblioteca">Biblioteca</option>
                        <option value="joia">Joia</option>
                        <option value="doacao">DoaÃ§Ã£o</option>
                        <option value="outra">Outra</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="block text-sm font-medium text-slate-700">Forma de cobranÃ§a</span>
                        <select name="recorrencia" id="recorrencia" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm">
                        <option value="mensal">Mensal</option>
                        <option value="anual">Anual</option>
                        <option value="parcelado">Parcelado</option>
                        <option value="avulsa">Avulsa</option>
                        </select>
                    </label>
                    <div class="md:col-span-2 grid gap-3 rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4 lg:grid-cols-[1.15fr_0.85fr]">
                        <div class="space-y-3">
                            <label class="space-y-2">
                                <span class="block text-sm font-medium text-slate-700">TÃ­tulo ou contexto</span>
                                <input type="text" name="titulo" id="titulo_obrigacao" placeholder="Ex.: Joia de exaltaÃ§Ã£o" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm" required>
                            </label>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="block text-sm font-medium text-slate-700">Valor total</span>
                                    <input type="number" name="valor_base" id="valor_base" step="0.01" min="0.01" value="<?php echo htmlspecialchars(number_format($mensalidadePadrao, 2, '.', '')); ?>" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm" required>
                                </label>
                                <label class="space-y-2">
                                    <span class="block text-sm font-medium text-slate-700">Parcelas</span>
                                    <input type="number" name="parcelas_total" id="parcelas_total" min="1" value="1" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm">
                                </label>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="space-y-2">
                                    <span class="block text-sm font-medium text-slate-700">InÃ­cio do lanÃ§amento</span>
                                    <input type="date" name="inicio_competencia" id="inicio_competencia" value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm">
                                </label>
                                <label class="space-y-2">
                                    <span class="block text-sm font-medium text-slate-700">Fim da cobranÃ§a</span>
                                    <input type="date" name="fim_competencia" id="fim_competencia" class="w-full rounded-2xl border border-stone-300 px-3 py-2.5 text-sm">
                                </label>
                            </div>
                        </div>
                        <div class="rounded-[1.35rem] bg-white p-4 shadow-sm">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Resumo automÃ¡tico</div>
                            <div id="resumo_obrigacao_principal" class="mt-3 text-lg font-semibold text-slate-900">Contribuicao mensal padrÃ£o da Loja</div>
                            <div id="resumo_obrigacao_secundario" class="mt-2 text-sm text-slate-700">Valor total de <?php echo $formatCurrency($mensalidadePadrao); ?> com vencimento guiado a partir da data informada.</div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-stone-50 px-4 py-3">
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">Cada parcela</div>
                                    <div id="resumo_valor_parcela" class="mt-1 text-base font-semibold text-slate-900"><?php echo $formatCurrency($mensalidadePadrao); ?></div>
                                </div>
                                <div class="rounded-2xl bg-stone-50 px-4 py-3">
                                    <div class="text-[11px] uppercase tracking-[0.16em] text-slate-400">TÃ©rmino previsto</div>
                                    <div id="resumo_fim_previsto" class="mt-1 text-base font-semibold text-slate-900">No mesmo mÃªs</div>
                                </div>
                            </div>
                            <div id="resumo_alerta_joia" class="mt-4 hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Joia sugerida no valor de 1 salario minimo vigente: <?php echo $formatCurrency($salarioMinimoPadrao); ?>.
                            </div>
                        </div>
                    </div>
                    <select name="categoria_id" id="categoria_id" class="md:col-span-2 rounded-2xl border border-stone-300 px-3 py-2.5 text-sm">
                        <option value="">Categoria financeira</option>
                        <?php foreach ($categoriasEntrada as $categoria): ?>
                            <option value="<?php echo (int) $categoria['id']; ?>" data-nome="<?php echo htmlspecialchars((string) $categoria['nome']); ?>"><?php echo htmlspecialchars((string) $categoria['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="instrucoes_pagamento" id="instrucoes_pagamento" rows="2" class="md:col-span-2 rounded-2xl border border-stone-300 px-3 py-2.5 text-sm" placeholder="InstruÃ§Ãµes de pagamento"></textarea>
                    <div class="md:col-span-2 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <a href="/tesouraria/obrigacoes" class="rounded-2xl border border-stone-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-stone-50">Cancelar</a>
                        <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Salvar obrigaÃ§Ã£o</button>
                    </div>
                </form>
            </section>
        </main>
    </section>
</div>
<?php if ($selectedObreiroId !== '' && $registroSelecionado): ?>
<script>
    (function () {
        const detalhe = document.getElementById('detalhe-individual');
        if (!detalhe) return;
        if (window.location.hash === '#detalhe-individual') {
            detalhe.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        detalhe.classList.add('ring-2', 'ring-amber-300');
        setTimeout(() => detalhe.classList.remove('ring-2', 'ring-amber-300'), 2200);
    })();
</script>
<?php endif; ?>
<script>
    (function () {
        const tipo = document.getElementById('tipo_obrigacao');
        const recorrencia = document.getElementById('recorrencia');
        const titulo = document.getElementById('titulo_obrigacao');
        const valorBase = document.getElementById('valor_base');
        const parcelasTotal = document.getElementById('parcelas_total');
        const inicio = document.getElementById('inicio_competencia');
        const fim = document.getElementById('fim_competencia');
        const categoria = document.getElementById('categoria_id');
        const instrucoes = document.getElementById('instrucoes_pagamento');
        const resumoPrincipal = document.getElementById('resumo_obrigacao_principal');
        const resumoSecundario = document.getElementById('resumo_obrigacao_secundario');
        const resumoParcela = document.getElementById('resumo_valor_parcela');
        const resumoFim = document.getElementById('resumo_fim_previsto');
        const alertaJoia = document.getElementById('resumo_alerta_joia');

        if (!tipo || !recorrencia || !valorBase || !parcelasTotal || !inicio) {
            return;
        }

        const salarioMinimo = <?php echo json_encode($salarioMinimoPadrao); ?>;
        const mensalidadePadrao = <?php echo json_encode($mensalidadePadrao); ?>;
        const bibliotecaPadrao = <?php echo json_encode($bibliotecaPadrao); ?>;
        const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
        const hoje = new Date();

        function addMonths(baseDate, monthsToAdd) {
            const clone = new Date(baseDate.getTime());
            clone.setMonth(clone.getMonth() + monthsToAdd);
            return clone;
        }

        function parseDate(value) {
            if (!value) {
                return null;
            }
            const parts = value.split('-').map(Number);
            if (parts.length !== 3) {
                return null;
            }
            return new Date(parts[0], parts[1] - 1, parts[2]);
        }

        function formatDate(date) {
            return date.toLocaleDateString('pt-BR');
        }

        function setCategoriaPorNome(parteNome) {
            if (!categoria) {
                return;
            }
            const alvo = parteNome.toLowerCase();
            const option = Array.from(categoria.options).find((item) => (item.dataset.nome || '').toLowerCase().includes(alvo));
            if (option) {
                categoria.value = option.value;
            }
        }

        function preencherDefaultsPorTipo() {
            if (tipo.value === 'mensalidade') {
                valorBase.value = Number(mensalidadePadrao).toFixed(2);
                if (!titulo.value.trim()) {
                    titulo.value = 'Contribuicao mensal da Loja';
                }
                if (recorrencia.value === 'avulsa') {
                    recorrencia.value = 'mensal';
                }
                setCategoriaPorNome('mensalidade');
            }

            if (tipo.value === 'biblioteca') {
                valorBase.value = Number(bibliotecaPadrao).toFixed(2);
                if (!titulo.value.trim()) {
                    titulo.value = 'ContribuiÃ§Ã£o Biblioteca';
                }
                setCategoriaPorNome('biblioteca');
            }

            if (tipo.value === 'joia') {
                valorBase.value = Number(salarioMinimo).toFixed(2);
                if (!titulo.value.trim() || titulo.value === 'Contribuicao mensal da Loja' || titulo.value === 'ContribuiÃ§Ã£o Biblioteca') {
                    titulo.value = 'Joia';
                }
                if (recorrencia.value === 'mensal' || recorrencia.value === 'anual') {
                    recorrencia.value = 'parcelado';
                }
                if (Number(parcelasTotal.value || 0) < 2) {
                    parcelasTotal.value = '5';
                }
                setCategoriaPorNome('joia');
                if (!instrucoes.value.trim()) {
                    instrucoes.value = 'Joia no valor de 1 salÃ¡rio mÃ­nimo vigente, com acompanhamento parcelado a partir da data de lanÃ§amento.';
                }
            }
        }

        function atualizarResumo() {
            const valor = Math.max(0, Number(valorBase.value || 0));
            const parcelas = Math.max(1, Number(parcelasTotal.value || 1));
            const inicioData = parseDate(inicio.value) || hoje;
            const fimData = recorrencia.value === 'parcelado'
                ? addMonths(inicioData, parcelas - 1)
                : (fim.value ? parseDate(fim.value) : inicioData) || inicioData;
            const valorParcela = recorrencia.value === 'parcelado' ? valor / parcelas : valor;

            alertaJoia.classList.toggle('hidden', tipo.value !== 'joia');

            if (tipo.value === 'joia') {
                resumoPrincipal.textContent = titulo.value.trim() || 'Joia da Loja';
                resumoSecundario.textContent = recorrencia.value === 'parcelado'
                    ? parcelas + ' parcelas iniciando em ' + formatDate(inicioData) + ', com acompanhamento mensal ate ' + formatDate(fimData) + '.'
                    : 'Joia lancada a partir de ' + formatDate(inicioData) + '.';
            } else if (tipo.value === 'biblioteca') {
                resumoPrincipal.textContent = titulo.value.trim() || 'ContribuiÃ§Ã£o Biblioteca';
                resumoSecundario.textContent = 'ContribuiÃ§Ã£o individual vinculada ao mÃªs designado, com valor atual de ' + money.format(valor) + '.';
            } else if (tipo.value === 'mensalidade') {
                resumoPrincipal.textContent = titulo.value.trim() || 'Contribuicao mensal da Loja';
                resumoSecundario.textContent = 'Obrigacao recorrente da Loja, considerada aberta somente apos o primeiro dia util do mes seguinte.';
            } else {
                resumoPrincipal.textContent = titulo.value.trim() || 'ObrigaÃ§Ã£o financeira';
                resumoSecundario.textContent = 'Lancamento a partir de ' + formatDate(inicioData) + ' com contexto financeiro definido pelo tesoureiro.';
            }

            resumoParcela.textContent = money.format(valorParcela);
            resumoFim.textContent = recorrencia.value === 'parcelado' ? formatDate(fimData) : 'No mesmo mÃªs';

            if (recorrencia.value === 'parcelado') {
                fim.value = fimData.toISOString().slice(0, 10);
            }
        }

        tipo.addEventListener('change', function () {
            preencherDefaultsPorTipo();
            atualizarResumo();
        });
        recorrencia.addEventListener('change', atualizarResumo);
        valorBase.addEventListener('input', atualizarResumo);
        parcelasTotal.addEventListener('input', atualizarResumo);
        inicio.addEventListener('change', atualizarResumo);
        fim.addEventListener('change', atualizarResumo);
        titulo.addEventListener('input', atualizarResumo);

        preencherDefaultsPorTipo();
        atualizarResumo();
    })();
</script>
</body>
</html>

