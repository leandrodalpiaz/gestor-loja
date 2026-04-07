<?php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}
$appTitle = "Editar Obreiro - Secretaria";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalto: '#0a192f',
                        ouro: '#cfa935',
                        pedra: '#f3f4f6'
                    },
                    fontFamily: {
                        serif: ['Merriweather', 'serif'],
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="bg-pedra font-sans text-gray-800 antialiased">
    <header class="bg-cobalto text-white shadow-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-3">
            <a href="/obreiros" class="text-gray-300 hover:text-white" title="Voltar">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <h1 class="font-serif text-lg font-bold tracking-wider">
                <i class="fas fa-user-edit text-ouro mr-2"></i>Editar ficha
            </h1>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 mt-4 mb-20">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <h2 class="text-xl font-bold text-cobalto"><?= htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome']) ?></h2>
                <p class="text-sm text-gray-500 mt-1">Atualizacao cadastral operada pela Secretaria.</p>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="mx-6 mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Ficha atualizada com sucesso.</div>
            <?php endif; ?>

            <form action="/obreiros/atualizar" method="POST" class="p-6 space-y-8">
                <input type="hidden" name="id" value="<?= htmlspecialchars($obreiro['id']) ?>">

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Dados maconicos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CIM</label>
                            <input type="number" name="cim" required value="<?= htmlspecialchars($obreiro['cim'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo legado</label>
                            <input type="text" name="cargo" value="<?= htmlspecialchars($obreiro['cargo'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grau</label>
                            <select name="grau" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $grau): ?>
                                    <option value="<?= $grau ?>" <?= ($obreiro['grau'] ?? '') === $grau ? 'selected' : '' ?>><?= $grau ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loja de origem</label>
                            <input type="text" name="loja_origem" value="<?= htmlspecialchars($obreiro['loja_origem'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de iniciacao</label>
                            <input type="date" name="data_iniciacao" value="<?= htmlspecialchars($obreiro['data_iniciacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de elevacao</label>
                            <input type="date" name="data_elevacao" value="<?= htmlspecialchars($obreiro['data_elevacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de exaltacao</label>
                            <input type="date" name="data_exaltacao" value="<?= htmlspecialchars($obreiro['data_exaltacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Dados pessoais</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo civil</label>
                            <input type="text" name="nome_completo" required value="<?= htmlspecialchars($obreiro['nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome historico</label>
                            <input type="text" name="nome_historico" value="<?= htmlspecialchars($obreiro['nome_historico'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de nascimento civil</label>
                            <input type="date" name="data_nascimento_civil" value="<?= htmlspecialchars($obreiro['data_nascimento_civil'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" name="telefone" value="<?= htmlspecialchars($obreiro['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($obreiro['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profissao</label>
                            <input type="text" name="profissao" value="<?= htmlspecialchars($obreiro['profissao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Controle administrativo da Secretaria</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telegram ID</label>
                            <input type="number" name="telegram_id" value="<?= htmlspecialchars($obreiro['telegram_id'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Login na Potencia</label>
                            <input type="text" name="potencia_login" value="<?= htmlspecialchars($obreiro['potencia_login'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 mt-6">
                                <input type="checkbox" name="acesso_potencia_liberado" value="1" <?= !empty($obreiro['acesso_potencia_liberado']) ? 'checked' : '' ?> class="rounded border-gray-300">
                                Acesso na plataforma da Potencia liberado
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data da liberacao</label>
                            <input type="datetime-local" name="acesso_potencia_liberado_em" value="<?= !empty($obreiro['acesso_potencia_liberado_em']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime((string) $obreiro['acesso_potencia_liberado_em']))) : '' ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observacao da Secretaria</label>
                            <textarea name="observacao_secretaria" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($obreiro['observacao_secretaria'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="ativo" value="0">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?= ($obreiro['ativo'] ?? true) ? 'checked' : '' ?> class="h-4 w-4 text-cobalto border-gray-300 rounded">
                    <label for="ativo" class="ml-2 block text-sm text-gray-900 font-medium">Obreiro ativo na Loja</label>
                </div>

                <div class="border-t border-gray-100 bg-gray-50 -my-6 -mx-6 mt-2 p-6 flex justify-end gap-3 flex-col sm:flex-row rounded-b-xl">
                    <a href="/obreiros" class="w-full sm:w-auto text-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Voltar
                    </a>
                    <button type="submit" class="w-full sm:w-auto flex justify-center py-2 px-6 text-sm font-medium rounded-md text-white bg-cobalto hover:bg-blue-900 gap-2 items-center">
                        <i class="fas fa-save text-ouro"></i>Atualizar dados
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
