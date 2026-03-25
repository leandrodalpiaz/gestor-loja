<?php
// Mini App Telegram — Cadastro via scanner de ISBN
// Mesmos campos do novo.php + leitura de código de barras via câmera
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Scanner ISBN</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
        #scanner-container {
            background: var(--bg2);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 14px;
            border: 1px solid rgba(0,0,0,0.06);
        }
        #scanner-header {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #scanner-titulo {
            font-size: 13px;
            font-weight: 700;
            color: var(--hint);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        #scanner-status { font-size: 12px; color: var(--hint); }
        #reader { width: 100%; }
        #reader video { width: 100% !important; }
        #btn-novo-scan {
            width: calc(100% - 32px);
            margin: 0 16px 16px;
            padding: 10px;
            border-radius: 8px;
            background: transparent;
            border: 1px solid var(--btn);
            color: var(--btn);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: none;
        }
        .campo-preenchido input { border-color: #16a34a; background: #f0fdf4; }
        h2 { font-size: 17px; font-weight: 700; margin-bottom: 4px; color: var(--btn); }
        .subtitulo { font-size: 12px; color: var(--hint); margin-bottom: 20px; }
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
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%236b7280' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        textarea { resize: none; height: 72px; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
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
        #formulario { display: none; }
    </style>
</head>
<body>

<h2>📷 Cadastrar por ISBN</h2>
<p class="subtitulo">Aponte a câmera para o código de barras do livro.</p>

<!-- Scanner -->
<div id="scanner-container">
    <div id="scanner-header">
        <span id="scanner-titulo">📷 Câmera</span>
        <span id="scanner-status">Aguardando leitura...</span>
    </div>
    <div id="reader"></div>
    <button id="btn-novo-scan" onclick="reiniciarScanner()">📷 Ler Outro Código</button>
</div>

<!-- Formulário (aparece após leitura) -->
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
        <div class="grid2">
            <div class="campo">
                <label>ISBN</label>
                <input type="text" id="isbn" readonly>
            </div>
            <div class="campo">
                <label>URL da Capa</label>
                <input type="url" id="capa_url" placeholder="https://...">
            </div>
        </div>
    </div>

    <div class="secao">
        <div class="secao-titulo">Catalogação</div>
        <div class="grid2">
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
    <button class="btn btn-primary" id="btn-salvar" onclick="salvar()" style="display:none">💾 Salvar Livro</button>
</div>

<script>
    const tg = window.Telegram.WebApp;
    tg.expand();
    tg.ready();

    let scanner = null;

    function iniciarScanner() {
        scanner = new Html5Qrcode("reader");
        scanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 260, height: 100 } },
            (isbn) => {
                scanner.stop().then(() => {
                    document.getElementById('isbn').value = isbn;
                    document.getElementById('scanner-status').textContent = '✅ ' + isbn;
                    document.getElementById('btn-novo-scan').style.display = 'block';
                    buscarMetadados(isbn);
                    document.getElementById('formulario').style.display = 'block';
                    document.getElementById('btn-salvar').style.display = 'block';
                    document.getElementById('formulario').scrollIntoView({ behavior: 'smooth' });
                });
            },
            () => {}
        ).catch(() => {
            // Câmera indisponível — mostra formulário direto para ISBN manual
            document.getElementById('scanner-container').style.display = 'none';
            document.getElementById('formulario').style.display = 'block';
            document.getElementById('btn-salvar').style.display = 'block';
        });
    }

    function buscarMetadados(isbn) {
        document.getElementById('scanner-status').textContent = '🔍 Buscando dados...';
        fetch(`https://openlibrary.org/api/books?bibkeys=ISBN:${isbn}&format=json&jscmd=data`)
            .then(r => r.json())
            .then(data => {
                const livro = data[`ISBN:${isbn}`];
                if (livro) {
                    if (livro.title)           document.getElementById('titulo').value = livro.title;
                    if (livro.authors?.length) document.getElementById('autor').value = livro.authors.map(a => a.name).join(', ');
                    if (livro.cover?.large)    document.getElementById('capa_url').value = livro.cover.large;
                    document.getElementById('scanner-status').textContent = '✅ Dados preenchidos automaticamente';
                } else {
                    document.getElementById('scanner-status').textContent = '⚠️ ISBN não encontrado — preencha manualmente';
                }
            })
            .catch(() => {
                document.getElementById('scanner-status').textContent = '⚠️ Sem conexão para buscar dados';
            });
    }

    function reiniciarScanner() {
        document.getElementById('formulario').style.display = 'none';
        document.getElementById('btn-salvar').style.display = 'none';
        document.getElementById('btn-novo-scan').style.display = 'none';
        document.getElementById('scanner-status').textContent = 'Aguardando leitura...';
        ['titulo', 'autor', 'isbn', 'capa_url', 'nota_instrucao'].forEach(id => document.getElementById(id).value = '');
        iniciarScanner();
    }

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

    iniciarScanner();
</script>
</body>
</html>