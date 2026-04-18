<?php
$parcelaRecibo = $parcelaRecibo ?? [];
$configuracaoLoja = $configuracaoLoja ?? [];
$formatCurrency = static function ($value): string {
    return number_format((float) $value, 2, ',', '.');
};
$nomeLoja = trim((string) ($configuracaoLoja['nome_loja'] ?? 'Renascenca'));
$numeroLoja = trim((string) ($configuracaoLoja['numero_loja'] ?? '270'));
$tituloTratamento = trim((string) ($configuracaoLoja['titulo_tratamento'] ?? 'Aug Resp Loj Simb'));
$oriente = trim((string) ($configuracaoLoja['oriente'] ?? (($configuracaoLoja['cidade'] ?? 'Arroio do Sal') . ' / ' . ($configuracaoLoja['uf'] ?? 'RS'))));
$dataFundacao = !empty($configuracaoLoja['data_fundacao']) ? date('d/m/Y', strtotime((string) $configuracaoLoja['data_fundacao'])) : '';
$nomeIrmao = (string) ($parcelaRecibo['obreiro_nome'] ?? 'Irmao');
$tituloPagamento = (string) ($parcelaRecibo['titulo'] ?? 'Recebimento');
$tipoObrigacao = strtolower((string) ($parcelaRecibo['tipo_obrigacao'] ?? 'outra'));
$categoriaNome = (string) ($parcelaRecibo['categoria_nome'] ?? '');
$competencia = (string) ($parcelaRecibo['competencia_label'] ?? '');
$valorPago = (float) ($parcelaRecibo['valor_previsto'] ?? 0);
$dataPagamento = !empty($parcelaRecibo['pago_em']) ? date('d/m/Y', strtotime((string) $parcelaRecibo['pago_em'])) : date('d/m/Y');
$numeroRecibo = str_pad((string) ($parcelaRecibo['lancamento_id'] ?? $parcelaRecibo['id'] ?? 0), 5, '0', STR_PAD_LEFT);

$campos = [
    'Mensalidade' => '',
    'Iniciacao' => '',
    'Elevacao' => '',
    'Exaltacao' => '',
    'Outros recebimentos' => '',
];

if ($tipoObrigacao === 'mensalidade') {
    $campos['Mensalidade'] = $competencia !== '' ? 'Ref. ' . $competencia : $tituloPagamento;
} elseif (str_contains($tituloPagamento, 'Inici')) {
    $campos['Iniciacao'] = $tituloPagamento;
} elseif (str_contains($tituloPagamento, 'Eleva')) {
    $campos['Elevacao'] = $tituloPagamento;
} elseif (str_contains($tituloPagamento, 'Exalta')) {
    $campos['Exaltacao'] = $tituloPagamento;
} else {
    $campos['Outros recebimentos'] = trim($tituloPagamento . ($categoriaNome !== '' ? ' - ' . $categoriaNome : ''));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo Tesouraria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
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
<body class="erp-readable min-h-screen bg-stone-100 p-6 text-slate-900">
    <div class="no-print mx-auto mb-4 flex max-w-3xl justify-end">
        <button onclick="window.print()" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Imprimir recibo</button>
    </div>

    <main class="mx-auto max-w-3xl rounded-[2rem] border-[3px] border-stone-800 bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-6">
            <div class="max-w-xl">
                <div class="text-sm font-semibold uppercase tracking-[0.22em]"><?php echo htmlspecialchars($tituloTratamento); ?></div>
                <div class="mt-2 text-2xl font-bold uppercase"><?php echo htmlspecialchars($nomeLoja . ' n ' . $numeroLoja); ?></div>
                <div class="mt-2 text-sm">Fundada em <?php echo htmlspecialchars($dataFundacao ?: '--/--/----'); ?></div>
                <div class="text-sm"><?php echo htmlspecialchars($oriente); ?></div>
            </div>
            <div class="min-w-[110px] rounded-[1.5rem] border-2 border-stone-700 px-4 py-3 text-center">
                <div class="text-xs uppercase tracking-[0.18em] text-slate-700">No</div>
                <div class="mt-1 text-3xl font-bold text-rose-700"><?php echo htmlspecialchars($numeroRecibo); ?></div>
            </div>
        </div>

        <div class="mt-8 grid gap-4">
            <?php foreach ($campos as $rotulo => $texto): ?>
                <div class="grid grid-cols-[160px_1fr_120px] items-end gap-3 border-b border-stone-300 pb-2">
                    <div class="text-base"><?php echo htmlspecialchars($rotulo); ?>:</div>
                    <div class="min-h-[28px] text-lg"><?php echo htmlspecialchars($texto); ?></div>
                    <div class="text-right text-lg font-semibold"><?php echo $texto !== '' ? 'R$ ' . $formatCurrency($valorPago) : 'R$'; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <div class="border-b border-stone-400 pb-2 text-xl uppercase"><?php echo htmlspecialchars($nomeIrmao); ?></div>
                <div class="mt-2 text-base">Ir.</div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold">Total Recebido R$ <?php echo $formatCurrency($valorPago); ?></div>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="text-lg">
                Arroio do Sal, <?php echo htmlspecialchars($dataPagamento); ?>
            </div>
            <div class="min-w-[240px] text-center">
                <div class="border-b border-stone-500 pb-2 text-lg uppercase"><?php echo htmlspecialchars($tesoureiroNome ?? 'Tesoureiro'); ?></div>
                <div class="mt-2 text-base">Tesoureiro</div>
            </div>
        </div>
    </main>
</body>
</html>

