<?php
// src/Views/obreiro_editar.php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}
$appTitle = "Editar Obreiro - Chancelaria";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appTitle) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cobalto': '#0a192f',
                        'ouro': '#cfa935',
                        'pedra': '#f3f4f6',
                        'pedra-escura': '#e5e7eb'
                    },
                    fontFamily: {
                        'serif': ['Merriweather', 'serif'],
                        'sans': ['Inter', 'sans-serif'],
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
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/obreiros" class="text-gray-300 hover:text-white transition-colors" title="Voltar">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                <h1 class="font-serif text-lg font-bold tracking-wider">
                    <i class="fas fa-user-edit text-ouro mr-2"></i> Editar Ficha: <?= htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome']) ?>
                </h1>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8 mt-4 mb-20">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            
            <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-cobalto">Atualizar Cadastro</h2>
                    <p class="text-sm text-gray-500 mt-1">Verifique as efemérides para notificação automática.</p>
                </div>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 m-6 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">Ficha do Irmão atualizada com sucesso.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="/obreiros/atualizar" method="POST" class="p-6 space-y-8">
                <input type="hidden" name="id" value="<?= htmlspecialchars($obreiro['id']) ?>">
                
                <!-- Secão Maçônica -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2 mb-4">Dados Maçônicos</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CIM (Matrícula) *</label>
                            <input type="number" name="cim" required value="<?= htmlspecialchars($obreiro['cim'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo Atual</label>
                            <select name="cargo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                                <option value="" <?= empty($obreiro['cargo']) ? 'selected' : '' ?>>Sem cargo (Somente membro)</option>
                                <option value="Venerável" <?= ($obreiro['cargo'] ?? '') === 'Venerável' ? 'selected' : '' ?>>Venerável Mestre</option>
                                <option value="1º Vigilante" <?= ($obreiro['cargo'] ?? '') === '1º Vigilante' ? 'selected' : '' ?>>1º Vigilante</option>
                                <option value="2º Vigilante" <?= ($obreiro['cargo'] ?? '') === '2º Vigilante' ? 'selected' : '' ?>>2º Vigilante</option>
                                <option value="Secretário" <?= ($obreiro['cargo'] ?? '') === 'Secretário' ? 'selected' : '' ?>>Secretário</option>
                                <option value="Tesoureiro" <?= ($obreiro['cargo'] ?? '') === 'Tesoureiro' ? 'selected' : '' ?>>Tesoureiro</option>
                                <option value="Chanceler" <?= ($obreiro['cargo'] ?? '') === 'Chanceler' ? 'selected' : '' ?>>Chanceler</option>
                                <option value="Orador" <?= ($obreiro['cargo'] ?? '') === 'Orador' ? 'selected' : '' ?>>Orador</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grau *</label>
                            <select name="grau" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                                <option value="Aprendiz" <?= ($obreiro['grau'] ?? '') === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                                <option value="Companheiro" <?= ($obreiro['grau'] ?? '') === 'Companheiro' ? 'selected' : '' ?>>Companheiro</option>
                                <option value="Mestre" <?= ($obreiro['grau'] ?? '') === 'Mestre' ? 'selected' : '' ?>>Mestre</option>
                                <option value="Mestre Instalado" <?= ($obreiro['grau'] ?? '') === 'Mestre Instalado' ? 'selected' : '' ?>>Mestre Instalado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loja de Origem (Se filiado)</label>
                            <input type="text" name="loja_origem" placeholder="Loja" value="<?= htmlspecialchars($obreiro['loja_origem'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Secão Pessoal -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2 mb-4">Dados Pessoais & Efemérides</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo Civil *</label>
                            <input type="text" name="nome_completo" required value="<?= htmlspecialchars($obreiro['nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Histórico</label>
                            <input type="text" name="nome_historico" placeholder="Como é chamado em Loja" value="<?= htmlspecialchars($obreiro['nome_historico'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento Civil 🎂</label>
                            <input type="date" name="data_nascimento_civil" value="<?= htmlspecialchars($obreiro['data_nascimento_civil'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Iniciação (Aniv. Maçônico) 🏛️</label>
                            <input type="date" name="data_iniciacao" value="<?= htmlspecialchars($obreiro['data_iniciacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone (WhatsApp)</label>
                            <input type="text" name="telefone" placeholder="(00) 00000-0000" value="<?= htmlspecialchars($obreiro['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($obreiro['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex items-center mt-4">
                    <input type="hidden" name="ativo" value="0">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?= ($obreiro['ativo'] ?? true) ? 'checked' : '' ?> class="h-4 w-4 text-cobalto border-gray-300 rounded focus:ring-cobalto">
                    <label for="ativo" class="ml-2 block text-sm text-gray-900 font-medium">Obreiro está Ativo na Loja</label>
                </div>

                <!-- Botões -->
                <div class="border-t border-gray-100 bg-gray-50 -my-6 -mx-6 mt-2 p-6 flex justify-between gap-3 flex-col sm:flex-row rounded-b-xl items-center">
                    <button type="button" onclick="confirm('Não implementado na demo. Em ambiente real apagará o obreiro.')" class="w-full sm:w-auto text-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-red-600 bg-red-50 hover:bg-red-100">
                        <i class="fas fa-trash-alt"></i> Excluir
                    </button>
                    
                    <div class="flex gap-3 w-full sm:w-auto flex-col sm:flex-row">
                        <a href="/obreiros" class="w-full sm:w-auto text-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalto">
                            Voltar
                        </a>
                        <button type="submit" class="w-full sm:w-auto flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-cobalto hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalto gap-2 items-center">
                            <i class="fas fa-save text-ouro"></i> Atualizar Dados
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

</body>
</html>