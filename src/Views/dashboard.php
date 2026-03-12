<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Diretoria - Gestor Loja Maçônica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <nav class="bg-blue-900 text-white p-4 shadow-md flex justify-between">
        <div class="text-xl font-bold tracking-wider">Gestor da Loja</div>
        <div class="flex gap-4">
            <a href="#" class="hover:text-gray-300">Obreiros</a>
            <a href="#" class="hover:text-gray-300">Presenças</a>
            <a href="#" class="hover:text-gray-300">Efemérides</a>
        </div>
    </nav>
    <main class="p-8 max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Painel Geral</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-blue-500">
                <div class="text-sm text-gray-500">Obreiros Ativos</div>
                <div class="text-2xl font-bold">---</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-green-500">
                <div class="text-sm text-gray-500">Presenças Mês</div>
                <div class="text-2xl font-bold">---</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-yellow-500">
                <div class="text-sm text-gray-500">Sessões Próximas</div>
                <div class="text-2xl font-bold">---</div>
            </div>
        </div>

        <section class="bg-white shadow-sm rounded-lg p-6" x-data="{ open: true }">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 cursor-pointer flex justify-between" @click="open = !open">
                Obreiros Presentes na Última Sessão
                <span x-text="open ? '▼' : '►'" class="text-sm"></span>
            </h2>
            <div x-show="open" class="text-gray-600">
                Nenhum dado integrado com base de dados ainda.<br/><br/>
                <em>Status da Conexão DB: -- Aguardando Configuração PDO --</em>
            </div>
        </section>
    </main>
</body>
</html>