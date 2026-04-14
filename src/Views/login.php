<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso à Chancelaria - Gestor de Loja</title>
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
<?php
$logoRenascencaLogin = null;
foreach ([
    '/assets/logo-renascenca.png',
    '/assets/logo-renascenca.svg',
    '/assets/logo-loja-renascenca.png',
    '/assets/logo-loja-renascenca.svg',
] as $logoPath) {
    if (file_exists(__DIR__ . '/../../public' . $logoPath)) {
        $logoRenascencaLogin = $logoPath;
        break;
    }
}
?>
<body class="bg-pedra min-h-screen flex items-center justify-center p-4 font-sans relative overflow-hidden">
    
    <!-- Efeito visual de fundo sutíl -->
    <div class="absolute inset-0 z-0 opacity-[0.03] pointer-events-none flex items-center justify-center">
        <!-- Um g de compasso estilizado genérico só para dar textura -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-[30rem] h-[30rem] text-cobalto">
            <path d="M12 2L2 22h20L12 2zm0 4.5l6.5 13h-13L12 6.5z"/>
        </svg>
    </div>

    <!-- Container Principal do Login -->
    <div class="w-full max-w-md bg-white rounded-xl shadow-xl z-10 border-t-4 border-cobalto overflow-hidden relative">
        
        <!-- Header do Card -->
        <div class="pt-8 pb-4 px-8 text-center bg-white">
            <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-ouro/30 bg-pedraEscura shadow-inner">
                <?php if ($logoRenascencaLogin): ?>
                    <img src="<?= htmlspecialchars($logoRenascencaLogin) ?>" alt="Logotipo da Loja Renascença" class="h-full w-full object-cover">
                <?php else: ?>
                    <span class="text-3xl text-ouro font-serif">∴</span>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl font-serif font-bold text-cobalto">Bem-vindo a Renascenca</h1>
            <p class="text-sm text-gray-500 mt-2">Entre com seu CIM e sua palavra de passe.</p>
        </div>

        <!-- Formulário -->
        <div class="p-8 pt-4">
            <form action="/login" method="POST" class="space-y-5">
                
                <!-- Matrícula / Identificação -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CIM / Matrícula</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <!-- Ícone de Identidade -->
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 6.908V16a2 2 0 002 2h8a2 2 0 002-2V6.908a2.5 2.5 0 00-1.042-2.031L11.542 2.3A2.5 2.5 0 0010 2.062V6.5h2v2h-2v2h2v2h-2v2h-2v-2h-2v-2h2v-2h-2v-2H9.99V2.062a2.5 2.5 0 00-1.542.237L5.042 4.877A2.5 2.5 0 004 6.908l.001.001z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" name="matricula" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md bg-pedra/30 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-ouro focus:border-ouro sm:text-sm transition-all" placeholder="Digite seu CIM">
                    </div>
                </div>

                <!-- Senha -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Palavra de Passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="password" name="password" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md bg-pedra/30 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-ouro focus:border-ouro sm:text-sm transition-all" placeholder="••••••••">
                    </div>
                </div>

                <!-- Tratamento de Erro Visual (Deixamos pronto para o PHP injetar) -->
                <?php if(isset($erroLogin)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?= htmlspecialchars($erroLogin) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botão -->
                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-cobalto hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cobalto transition-colors duration-200">
                        <span>Entrar no sistema</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer do Card -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <span class="text-xs text-gray-500">Em caso de duvidas sobre acesso, procure a administracao da Loja.</span>
        </div>
    </div>

</body>
</html>
