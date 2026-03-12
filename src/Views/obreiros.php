<?php
// src/Views/obreiros.php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}
$appTitle = "Chancelaria - Obreiros";
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
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="bg-pedra font-sans text-gray-800 antialiased" x-data="{ sidebarOpen: false }">

    <!-- Header Mobile/Desktop -->
    <header class="bg-cobalto text-white shadow-md sticky top-0 z-50">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center pb-2">
                <button @click="sidebarOpen = !sidebarOpen" class="text-ouro hover:text-white focus:outline-none lg:hidden mr-3">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="font-serif text-lg font-bold tracking-wider relative flex items-center gap-2">
                    <i class="fas fa-users text-ouro"></i> Chancelaria
                </h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="hidden md:block text-sm text-gray-300">
                    Ir∴ <?= htmlspecialchars($_SESSION['usuario_logado']['nome_historico'] ?? 'Irmão') ?>
                </div>
                <div class="h-8 w-8 rounded-full bg-ouro text-cobalto flex items-center justify-center font-bold shadow-sm ring-2 ring-white/20">
                    <?= substr(htmlspecialchars($_SESSION['usuario_logado']['nome_historico'] ?? 'I'), 0, 1) ?>
                </div>
                <a href="/logout" class="text-gray-300 hover:text-white" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="flex h-[calc(100vh-60px)] overflow-hidden">
        
        <!-- Sidebar (Overlay on mobile) -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="sidebarOpen = false" x-transition.opacity></div>
        
        <aside :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}" 
               class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 border-r border-gray-200 flex flex-col pt-16 lg:pt-0 h-full">
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <a href="/dashboard" class="flex items-center px-4 py-3 text-gray-700 hover:bg-pedra hover:text-cobalto rounded-lg transition-colors">
                    <i class="fas fa-home w-6 text-center text-gray-500"></i>
                    <span class="ml-3 font-medium">Início</span>
                </a>
                
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Administração
                    </p>
                </div>
                
                <a href="/obreiros" class="flex items-center px-4 py-3 text-cobalto bg-blue-50/50 rounded-lg border-l-4 border-ouro transition-colors">
                    <i class="fas fa-users w-6 text-center text-ouro"></i>
                    <span class="ml-3 font-medium">Obreiros</span>
                </a>
                
                <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-pedra hover:text-cobalto rounded-lg transition-colors">
                    <i class="fas fa-money-check-alt w-6 text-center text-gray-500"></i>
                    <span class="ml-3 font-medium">Tesouraria</span>
                </a>
                
                <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-pedra hover:text-cobalto rounded-lg transition-colors">
                    <i class="fas fa-book w-6 text-center text-gray-500"></i>
                    <span class="ml-3 font-medium">Biblioteca</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-pedra p-4 lg:p-8">
            <div class="max-w-4xl mx-auto">
                
                <!-- Action Bar -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-serif font-bold text-cobalto">Lista de Obreiros</h2>
                        <p class="text-sm text-gray-500 mt-1">Gerencie os membros ativos da Loja.</p>
                    </div>
                    <a href="/obreiros/novo" class="w-full sm:w-auto bg-cobalto hover:bg-blue-900 text-white font-medium py-2.5 px-4 rounded-lg shadow-md transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-user-plus text-ouro"></i> Adicionar Obreiro
                    </a>
                </div>

                <!-- Search/Filter -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-pedra-escura text-gray-900 placeholder-gray-500 focus:outline-none focus:bg-white focus:ring-2 focus:ring-ouro focus:border-ouro sm:text-sm transition-colors" placeholder="Buscar por nome, grau ou CIM...">
                    </div>
                </div>

                <!-- Obreiros Cards (Mobile First) -->
                <div class="space-y-4">
                    
                    <?php if (empty($obreiros)): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                            <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                            <p>Nenhum obreiro ativo encontrado.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($obreiros as $obreiro): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:border-blue-200 transition-colors flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center relative">
                            <!-- Left: Info -->
                            <div class="flex items-start gap-4 w-full sm:w-auto">
                                <div class="h-12 w-12 rounded-full bg-pedra-escura flex items-center justify-center text-cobalto text-xl font-bold border border-gray-200 shrink-0">
                                    <?= substr(htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome']), 0, 1) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-gray-900 truncate">
                                        <?= htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome']) ?>
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                            <?= htmlspecialchars($obreiro['grau'] ?? 'Não informado') ?>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-id-card text-gray-400 text-xs"></i> 
                                            CIM: <?= htmlspecialchars($obreiro['cim'] ?? 'Não informado') ?>
                                        </span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-400 flex items-center gap-1">
                                        <i class="fab fa-telegram text-blue-500" <?= empty($obreiro['telegram_id']) ? 'style="opacity:0.3"' : '' ?>></i>
                                        <?= !empty($obreiro['telegram_id']) ? 'Bot Ativo' : 'Bot Inativo' ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right: Actions -->
                            <div class="flex items-center gap-2 w-full sm:w-auto justify-end sm:mt-0 mt-2 pt-3 sm:pt-0 border-t sm:border-t-0 border-gray-100">
                                <button class="p-2 text-gray-400 hover:text-cobalto bg-pedra hover:bg-blue-50 rounded-lg transition-colors" title="Ver Detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="p-2 text-gray-400 hover:text-ouro bg-pedra hover:bg-yellow-50 rounded-lg transition-colors" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
                
                <!-- Pagination (Mock) -->
                <?php if (!empty($obreiros) && count($obreiros) > 10): ?>
                <div class="mt-6 flex justify-center">
                    <nav class="flex items-center gap-1">
                        <button class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-500 hover:bg-pedra-escura disabled:opacity-50"><i class="fas fa-chevron-left text-xs"></i></button>
                        <button class="px-3 py-1 rounded bg-cobalto text-white border border-cobalto">1</button>
                        <button class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-700 hover:bg-pedra-escura">2</button>
                        <button class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-500 hover:bg-pedra-escura"><i class="fas fa-chevron-right text-xs"></i></button>
                    </nav>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>
