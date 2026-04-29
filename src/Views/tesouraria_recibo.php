<?php
declare(strict_types=1);

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E CONSULTA DE DADOS
// #############################################################################

$parcelaRecibo = $parcelaRecibo ?? [];
$configuracaoLoja = $configuracaoLoja ?? [];

$formatCurrency = static fn($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static fn($dateStr): string => !empty($dateStr) ? (new DateTime($dateStr))->format('d/m/Y') : '';

$nomeLoja = trim((string) ($configuracaoLoja['nome_loja'] ?? $_SESSION['tenant_name'] ?? 'Loja'));
$numeroLoja = trim((string) ($configuracaoLoja['numero_loja'] ?? ''));
$tituloTratamento = trim((string) ($configuracaoLoja['titulo_tratamento'] ?? 'Augâˆ´ Respâˆ´ Lojâˆ´ Simbâˆ´'));
$oriente = trim((string) ($configuracaoLoja['oriente'] ?? (($configuracaoLoja['cidade'] ?? 'Cidade') . ' / ' . ($configuracaoLoja['uf'] ?? 'UF'))));
$dataFundacao = $formatDate($configuracaoLoja['data_fundacao'] ?? '');
$tesoureiroNome = (string) ($_SESSION['user_name'] ?? 'Tesoureiro');

$nomeIrmao = (string) ($parcelaRecibo['obreiro_nome'] ?? 'IrmÃ£o');
$tituloContribuicao = (string) ($parcelaRecibo['titulo'] ?? 'Recebimento');
$tipoObrigacao = strtolower((string) ($parcelaRecibo['tipo_obrigacao'] ?? 'outra'));
$categoriaNome = (string) ($parcelaRecibo['categoria_nome'] ?? '');
$competencia = (string) ($parcelaRecibo['competencia_label'] ?? '');
$valorPago = (float) ($parcelaRecibo['valor_previsto'] ?? 0);
$dataContribuicao = $formatDate($parcelaRecibo['pago_em'] ?? 'now');
$numeroRecibo = str_pad((string) ($parcelaRecibo['lancamento_id'] ?? $parcelaRecibo['id'] ?? 0), 5, '0', STR_PAD_LEFT);

$discriminacao = ['titulo' => 'Outros recebimentos', 'descricao' => trim($tituloContribuicao . ($categoriaNome !== '' ? ' - ' . $categoriaNome : ''))];
if ($tipoObrigacao === 'mensalidade') {
    $discriminacao['titulo'] = 'ContribuiÃ§Ã£o mensal';
    $discriminacao['descricao'] = $competencia !== '' ? 'Referente Ã  competÃªncia ' . $competencia : $tituloContribuicao;
} elseif (stripos($tituloContribuicao, 'Inicia') !== false) {
    $discriminacao['titulo'] = 'Taxa de IniciaÃ§Ã£o';
    $discriminacao['descricao'] = $tituloContribuicao;
} elseif (stripos($tituloContribuicao, 'Eleva') !== false) {
    $discriminacao['titulo'] = 'Taxa de ElevaÃ§Ã£o';
    $discriminacao['descricao'] = $tituloContribuicao;
} elseif (stripos($tituloContribuicao, 'Exalta') !== false) {
    $discriminacao['titulo'] = 'Taxa de ExaltaÃ§Ã£o';
    $discriminacao['descricao'] = $tituloContribuicao;
}

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL E RENDERIZAÃ‡ÃƒO DA VIEW
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Recibo NÂº ' . htmlspecialchars($numeroRecibo);
$appShellDescription = 'Recibo de pagamento para impressÃ£o ou arquivamento digital.';
$appShellActiveHref = '/tesouraria/caixa';
$renderShell = ($_GET['print'] ?? 'false') !== 'true';

if ($renderShell) {
    require __DIR__ . '/partials/erp_shell_open.php';
} else {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Recibo Tesouraria - NÂº <?= htmlspecialchars($numeroRecibo) ?></title>
        <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
        <style>
            body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; font-family: 'Inter', sans-serif; }
            .font-serif { font-family: 'Playfair Display', serif; }
            @media print {
                .no-print { display: none !important; }
                body { background-color: white !important; }
                #recibo-container { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
                #recibo-paper { box-shadow: none !important; border-radius: 0 !important; border: none !important; }
                body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            }
        </style>
    </head>
    <body class="bg-gray-100">
    <?php
}
?>

