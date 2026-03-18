<?php
// Garante que a lista de livros seja lida pelo Controller
$lista = $itens ?? $acervo ?? $livros ?? [];

// Variáveis de sessão para o layout e permissões
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
$usuarioCargo = $_SESSION['usuario_cargo'] ?? '';
$isTestSession = isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === 0;
$allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'true', FILTER_VALIDATE_BOOL);
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;

// Definindo os papéis
$isChanceler = $usuarioCargo === 'chanceler';
$isTesoureiro = $usuarioCargo === 'tesoureiro';
$isBibliotecario = $usuarioCargo === 'bibliotecario';
$isVigilante = in_array($usuarioCargo, ['vigilante1', 'vigilante2']);

// Permissões de Ação na Biblioteca
$podeEditarCatalogo = $isBibliotecario || $showAllPanels; // Adicionar/Editar/Excluir
$podeClassificar = $isVigilante || $showAllPanels; // Botão de Curadoria
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca - Gestor de Loja</title>
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

    <!-- Navbar Mobile / Topbar Desktop -->
    <header class="bg-cobalto text-white shadow-md relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl text-ouro font-serif mr-3">∴</span>
                    <span class="font-serif font-bold text-lg tracking-wide hidden sm:block">Loja Maçônica Renascença</span>
                    <span class="font-serif font-bold text-lg tracking-wide sm:hidden">Renascença</span>
                </div>
                <div class="hidden sm:flex sm:items-center space-x-6">
                    <span class="text-sm border-r border-blue-700 pr-6">Olá, <?= htmlspecialchars($usuarioNome) ?></span>
                    <a href="/logout" class="text-gray-300 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>
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
                <a href="/dashboard" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                <a href="/obreiros" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Obreiros</a>
                <a href="/biblioteca" class="bg-blue-800 text-white block px-3 py-2 rounded-md text-base font-medium">📚 Biblioteca</a>
                <?php if ($isChanceler || $showAllPanels): ?>
                    <a href="/chancelaria/efemerides" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Sessão do Chanceler</a>
                <?php endif; ?>
                <?php if ($isTesoureiro || $showAllPanels): ?>
                    <a href="/tesouraria/caixa" class="text-gray-300 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Livro-Caixa</a>
                <?php endif; ?>
                <a href="/logout" class="text-red-400 hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Sair</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row gap-6">

        <!-- Sidebar Desktop -->
        <aside class="w-full md:w-64 flex-shrink-0 hidden md:block">
            <nav class="space-y-2 relative">
                <a href="/dashboard" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Dashboard
                </a>
                <a href="/obreiros" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Obreiros (Chancelaria)
                </a>
                <a href="/biblioteca" class="bg-white text-cobalto border-l-4 border-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-r-md shadow-sm">
                    📚 Biblioteca
                </a>
                <?php if ($isChanceler || $showAllPanels): ?>
                <a href="/chancelaria/efemerides" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
                    Sessão do Chanceler (Efemérides)
                </a>
                <?php endif; ?>
                <a href="javascript:alert('Módulo de Sessões em desenvolvimento.');" class="text-gray-600 hover:bg-white hover:text-cobalto group flex items-center px-3 py-3 text-sm font-medium rounded-md transition-colors">
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

        <!-- Conteúdo Principal da Biblioteca -->
        <main class="flex-1">
            <div class="mb-6 flex justify-between items-end border-b border-gray-200 pb-4">
                <div>
                    <h2 class="text-2xl font-serif font-bold text-cobalto">Catálogo da Biblioteca</h2>
                    <p class="text-sm text-gray-500 mt-1">Gerencie o acervo de livros e empréstimos da Loja.</p>
                </div>

                <!-- Botão Novo Título (Apenas Bibliotecário/Admin) -->
                <?php if ($podeEditarCatalogo): ?>
                <a href="/biblioteca/adicionar" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                    + Novo Título
                </a>
                <?php endif; ?>
            </div>

            <!-- Tabela de Livros -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capa</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Autor</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recomendação</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($lista)): ?>
                                <?php foreach ($lista as $item): ?>
                                    <tr class="hover:bg-gray-50">

                                        <!-- Coluna da Capa -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($item['capa_url'])): ?>
                                                <img src="<?= htmlspecialchars($item['capa_url']) ?>" alt="Capa" class="h-12 w-8 object-cover rounded shadow-sm border border-gray-200">
                                            <?php else: ?>
                                                <div class="h-12 w-8 bg-gray-100 rounded flex items-center justify-center text-gray-400 border border-gray-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Coluna Título -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($item['titulo'] ?? '') ?>
                                            <div class="text-xs text-gray-400 font-normal">ID: #<?= htmlspecialchars($item['id'] ?? '') ?></div>
                                        </td>

                                        <!-- Coluna Autor -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($item['autor'] ?? '') ?>
                                        </td>

                                        <!-- Coluna Recomendação (Curadoria) -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php
                                                $grau = $item['grau_recomendado'] ?? 'Livre';
                                                $badgeClass = 'bg-gray-100 text-gray-800'; // Padrão Livre
                                                $icon = '🟢';

                                                if ($grau === 'Aprendiz') {
                                                    $badgeClass = 'bg-blue-100 text-blue-800';
                                                    $icon = '🔵';
                                                } elseif ($grau === 'Companheiro') {
                                                    $badgeClass = 'bg-red-100 text-red-800';
                                                    $icon = '🔴';
                                                } elseif ($grau === 'Mestre') {
                                                    $badgeClass = 'bg-purple-100 text-purple-800';
                                                    $icon = '🟣';
                                                }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                                <span class="mr-1"><?= $icon ?></span> <?= htmlspecialchars($grau) ?>
                                            </span>
                                        </td>

                                        <!-- Coluna Status -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($item['disponivel'] ?? true): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Disponível</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Emprestado</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Coluna Ações (Dinâmica por Cargo) -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                                            <?php if ($podeEditarCatalogo): ?>
                                                <a href="/biblioteca/editar?id=<?= $item['id'] ?? '' ?>" class="text-cobalto hover:text-blue-900 mr-3">Editar</a>
                                            <?php endif; ?>

                                            <?php if ($podeClassificar): ?>
                                                <button onclick="alert('A janela de classificação será implementada no próximo passo!');" class="text-purple-600 hover:text-purple-900 font-bold flex items-center justify-end w-full">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                                    Classificar
                                                </button>
                                            <?php endif; ?>

                                            <?php if (!$podeEditarCatalogo && !$podeClassificar): ?>
                                                <a href="/biblioteca/detalhes?id=<?= $item['id'] ?? '' ?>" class="text-gray-500 hover:text-gray-800">Ver Detalhes</a>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        Nenhum título cadastrado no acervo ainda.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

</body>
</html>