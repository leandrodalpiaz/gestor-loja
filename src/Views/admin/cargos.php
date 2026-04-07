<?php
$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestao de Cargos - Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="max-w-7xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-cobalto mb-1">Gestao de Cargos</h1>
                <p class="text-sm text-gray-600">Fonte de verdade em `cargos` + `atribuicoes_cargo`, com historico preservado e titular unico ativo por cargo.</p>
            </div>
            <a href="/dashboard" class="text-sm text-cobalto hover:underline">Voltar ao painel</a>
        </div>

        <?php if ($mensagem): ?>
            <div class="mb-4 p-3 bg-green-100 border border-green-300 rounded text-green-800 text-sm">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-300 rounded text-red-800 text-sm">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow rounded-lg p-6 mb-8 overflow-x-auto">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Titulares Atuais</h2>
                <p class="text-sm text-gray-500">Cada troca chama a funcao central do banco e encerra automaticamente a atribuicao anterior.</p>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Cargo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Titular Atual</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Desde</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Nova Troca</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($cargosResumo as $cargo): ?>
                        <tr class="align-top">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="font-medium"><?= htmlspecialchars($cargo['nome_exibicao'] ?? '') ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($cargo['codigo'] ?? '') ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php if (!empty($cargo['titular_nome'])): ?>
                                    <div class="font-medium"><?= htmlspecialchars($cargo['titular_nome']) ?></div>
                                    <div class="text-xs text-gray-500">CIM <?= htmlspecialchars($cargo['titular_cim'] ?? '-') ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Obs.: <?= htmlspecialchars($cargo['observacao'] ?? '-') ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-500">Sem titular ativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?= !empty($cargo['inicio_em']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $cargo['inicio_em']))) : '-' ?>
                            </td>
                            <td class="px-4 py-3">
                                <form action="/admin/cargos/salvar" method="POST" class="space-y-2 min-w-80">
                                    <input type="hidden" name="cargo_codigo" value="<?= htmlspecialchars($cargo['codigo'] ?? '') ?>">
                                    <select name="obreiro_id" class="w-full rounded border-gray-300 p-2 text-sm" required>
                                        <option value="">Selecione o novo titular</option>
                                        <?php foreach ($obreiros as $obreiro): ?>
                                            <option value="<?= htmlspecialchars($obreiro['id']) ?>">
                                                <?= htmlspecialchars(($obreiro['nome_historico'] ?? $obreiro['nome'] ?? '') . ' - CIM ' . ($obreiro['cim'] ?? '-')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="observacao" class="w-full rounded border-gray-300 p-2 text-sm" placeholder="Motivo / observacao da troca">
                                    <button type="submit" class="bg-cobalto text-white px-3 py-2 rounded hover:bg-blue-700 text-sm">
                                        Salvar troca
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="bg-white shadow rounded-lg p-6 overflow-x-auto">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Historico Recente</h2>
                <p class="text-sm text-gray-500">Ultimas atribuicoes registradas no banco.</p>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Cargo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Obreiro</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Inicio</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Fim</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Observacao</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($historico as $item): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars($item['nome_exibicao'] ?? '') ?></td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <?= htmlspecialchars($item['obreiro_nome'] ?? '') ?>
                                <div class="text-xs text-gray-500">CIM <?= htmlspecialchars($item['cim'] ?? '-') ?></div>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $item['inicio_em']))) ?></td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <?= !empty($item['fim_em']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $item['fim_em']))) : 'Ativo' ?>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars($item['observacao'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