<div class="max-w-4xl mx-auto" id="recibo-container">
    <?php if ($renderShell): ?>
    <div class="no-print mb-6 flex justify-between items-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">Pronto para impressÃ£o.</p>
        <div class="flex gap-2">
            <a href="/tesouraria/obrigacoes" class="btn btn-secondary">Voltar</a>
            <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        </div>
    </div>
    <?php endif; ?>

    <main class="bg-white rounded-lg shadow-lg border border-gray-200" id="recibo-paper">
        <div class="p-8 sm:p-10 lg:p-12">
            <header class="grid grid-cols-[1fr_auto] gap-8 items-start mb-10">
                <div>
                    <p class="font-serif text-lg font-bold text-gray-800 tracking-wide"><?= htmlspecialchars($tituloTratamento) ?></p>
                    <h2 class="mt-1 text-2xl font-bold text-gray-900 uppercase"><?= htmlspecialchars($nomeLoja . ' NÂº ' . $numeroLoja) ?></h2>
                    <p class="mt-2 text-xs text-gray-500">
                        Fundada em <?= htmlspecialchars($dataFundacao ?: '--/--/----') ?><br>
                        <?= htmlspecialchars($oriente) ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-500">RECIBO NÂº</p>
                    <p class="text-5xl font-bold text-blue-600 tracking-tighter"><?= htmlspecialchars($numeroRecibo) ?></p>
                </div>
            </header>

            <div class="mb-10">
                <p class="text-base leading-relaxed text-gray-700">
                    Recebemos de <strong class="font-semibold text-gray-900"><?= htmlspecialchars($nomeIrmao) ?></strong>,
                    a importÃ¢ncia de <strong class="font-semibold text-gray-900"><?= $formatCurrency($valorPago) ?></strong>,
                    referente ao que se segue:
                </p>
            </div>

            <div class="border rounded-lg mb-10">
                <div class="grid grid-cols-[1fr_auto] gap-4 p-4">
                    <div>
                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($discriminacao['titulo']) ?></p>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($discriminacao['descricao']) ?></p>
                    </div>
                    <div class="text-right font-semibold text-gray-800">
                        <?= $formatCurrency($valorPago) ?>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mb-12">
                <div class="text-right w-64">
                    <p class="text-sm font-medium text-gray-500">TOTAL RECEBIDO</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= $formatCurrency($valorPago) ?></p>
                </div>
            </div>

            <footer class="grid sm:grid-cols-2 gap-12 items-end pt-8 border-t border-gray-200">
                <div class="text-left">
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($configuracaoLoja['cidade'] ?? 'Cidade') ?>, <?= htmlspecialchars($dataContribuicao) ?></p>
                </div>
                <div class="text-center">
                    <div class="border-b-2 border-gray-400 border-dotted pb-2 w-full max-w-xs mx-auto"></div>
                    <p class="mt-2 text-sm font-semibold text-gray-800"><?= htmlspecialchars($tesoureiroNome) ?></p>
                    <p class="text-xs text-gray-600">Tesoureiro</p>
                </div>
            </footer>
        </div>
    </main>
</div>

<?php if ($renderShell) { ?>
<style>
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        transition: background-color 0.2s ease, color 0.2s ease;
        text-decoration: none;
    }
    .btn-primary {
        background-color: #2563eb;
        color: #ffffff;
    }
    .btn-primary:hover {
        background-color: #1d4ed8;
    }
    .btn-secondary {
        background-color: #e5e7eb;
        color: #1f2937;
    }
    .btn-secondary:hover {
        background-color: #d1d5db;
    }
</style>
<?php
    require __DIR__ . '/partials/erp_shell_close.php';
} else {
    ?>
    </body>
    </html>
    <?php
}
?>


