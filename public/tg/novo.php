<?php
declare(strict_types=1);
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
            --bg: var(--tg-theme-bg-color, #f5f5f0);
            --bg2: var(--tg-theme-secondary-bg-color, #ffffff);
            --text: var(--tg-theme-text-color, #1a1a1a);
            --hint: var(--tg-theme-hint-color, #6b7280);
            --btn: var(--tg-theme-button-color, #0047ab);
            --btn-text: var(--tg-theme-button-text-color, #ffffff);
            --border: var(--tg-theme-hint-color, #d1d5db);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 16px 16px 86px;
        }
        h2 { margin-bottom: 6px; }
        .subtitulo { color: var(--hint); margin-bottom: 14px; font-size: 13px; }
        .secao {
            background: var(--bg2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 14px;
            border: 1px solid rgba(0, 0, 0, 0.06);
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
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
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
        }
        input:focus, select:focus, textarea:focus { border-color: var(--btn); }
        textarea { resize: none; height: 72px; }
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
        .rodape {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--bg2);
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            padding: 12px 16px;
            display: flex;
            gap: 10px;
        }
        .btn {
            border-radius: 10px;
            border: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            padding: 12px;
        }
        .btn-primary {
            flex: 1;
            background: var(--btn);
            color: var(--btn-text);
        }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-secondary {
            flex: 0 0 auto;
            background: transparent;
            color: var(--btn);
            border: 1px solid var(--btn);
            padding-inline: 20px;
        }
    </style>
</head>
<body>

<h2>Cadastro Manual de Livro</h2>
<p class="subtitulo">Preencha os dados para cadastrar o livro no acervo.</p>

<div id="erro-geral" class="erro-geral"></div>

<div class="secao">
    <div class="secao-titulo">Identificacao</div>
    <div class="campo">
        <label>Titulo da Obra <span class="obrig">*</span></label>
        <input type="text" id="titulo" placeholder="Ex.: Morals and Dogma">
    </div>
    <div class="campo">
        <label>Autor <span class="obrig">*</span></label>
        <input type="text" id="autor" placeholder="Ex.: Albert Pike">
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
    <div class="secao-titulo">Catalogacao</div>
    <div class="campo">
        <label>Tipo de Material <span class="obrig">*</span></label>
        <select id="tipo">
            <option value="Livro Fisico">Livro Fisico</option>
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
        <label>Resumo / Sinopse</label>
        <textarea id="resumo" placeholder="Informe um resumo da obra (preenchido automaticamente quando o ISBN retornar dados)."></textarea>
    </div>
    <div class="campo">
        <label>Grau Recomendado</label>
        <select id="grau_recomendado">
            <option value="Livre">Livre / Todos os Graus</option>
            <option value="Aprendiz">Aprendiz</option>
            <option value="Companheiro">Companheiro</option>
            <option value="Mestre">Mestre</option>
        </select>
    </div>
    <div class="campo">
        <label>Nota de Instrucao</label>
        <textarea id="nota_instrucao" placeholder="Ex.: Leitura essencial para a elevacao."></textarea>
    </div>
</div>

<div class="rodape">
    <button class="btn btn-secondary" onclick="fecharApp()">Cancelar</button>
    <button class="btn btn-primary" id="btn-salvar" onclick="salvar()">Salvar Livro</button>
</div>

<script>
    const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
    if (tg) {
        tg.expand();
        tg.ready();
    }

    function fecharApp() {
        if (tg && typeof tg.close === "function") {
            tg.close();
            return;
        }
        window.history.back();
    }

    function mostrarErro(msg) {
        const el = document.getElementById("erro-geral");
        el.textContent = msg;
        el.style.display = "block";
        el.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function notificarSucesso(mensagem) {
        if (tg && typeof tg.showAlert === "function") {
            tg.showAlert(mensagem, () => fecharApp());
            return;
        }
        alert(mensagem);
        fecharApp();
    }

    async function salvar() {
        document.getElementById("erro-geral").style.display = "none";

        const titulo = document.getElementById("titulo").value.trim();
        const autor = document.getElementById("autor").value.trim();
        const quantidade = parseInt(document.getElementById("quantidade").value, 10);

        if (!titulo) { mostrarErro("Informe o titulo da obra para continuar."); return; }
        if (!autor) { mostrarErro("Informe o autor para continuar."); return; }
        if (!quantidade || quantidade < 1) { mostrarErro("Informe uma quantidade valida (minimo 1)."); return; }

        const btn = document.getElementById("btn-salvar");
        btn.disabled = true;
        btn.textContent = "Salvando...";

        try {
            const res = await fetch("/api/biblioteca/cadastrar.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    titulo,
                    autor,
                    isbn: document.getElementById("isbn").value.trim(),
                    capa_url: document.getElementById("capa_url").value.trim(),
                    tipo: document.getElementById("tipo").value,
                    quantidade_disponivel: quantidade,
                    resumo: document.getElementById("resumo").value.trim(),
                    grau_recomendado: document.getElementById("grau_recomendado").value,
                    nota_instrucao: document.getElementById("nota_instrucao").value.trim(),
                    init_data: tg ? (tg.initData || "") : ""
                })
            });

            const data = await res.json();
            if (!data.sucesso) {
                throw new Error(data.mensagem || "Não foi possível concluir o cadastro agora. Tente novamente em instantes.");
            }

            notificarSucesso("Livro cadastrado com sucesso.");
        } catch (err) {
            mostrarErro(err.message || "Não foi possível concluir o cadastro agora. Verifique a conexão e tente novamente.");
            btn.disabled = false;
            btn.textContent = "Salvar Livro";
        }
    }
</script>

</body>
</html>
