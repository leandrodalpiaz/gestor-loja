<?php
// Variáveis de sessão para o layout
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
$usuarioCargo = $_SESSION['usuario_cargo'] ?? '';
$isChanceler = $usuarioCargo === 'chanceler';
$isTesoureiro = $usuarioCargo === 'tesoureiro';
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Livro - Gestor de Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { cobalto: '#0047AB', ouro: '#D4AF37', pedra: '#F5F5F0' },
                    fontFamily: { serif: ['"Playfair Display"', 'serif'], sans: ['"Inter"', 'sans-serif'] }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-pedra min-h-screen font-sans text-gray-800" x-data="{ menuOpen: false }">

    <!-- Navbar (Simplificada para o exemplo) -->
    <header class="bg-cobalto text-white shadow-md relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
            <div class="flex items-center">
                <span class="text-2xl text-ouro font-serif mr-3">∴</span>
                <span class="font-serif font-bold text-lg tracking-wide">Loja Maçônica Renascença</span>
            </div>
            <div class="flex items-center space-x-6">
                <span class="text-sm border-r border-blue-700 pr-6">Olá, <?= htmlspecialchars($usuarioNome) ?></span>
                <a href="/logout" class="text-gray-300 hover:text-white">Sair</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row gap-6">

        <!-- Sidebar (Simplificada) -->
        <aside class="w-full md:w-64 flex-shrink-0 hidden md:block">
            <nav class="space-y-2">
                <a href="/dashboard" class="text-gray-600 hover:bg-white hover:text-cobalto block px-3 py-3 text-sm font-medium rounded-md">Dashboard</a>
                <a href="/biblioteca" class="bg-white text-cobalto border-l-4 border-cobalto block px-3 py-3 text-sm font-medium rounded-r-md shadow-sm">📚 Biblioteca</a>
            </nav>
        </aside>

        <!-- Conteúdo Principal: Formulário -->
        <main class="flex-1">
            <div class="mb-6">
                <h2 class="text-2xl font-serif font-bold text-cobalto">Adicionar Novo Título</h2>
                <p class="text-sm text-gray-500 mt-1">Preencha os dados para catalogar uma nova obra no acervo.</p>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                <!-- O formulário enviará os dados via POST para uma rota de salvamento -->
                <form action="/biblioteca/salvar" method="POST" class="space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Título -->
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Título da Obra *</label>
                            <input type="text" name="titulo" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border">
                        </div>

                        <!-- Autor -->
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Autor *</label>
                            <input type="text" name="autor" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border">
                        </div>

                        <!-- ISBN e Capa -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ISBN (Código de Barras)</label>
                            <input type="text" name="isbn" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">URL da Imagem da Capa</label>
                            <input type="url" name="capa_url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border">
                        </div>

                        <!-- Tipo e Quantidade -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo de Material *</label>
                            <select name="tipo" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border bg-white">
                                <option value="Livro Físico">Livro Físico</option>
                                <option value="Digital (PDF)">Digital (PDF)</option>
                                <option value="Ritual">Ritual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Quantidade em Estoque *</label>
                            <input type="number" name="quantidade_disponivel" value="1" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border">
                        </div>

                        <!-- Curadoria (Opcional no cadastro) -->
                        <div class="col-span-2 bg-gray-50 p-4 rounded-md border border-gray-200 mt-2">
                            <h3 class="text-sm font-bold text-gray-700 mb-3">Curadoria Inicial (Opcional)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Grau Recomendado</label>
                                    <select name="grau_recomendado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border bg-white">
                                        <option value="Livre">🟢 Livre / Todos os Graus</option>
                                        <option value="Aprendiz">🔵 Recomendado: Aprendiz</option>
                                        <option value="Companheiro">🔴 Recomendado: Companheiro</option>
                                        <option value="Mestre">🟣 Recomendado: Mestre</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nota de Instrução</label>
                                    <input type="text" name="nota_instrucao" placeholder="Ex: Leitura essencial para a elevação..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cobalto focus:ring-cobalto sm:text-sm p-2 border">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                        <a href="/biblioteca" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalto">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-cobalto border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalto">
                            Salvar Livro
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>