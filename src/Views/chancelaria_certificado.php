<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Presença - Chancelaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 p-4 antialiased">
    <form method="POST" action="/chancelaria/certificado/gerar">
        <input type="hidden" id="chat_id" name="chat_id">
        <div class="bg-white rounded-xl shadow-sm p-5 max-w-md mx-auto">
            <label for="nome_visitante" class="block mb-2 font-semibold">Nome do Visitante</label>
            <input type="text" id="nome_visitante" name="nome_visitante" placeholder="Ex: João da Silva" required class="w-full mb-4 border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">

            <label for="loja_visitante" class="block mb-2 font-semibold">Loja do Visitante</label>
            <input type="text" id="loja_visitante" name="loja_visitante" placeholder="Ex: ARLS Luz e Verdade nº 123" required class="w-full mb-4 border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">

            <label for="oriente" class="block mb-2 font-semibold">Oriente</label>
            <input type="text" id="oriente" name="oriente" placeholder="Ex: São Paulo - SP" required class="w-full mb-4 border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">

            <label for="tipo_sessao" class="block mb-2 font-semibold">Tipo de Sessão</label>
            <select id="tipo_sessao" name="tipo_sessao" required class="w-full mb-4 border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white">
                <option value="Ordinária">Ordinária</option>
                <option value="Magna">Magna</option>
                <option value="Magna de Iniciação">Magna de Iniciação</option>
                <option value="Magna de Elevação">Magna de Elevação</option>
                <option value="Magna de Exaltação">Magna de Exaltação</option>
            </select>

            <label for="grau_sessao" class="block mb-2 font-semibold">Grau da Sessão</label>
            <select id="grau_sessao" name="grau_sessao" required class="w-full mb-4 border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white">
                <option value="Aprendiz Maçom">Aprendiz Maçom (Grau 1)</option>
                <option value="Companheiro Maçom">Companheiro Maçom (Grau 2)</option>
                <option value="Mestre Maçom">Mestre Maçom (Grau 3)</option>
            </select>

            <label for="data_sessao" class="block mb-2 font-semibold">Data da Sessão</label>
            <input type="date" id="data_sessao" name="data_sessao" required class="w-full mb-4 border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">

            <button type="submit" class="w-full bg-blue-700 text-white font-bold py-3 rounded-lg mt-4 hover:bg-blue-800">Gerar e Enviar Certificado</button>
        </div>
    </form>
    <script>
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();
        if (window.Telegram.WebApp.initDataUnsafe && window.Telegram.WebApp.initDataUnsafe.user) {
            document.getElementById('chat_id').value = window.Telegram.WebApp.initDataUnsafe.user.id;
        }
    </script>
</body>
</html>