<?php
// Mini App Telegram — Cadastro manual de livro
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Livro</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--tg-theme-bg-color, #fff);
            color: var(--tg-theme-text-color, #000);
            padding: 16px;
        }
        h2 { font-size: 18px; margin-bottom: 16px; text-align: center; }
        .campo { margin-bottom: 14px; }
        label { font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--tg-theme-hint-color, #ccc);
            background: var(--tg-theme-secondary-bg-color, #f5f5f5);
            color: var(--tg-theme-text-color, #000);
            font-size: 14px;
        }
        textarea { resize: none; height: 80px; }
        .btn {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            background: var(--tg-theme-button-color, #2481cc);
            color: var(--tg-theme-button-text-color, #fff);
        }
        .erro { color: #e53935; font-size: 13px; margin-top: 8px; }
    </style>
</head>
<body>

<h2>✏️ Cadastrar Novo Livro</h2>

<div class="campo">
    <label>ISBN</label>
    <input type="text" id="isbn" placeholder="978...">
</div>
<div class="campo">
    <label>Título da Obra *</label>
    <input type="text" id="titulo" placeholder="Título do livro">
</div>
<div class="campo">
    <label>Autor *</label>
    <input type="text" id="autor" placeholder="Nome do autor">
</div>
<div class="campo">
    <label>Editora</label>
    <input type="text" id="editora" placeholder="Editora">
</div>
<div class="campo">
    <label>URL da Capa</label>
    <input type="url" id="capa_url" placeholder="https://...">
</div>
<div class="campo">
    <label>Tipo de Material</label>
    <select id="tipo_material">
        <option value="livro">Livro</option>
        <option value="revista">Revista</option>
        <option value="manuscrito">Manuscrito</option>
        <option value="outro">Outro</option>
    </select>
</div>
<div class="campo">
    <label>Quantidade em Estoque</label>
    <input type="number" id="quantidade" value="1" min="1">
</div>
<div class="campo">
    <label>Grau Recomendado</label>
    <select id="grau_recomendado">
        <option value="">Todos os graus</option>
        <option value="aprendiz">Aprendiz</option>
        <option value="companheiro">Companheiro</option>
        <option value="mestre">Mestre</option>
    </select>
</div>
<div class="campo">
    <label>Nota de Instrução</label>
    <textarea id="nota_instrucao" placeholder="Observações ou notas sobre o livro..."></textarea>
</div>

<p class="erro" id="erro"></p>
<button class="btn" onclick="salvar()">💾 Salvar Livro</button>

<script>
    const tg = window.Telegram.WebApp;
    tg.expand();
    tg.ready();

    function salvar() {
        const titulo = document.getElementById('titulo').value.trim();
        const autor = document.getElementById('autor').value.trim();
        if (!titulo || !autor) {
            document.getElementById('erro').textContent = 'Título e Autor são obrigatórios.';
            return;
        }
        document.getElementById('erro').textContent = '';

        const dados = {
            isbn: document.getElementById('isbn').value,
            titulo,
            autor,
            editora: document.getElementById('editora').value,
            capa_url: document.getElementById('capa_url').value,
            tipo_material: document.getElementById('tipo_material').value,
            quantidade: document.getElementById('quantidade').value,
            grau_recomendado: document.getElementById('grau_recomendado').value,
            nota_instrucao: document.getElementById('nota_instrucao').value,
        };

        fetch('/api/biblioteca/cadastrar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        })
        .then(r => r.json())
        .then(res => {
            if (res.sucesso) {
                tg.showAlert('✅ Livro cadastrado com sucesso!', () => tg.close());
            } else {
                document.getElementById('erro').textContent = res.mensagem || 'Erro ao cadastrar.';
            }
        })
        .catch(() => {
            document.getElementById('erro').textContent = 'Erro de conexão. Tente novamente.';
        });
    }
</script>
</body>
</html>
