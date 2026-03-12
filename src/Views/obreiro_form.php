<?php
// src/Views/obreiro_form.php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}
$appTitle = "Adicionar Obreiro - Chancelaria";
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
    <!-- Font Awesome e Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="bg-pedra font-sans text-gray-800 antialiased">
    
    <!-- Topbar Simplificada para foco no Form -->
    <header class="bg-cobalto text-white shadow-md sticky top-0 z-50">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/obreiros" class="text-gray-300 hover:text-white transition-colors" title="Voltar">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                <h1 class="font-serif text-lg font-bold tracking-wider">
                    <i class="fas fa-user-plus text-ouro mr-2"></i> Adicionar Obreiro
                </h1>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8 mt-4 mb-20">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            
            <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-cobalto">Ficha de Inscrição</h2>
                    <p class="text-sm text-gray-500 mt-1">Preencha os dados maçônicos e civis do Irmão.</p>
                </div>
            </div>

            <form action="/obreiros/salvar" method="POST" class="p-6 space-y-8">
                
                <!-- Secão Maçônica -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2 mb-4">Dados Maçônicos</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CIM (Matrícula) *</label>
                            <input type="number" name="cim" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo Atual</label>
                            <select name="cargo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                                <option value="">Sem cargo (Somente membro)</option>
                                <option value="Venerável">Venerável Mestre</option>
                                <option value="1º Vigilante">1º Vigilante</option>
                                <option value="2º Vigilante">2º Vigilante</option>
                                <option value="Secretário">Secretário</option>
                                <option value="Tesoureiro">Tesoureiro</option>
                                <option value="Chanceler">Chanceler</option>
                                <option value="Orador">Orador</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grau *</label>
                            <select name="grau" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                                <option value="Aprendiz">Aprendiz</option>
                                <option value="Companheiro">Companheiro</option>
                                <option value="Mestre">Mestre</option>
                                <option value="Mestre Instalado">Mestre Instalado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loja de Origem (Se houver)</label>
                            <input type="text" name="loja_origem" placeholder="Apenas para filiados" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Secão Pessoal -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2 mb-4">Dados Pessoais</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo Civil *</label>
                            <input type="text" name="nome_completo" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Histórico</label>
                            <input type="text" name="nome_historico" placeholder="Como é chamado em Loja" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                            <input type="text" name="cpf" placeholder="000.000.000-00" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento Civil</label>
                            <input type="date" name="data_nascimento_civil" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm text-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Iniciação (Aniv. Maçônico)</label>
                            <input type="date" name="data_iniciacao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm text-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone (WhatsApp)</label>
                            <input type="text" name="telefone" placeholder="(00) 00000-0000" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profissão</label>
                            <input type="text" name="profissao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-ouro focus:border-ouro sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="border-t border-gray-100 bg-gray-50 -my-6 -mx-6 mt-2 p-6 flex justify-end gap-3 flex-col sm:flex-row rounded-b-xl">
                    <a href="/obreiros" class="w-full sm:w-auto text-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalto">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-cobalto hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalto gap-2 items-center">
                        <i class="fas fa-save text-ouro"></i> Gravar Obreiro
                    </button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>