<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Presenca - Chancelaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 p-4 antialiased">
    <div class="max-w-2xl mx-auto">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Emitir certificado</h1>
                <p class="mt-1 text-sm text-gray-600">Fluxo web da Chancelaria para gerar e enviar certificado de presenca.</p>
            </div>
            <a href="/chancelaria/efemerides" class="text-sm text-blue-700 hover:underline">Voltar para Chancelaria</a>
        </div>

        <form method="POST" action="/chancelaria/certificado/gerar">
            <input type="hidden" id="chat_id" name="chat_id">
            <input type="hidden" id="init_data" name="init_data">

            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="nome_visitante" class="block mb-2 font-semibold">Nome do visitante</label>
                        <input type="text" id="nome_visitante" name="nome_visitante" placeholder="Ex: Joao da Silva" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div class="md:col-span-2">
                        <label for="loja_visitante" class="block mb-2 font-semibold">Loja do visitante</label>
                        <input type="text" id="loja_visitante" name="loja_visitante" placeholder="Ex: ARLS Luz e Verdade n 123" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div>
                        <label for="oriente" class="block mb-2 font-semibold">Oriente</label>
                        <input type="text" id="oriente" name="oriente" placeholder="Ex: Sao Paulo - SP" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div>
                        <label for="data_sessao" class="block mb-2 font-semibold">Data da sessao</label>
                        <input type="date" id="data_sessao" name="data_sessao" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div>
                        <label for="tipo_sessao" class="block mb-2 font-semibold">Tipo de sessao</label>
                        <select id="tipo_sessao" name="tipo_sessao" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white">
                            <option value="Ordinaria">Ordinaria</option>
                            <option value="Magna">Magna</option>
                            <option value="Magna de Iniciacao">Magna de Iniciacao</option>
                            <option value="Magna de Elevacao">Magna de Elevacao</option>
                            <option value="Magna de Exaltacao">Magna de Exaltacao</option>
                        </select>
                    </div>

                    <div>
                        <label for="grau_sessao" class="block mb-2 font-semibold">Grau da sessao</label>
                        <select id="grau_sessao" name="grau_sessao" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white">
                            <option value="Aprendiz Macom">Aprendiz Macom (Grau 1)</option>
                            <option value="Companheiro Macom">Companheiro Macom (Grau 2)</option>
                            <option value="Mestre Macom">Mestre Macom (Grau 3)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-700 text-white font-bold py-3 rounded-lg mt-6 hover:bg-blue-800">Gerar e enviar certificado</button>
            </div>
        </form>
    </div>

    <script>
        const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
        if (tg) {
            tg.ready();
            tg.expand();
            document.getElementById('init_data').value = tg.initData || '';
            if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                document.getElementById('chat_id').value = tg.initDataUnsafe.user.id;
            }
        }
    </script>
</body>
</html>
