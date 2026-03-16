<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel da Diretoria - Gestor de Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalto: '#0047AB',
                        ouro: '#D4AF37',
                        pedra: '#F5F5F0',
                        pedraEscura: '#E8E8E2'
                    },
                    fontFamily: {
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans: ['"Inter"', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
</head>
<body class="bg-pedra min-h-screen font-sans text-gray-800" x-data="{ menuOpen: false }">

    <?php
    $usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
    $usuarioCargo = $_SESSION['usuario_cargo'] ?? '';
    $isTestSession = isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === 0;
    $allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'true', FILTER_VALIDATE_BOOL);
    $showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;
    $isChanceler = $usuarioCargo === 'chanceler';
    $isTesoureiro = $usuarioCargo === 'tesoureiro';
    ?>

    <!-- Navbar Mobile / Topbar Desktop -->
    <header class="bg-cobalto text-white shadow-md relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo e Título -->
                <div class="flex items-center">
                    <span class="text-2xl text-ouro font-serif mr-3">∴</span>
                    <span class="font-serif font-bold text-lg tracking-wide hidden sm:block">Loja Maçônica Renascença</span>
                    <span class="font-serif font-bold text-lg tracking-wide sm:hidden">Renascença</span>
                </div>

                <!-- Menu Botões Direita (Desktop) -->
                <div class="hidden sm:flex sm:items-center space-x-6">
                    <span class="text-sm border-r border-blue-700 pr-6">Olá, <?= htmlspecialchars($usuarioNome) ?></span>
                    <a href="/logout" class="text-gray-300 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>

                <!-- Botão Menu Mobile -->
                <div class="flex items-center sm:hidden">
                    <button @click="menuOpen = !menuOpen" type="button" class="text-gray-300 hover:text-white focus:outline-none">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu Mobile Dropdown -->
        <div x-show="menuOpen" @click.away="menuOpen = false" class="sm:hidden bg-blue-900 border-t border-blue-800" style="display: none;">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="/dashboard" class="bg-blue-800 text-white block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                <a href="/obreiros" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Obreiros</a>
                <?php if ($isChanceler || $showAllPanels): ?>
                    <a href="/chancelaria/efemerides" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Sessão do Chanceler</a>
                <?php endif; ?>
                <?php if ($isTesoureiro || $showAllPanels): ?>
                    <a href="/tesouraria/caixa" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Livro-Caixa</a>
                    <a href="/tesouraria/comprovantes" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Validação de Comprovantes</a>
                    <a href="/tesouraria/regularidade" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Regularidade</a>
                    <a href="/tesouraria/fechamento" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Fechamento Mensal</a>
                <?php endif; ?>
                <a href="#" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Sessões</a>
                <div class="border-t border-blue-800 my-2"></div>
                <div class="px-3 py-2 text-sm text-blue-300"><?= htmlspecialchars(ucfirst($usuarioCargo)) ?></div>
                <a href="/logout" class="text-red-400 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Sair</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row gap-6">
        
        <!-- Sidebar Desktop -->
        <aside class="w-full md:w-64 flex-shrink-0 hidden md:block">
            <nav class="space-y-2 relative">
                <a href="/dashboard" class="bg-white text-cobalto border-l-4 border-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-r-md shadow-sm">
                    Dashboard
                </a>
                <a href="/obreiros" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Obreiros (Chancelaria)
                </a>
                <?php if ($isChanceler || $showAllPanels): ?>
                <a href="/chancelaria/efemerides" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Sessão do Chanceler (Efemérides)
                </a>
                <?php endif; ?>
                <a href="#" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Sessões (Secretaria)
                </a>
                <?php if ($isTesoureiro || $showAllPanels): ?>
                <a href="/tesouraria/caixa" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Livro-Caixa
                </a>
                <a href="/tesouraria/comprovantes" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Validação de Comprovantes
                </a>
                <a href="/tesouraria/regularidade" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Regularidade
                </a>
                <a href="/tesouraria/fechamento" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Fechamento Mensal
                </a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Conteúdo Principal -->
        <main class="flex-1">
            <div class="mb-6 border-b border-gray-200 pb-4">
                <h2 class="text-2xl font-serif font-bold text-cobalto">Visão Geral da Loja</h2>
                <p class="text-sm text-gray-500 mt-1">Bem-vindo ao centro de comando. Aqui você acompanha o resumo das atividades.</p>
            </div>

            <!-- Cards Resumo -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                
                <!-- Card 1 -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100 relative">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-ouro"></div>
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-pedraEscura rounded-full p-3 text-ouro">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Obreiros Ativos</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900">42</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100 relative">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-cobalto"></div>
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-pedraEscura rounded-full p-3 text-cobalto">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Próxima Sessão</dt>
                                    <dd class="flex justify-between items-baseline">
                                        <div class="text-lg font-semibold text-gray-900">18/03 (Qui)</div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">12 Confirmados</span>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-100 relative">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gray-400"></div>
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-pedraEscura rounded-full p-3 text-gray-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z" />
                                </svg>
                            </div>
                            <div class="ml-4 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Aniversários Próximos</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-xl font-semibold text-gray-900">3 Irmãos</div>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

</body>
</html>