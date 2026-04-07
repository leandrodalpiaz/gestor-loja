<?php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}
$appTitle = "Adicionar Obreiro - Secretaria";
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
                <i class="fas fa-user-plus text-ouro mr-2"></i>Adicionar Obreiro
            </h1>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 mt-4 mb-20">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <h2 class="text-xl font-bold text-cobalto">Ficha de cadastro</h2>
                <p class="text-sm text-gray-500 mt-1">O Secretario pode registrar os dados maconicos, civis e administrativos do membro.</p>
            </div>

            <form action="/obreiros/salvar" method="POST" class="p-6 space-y-8">
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Dados maconicos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CIM</label>
                            <input type="number" name="cim" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo legado</label>
                            <select name="cargo" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Sem cargo</option>
                                <option value="Veneravel">Veneravel Mestre</option>
                                <option value="1 Vigilante">1 Vigilante</option>
                                <option value="2 Vigilante">2 Vigilante</option>
                                <option value="Secretario">Secretario</option>
                                <option value="Tesoureiro">Tesoureiro</option>
                                <option value="Chanceler">Chanceler</option>
                                <option value="Orador">Orador</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grau</label>
                            <select name="grau" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="Aprendiz">Aprendiz</option>
                                <option value="Companheiro">Companheiro</option>
                                <option value="Mestre">Mestre</option>
                                <option value="Mestre Instalado">Mestre Instalado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loja de origem</label>
                            <input type="text" name="loja_origem" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de iniciacao</label>
                            <input type="date" name="data_iniciacao" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de elevacao</label>
                            <input type="date" name="data_elevacao" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de exaltacao</label>
                            <input type="date" name="data_exaltacao" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Dados pessoais</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo civil</label>
                            <input type="text" name="nome_completo" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome historico</label>
                            <input type="text" name="nome_historico" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                            <input type="text" name="cpf" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de nascimento civil</label>
                            <input type="date" name="data_nascimento_civil" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" name="telefone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profissao</label>
                            <input type="text" name="profissao" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Controle administrativo da Secretaria</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telegram ID</label>
                            <input type="number" name="telegram_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Login na Potencia</label>
                            <input type="text" name="potencia_login" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" name="acesso_potencia_liberado" value="1" class="rounded border-gray-300">
                                Acesso na plataforma da Potencia ja liberado
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observacao da Secretaria</label>
                            <textarea name="observacao_secretaria" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 bg-gray-50 -my-6 -mx-6 mt-2 p-6 flex justify-end gap-3 flex-col sm:flex-row rounded-b-xl">
                    <a href="/obreiros" class="w-full sm:w-auto text-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto flex justify-center py-2 px-6 text-sm font-medium rounded-md text-white bg-cobalto hover:bg-blue-900 gap-2 items-center">
                        <i class="fas fa-save text-ouro"></i>Gravar obreiro
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
