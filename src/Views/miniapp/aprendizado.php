<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Meu Aprendizado</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg">
    <h1 class="text-xl font-bold">Meu aprendizado</h1>
    <p class="mt-1 text-sm text-gray-500">Acompanhe sua trilha de estudos e veja em que etapa você está.</p>

    <div id="loading" class="mt-6 text-sm text-gray-400">Carregando acompanhamento...</div>
    <div id="erro" class="mt-6 hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden">
        <div class="card mt-5 rounded-2xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400">Aprendiz</div>
            <div id="nome" class="mt-1 text-lg font-semibold"></div>
            <div id="resumo" class="mt-2 text-sm text-gray-600"></div>
        </div>

        <div class="card mt-4 rounded-2xl p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-400">Etapa atual</div>
                    <div id="etapa-atual" class="mt-1 text-base font-semibold"></div>
                </div>
                <div id="percentual" class="text-lg font-bold"></div>
            </div>
        </div>

        <div class="mt-4 space-y-3" id="timeline"></div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

function esc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function carregar() {
    try {
        const res = await fetch('/api/miniapp/aprendizado?initData=' + encodeURIComponent(tg.initData));
        const json = await res.json();
        document.getElementById('loading').classList.add('hidden');
        if (!json.ok) {
            throw new Error(json.erro || 'Não conseguimos carregar o acompanhamento agora. Tente novamente em alguns minutos.');
        }

        const dados = json.dados;
        document.getElementById('conteudo').classList.remove('hidden');
        document.getElementById('nome').innerHTML = esc(dados.aprendiz.nome);
        document.getElementById('resumo').innerHTML = `${dados.resumo.total_concluidas} de ${dados.resumo.total_etapas} etapas concluídas`;
        document.getElementById('etapa-atual').innerHTML = dados.resumo.etapa_atual
            ? `Etapa ${dados.resumo.etapa_atual.ordem} · ${esc(dados.resumo.etapa_atual.titulo)}`
            : 'Sem etapa atual';
        document.getElementById('percentual').innerHTML = `${dados.resumo.percentual_conclusao}%`;

        const timeline = document.getElementById('timeline');
        timeline.innerHTML = '';
        dados.etapas.forEach(etapa => {
            const ativo = dados.resumo.etapa_atual && etapa.ordem === dados.resumo.etapa_atual.ordem;
            const concluido = ['concluido', 'certificado_solicitado'].includes(etapa.status);
            const div = document.createElement('div');
            div.className = 'card rounded-2xl p-4 border ' + (ativo ? 'border-amber-300' : (concluido ? 'border-emerald-300' : 'border-gray-200'));
            div.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-400">Etapa ${etapa.ordem}</div>
                        <div class="mt-1 text-sm font-semibold">${esc(etapa.titulo)}</div>
                        <div class="mt-2 inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">${esc(etapa.status)}</div>
                        ${etapa.observacao_vigilante ? `<p class="mt-3 text-sm text-gray-600">${esc(etapa.observacao_vigilante)}</p>` : ''}
                    </div>
                </div>
            `;
            timeline.appendChild(div);
        });
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
    }
}

carregar();
</script>
</body>
</html>
