<?php
// Exibe erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
// Mini App Telegram — Cadastro manual de livro
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Cadastro Manual de Livro</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        :root {
            --bg: var(--tg-theme-bg-color, #F5F5F0);
            --bg2: var(--tg-theme-secondary-bg-color, #ffffff);
            --text: var(--tg-theme-text-color, #1a1a1a);
            --hint: var(--tg-theme-hint-color, #6b7280);
            --btn: var(--tg-theme-button-color, #0047AB);
            --btn-text: var(--tg-theme-button-text-color, #ffffff);
            --border: var(--tg-theme-hint-color, #d1d5db);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 16px;
            padding-bottom: 80px;
        }
        .secao {
            background: var(--bg2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 14px;
            border: 1px solid rgba(0,0,0,0.06);
        }
        .secao-titulo {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--hint);
            margin-bottom: 12px;
        }
        .campo { margin-bottom: 12px; }
        .campo:last-child { margin-bottom: 0; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 5px; }
        label .obrig { color: #e53935; margin-left: 2px; }
        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            -webkit-appearance: none;
            appearance: none;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--btn); }
        textarea { resize: none; height: 72px; }
        .rodape {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--bg2);
            border-top: 1px solid rgba(0,0,0,0.08);
            padding: 12px 16px;
            display: flex;
            gap: 10px;
        }
        .btn { flex: 1; padding: 12px; border-radius: 10px; border: none; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: var(--btn); color: var(--btn-text); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-secondary {
            background: transparent;
            color: var(--btn);
            border: 1px solid var(--btn);
            flex: 0 0 auto;
            padding: 12px 20px;
        }
        .erro-geral {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            color: #b91c1c;
            margin-bottom: 14px;
            display: none;
        }
        #formulario { display: block; }
    </style>
</head>
<body>

<h2>📚 Cadastro Manual de Livro</h2>
<p class="subtitulo">Preencha os dados do livro manualmente.</p>

<div id="formulario">
    <div class="erro-geral" id="erro-geral"></div>

    <div class="secao">
        <div class="secao-titulo">Identificação</div>
        <div class="campo">
            <label>Título da Obra <span class="obrig">*</span></label>
            <input type="text" id="titulo" placeholder="Ex: Morals and Dogma">
        </div>
        <div class="campo">
            <label>Autor <span class="obrig">*</span></label>
            <input type="text" id="autor" placeholder="Ex: Albert Pike">
        </div>
        <div class="campo">
            <label>ISBN</label>
            <input type="text" id="isbn">
        </div>
        <div class="campo">
            <label>URL da Capa</label>
            <input type="url" id="capa_url" placeholder="https://...">
        </div>
    </div>

    <div class="secao">
        <div class="secao-titulo">Catalogação</div>
        <div class="campo">
            <label>Tipo de Material <span class="obrig">*</span></label>
            <select id="tipo">
                <option value="Livro Físico">Livro Físico</option>
                <option value="Digital (PDF)">Digital (PDF)</option>
                <option value="Ritual">Ritual</option>
            </select>
        </div>
        <div class="campo">
            <label>Quantidade <span class="obrig">*</span></label>
            <input type="number" id="quantidade" value="1" min="1">
        </div>
    </div>

    <div class="secao">
        <div class="secao-titulo">Curadoria (Opcional)</div>
        <div class="campo">
            <label>Grau Recomendado</label>
            <select id="grau_recomendado">
                <option value="Livre">🟢 Livre / Todos os Graus</option>
                <option value="Aprendiz">🔵 Recomendado: Aprendiz</option>
                <option value="Companheiro">🔴 Recomendado: Companheiro</option>
                <option value="Mestre">🟣 Recomendado: Mestre</option>
            </select>
        </div>
        <div class="campo">
            <label>Nota de Instrução</label>
            <textarea id="nota_instrucao" placeholder="Ex: Leitura essencial para a elevação..."></textarea>
        </div>
    </div>
</div>

<div class="rodape">
    <button class="btn btn-secondary" onclick="tg.close()">Cancelar</button>
    <button class="btn btn-primary" id="btn-salvar" onclick="salvar()">💾 Salvar Livro</button>
</div>

<script>
    const tg = window.Telegram.WebApp;
    tg.expand();
    tg.ready();

    function mostrarErro(msg) {
        const el = document.getElementById('erro-geral');
        el.textContent = msg;
        el.style.display = 'block';
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function salvar() {
        document.getElementById('erro-geral').style.display = 'none';

        const titulo    = document.getElementById('titulo').value.trim();
        const autor     = document.getElementById('autor').value.trim();
        const quantidade = parseInt(document.getElementById('quantidade').value);

        if (!titulo)   { mostrarErro('O título da obra é obrigatório.'); return; }
        if (!autor)    { mostrarErro('O autor é obrigatório.'); return; }
        if (!quantidade || quantidade < 1) { mostrarErro('A quantidade deve ser pelo menos 1.'); return; }

        const btn = document.getElementById('btn-salvar');
        btn.disabled = true;
        btn.textContent = 'Salvando...';

        fetch('/api/biblioteca/cadastrar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                titulo,
                autor,
                isbn:                  document.getElementById('isbn').value.trim(),
                capa_url:              document.getElementById('capa_url').value.trim(),
                tipo:                  document.getElementById('tipo').value,
                quantidade_disponivel: quantidade,
                grau_recomendado:      document.getElementById('grau_recomendado').value,
                nota_instrucao:        document.getElementById('nota_instrucao').value.trim(),
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.sucesso) {
                tg.showAlert('✅ Livro cadastrado com sucesso!', () => tg.close());
            } else {
                mostrarErro(res.mensagem || 'Erro ao cadastrar. Tente novamente.');
                btn.disabled = false;
                btn.textContent = '💾 Salvar Livro';
            }
        })
        .catch(() => {
            mostrarErro('Erro de conexão. Verifique sua internet e tente novamente.');
            btn.disabled = false;
            btn.textContent = '💾 Salvar Livro';
        });
    }
</script>
</body>
</html>
