<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Livro Manualmente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Telegram WebApp Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg flex flex-col items-center">
        <h1 class="text-2xl font-bold mb-6 text-blue-600">Cadastro Manual de Livro</h1>
        <form id="form-livro" class="w-full flex flex-col gap-4">
            <input class="border p-2 rounded" name="titulo" placeholder="Título" required>
            <input class="border p-2 rounded" name="autor" placeholder="Autor" required>
            <input class="border p-2 rounded" name="isbn" placeholder="ISBN">
            <input class="border p-2 rounded" name="capa_url" placeholder="URL da Capa">
            <select class="border p-2 rounded" name="tipo" required>
                <option value="Livro Físico">Livro Físico</option>
                <option value="Digital (PDF)">Digital (PDF)</option>
                <option value="Ritual">Ritual</option>
            </select>
            <input class="border p-2 rounded" name="quantidade_disponivel" type="number" min="1" value="1" placeholder="Quantidade" required>
            <select class="border p-2 rounded" name="grau_recomendado">
                <option value="Livre">🟢 Livre / Todos os Graus</option>
                <option value="Aprendiz">🔵 Recomendado: Aprendiz</option>
                <option value="Companheiro">🔴 Recomendado: Companheiro</option>
                <option value="Mestre">🟣 Recomendado: Mestre</option>
            </select>
            <input class="border p-2 rounded" name="nota_instrucao" placeholder="Nota de Instrução">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Cadastrar</button>
        </form>
    </div>
    <script>
        window.Telegram.WebApp.ready();
        document.getElementById('form-livro').onsubmit = async function(e) {
            e.preventDefault();
            const form = e.target;
            const dados = {
                titulo: form.titulo.value,
                autor: form.autor.value,
                isbn: form.isbn.value,
                capa_url: form.capa_url.value,
                tipo: form.tipo.value,
                quantidade_disponivel: form.quantidade_disponivel.value,
                grau_recomendado: form.grau_recomendado.value,
                nota_instrucao: form.nota_instrucao.value,
                init_data: window.Telegram && window.Telegram.WebApp ? (window.Telegram.WebApp.initData || '') : ''
            };
            const resp = await fetch('/api/biblioteca/cadastrar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            const json = await resp.json();
            if (json.sucesso) {
                window.Telegram.WebApp.showAlert('Livro cadastrado!');
                window.Telegram.WebApp.close();
            } else {
                window.Telegram.WebApp.showAlert(json.mensagem || 'Erro ao cadastrar.');
            }
        };
    </script>
</body>
</html>
