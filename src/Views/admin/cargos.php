<?php
// Mensagem de sucesso
$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
unset($_SESSION['mensagem_sucesso']);

$cargosDisponiveis = [
    'admin' => 'Administrador',
    'chanceler' => 'Chanceler',
    'tesoureiro' => 'Tesoureiro',
    'secretario' => 'Secretário',
    'mestre_banquetes' => 'Mestre Banquetes',
    'obreiro' => 'Obreiro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Cargos - Painel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <div class="max-w-4xl mx-auto py-10">
        <h1 class="text-2xl font-bold text-cobalto mb-6">Gestão de Cargos</h1>
        <?php if ($mensagem): ?>
            <div class="mb-4 p-3 bg-green-100 border border-green-300 rounded text-green-800 text-sm">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>
        <div class="bg-white shadow rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">CIM</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Cargo Atual</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Alterar Cargo</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($obreiros as $obreiro): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome'] ?? '') ?></td>
                            <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars($obreiro['cim'] ?? '') ?></td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <?= htmlspecialchars($obreiro['cargo'] ?? '') ?>
                            </td>
                            <td class="px-4 py-2">
                                <form action="/admin/cargos/salvar" method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($obreiro['id']) ?>">
                                    <select name="cargo" class="rounded border-gray-300 p-2 text-sm">
                                        <?php foreach ($cargosDisponiveis as $valor => $label): ?>
                                            <option value="<?= $valor ?>" <?= ($obreiro['cargo'] === $valor) ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="bg-cobalto text-white px-3 py-1 rounded hover:bg-blue-700 text-sm">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
