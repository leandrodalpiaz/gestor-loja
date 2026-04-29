<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Secretaria Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
        input, textarea, select {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">Secretaria Mobile</h1>
        <p class="mt-1 text-sm text-gray-500">SessÃµes, trabalhos, balaÃºstres, publicaÃ§Ãµes e relatÃ³rio em um Ãºnico painel.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400">Registros</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Total</div><div id="cad-total" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Alertas</div><div id="cad-alerta" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">No quadro</div><div id="cad-ativos" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Com bot</div><div id="cad-bot" class="mt-1 text-lg font-semibold"></div></div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-400">SessÃ£o em foco</div>
                    <div id="sessao-titulo" class="mt-1 text-base font-semibold"></div>
                    <div id="sessao-meta" class="mt-1 text-sm text-gray-600"></div>
                </div>
                <div class="flex gap-2">
                    <button id="btn-editar-sessao" type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700">Editar</button>
                    <button id="toggle-form" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white">Nova</button>
                </div>
            </div>
            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Trocar sessÃ£o</label>
                <select id="sessao-select" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Confirmados</div><div id="sessao-confirmados" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Ausentes</div><div id="sessao-ausentes" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Ãgape</div><div id="sessao-agape" class="mt-1 text-lg font-semibold"></div></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button id="btn-publicar" type="button" class="rounded-xl bg-amber-600 px-3 py-2 text-sm font-medium text-white">Publicar</button>
                <button id="btn-cancelar" type="button" class="rounded-xl bg-rose-600 px-3 py-2 text-sm font-medium text-white">Cancelar</button>
                <button id="btn-reabrir" type="button" class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-medium text-white">Reabrir</button>
            </div>
        </div>

        <form id="form-sessao" class="card hidden rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div><div class="text-xs uppercase tracking-wide text-gray-400">SessÃ£o</div><div id="form-titulo" class="mt-1 text-base font-semibold">Nova sessÃ£o</div></div>
                <button id="cancel-form" type="button" class="text-sm text-gray-500">Fechar</button>
            </div>
            <input type="hidden" id="form-sessao-id">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">TÃ­tulo</label><input id="titulo" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" required></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Data e hora de inÃ­cio</label><input id="data_hora_inicio" type="datetime-local" class="w-full rounded-lg border px-3 py-2 text-sm" required></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Data e hora de encerramento</label><input id="data_hora_fim" type="datetime-local" class="w-full rounded-lg border px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Grau</label><select id="grau_sessao" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="Aprendiz">Aprendiz</option><option value="Companheiro">Companheiro</option><option value="Mestre">Mestre</option><option value="Outro">Outro</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Grau personalizado</label><input id="grau_personalizado" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Somente quando grau for Outro"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Tipo principal</label><select id="tipo_sessao_principal" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="economica">EconÃ´mica</option><option value="magna">Magna</option><option value="outra">Outra</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Subtipo</label><select id="tipo_sessao_subtipo" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="economica_1">EconÃ´mica de 1Âº Grau</option><option value="economica_2">EconÃ´mica de 2Âº Grau</option><option value="economica_3">EconÃ´mica de 3Âº Grau</option><option value="magna_iniciacao">Magna de IniciaÃ§Ã£o</option><option value="magna_elevacao">Magna de ElevaÃ§Ã£o</option><option value="magna_exaltacao">Magna de ExaltaÃ§Ã£o</option><option value="magna_instalacao">Magna de InstalaÃ§Ã£o</option><option value="outra">Outra</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Tipo personalizado</label><input id="tipo_sessao_personalizado" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Use se nÃ£o houver na lista"></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Ãgape</label><select id="agape_modalidade" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="nao_havera">NÃ£o haverÃ¡</option><option value="gratuito">Gratuito</option><option value="pago">Pago</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Ordem do dia</label><textarea id="ordem_dia" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea></div>

            <details class="rounded-xl border border-slate-200 bg-white/70 p-3">
                <summary class="cursor-pointer text-sm font-semibold text-slate-700">Editar completo (datas, vÃ­nculo e relatÃ³rio)</summary>
                <div class="mt-3 space-y-3">
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Traje</label><select id="traje_tipo" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="maconico">MaÃ§Ã´nico</option><option value="livre">Livre</option><option value="outro">Outro</option></select></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Traje personalizado</label><input id="traje_personalizado" type="text" class="w-full rounded-lg border px-3 py-2 text-sm"></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Modelo financeiro do Ã¡gape</label><select id="agape_modelo_financeiro" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="oficial_loja">Oficial da Loja</option><option value="particular">Particular</option><option value="misto">Misto</option></select></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Valor de referÃªncia do Ã¡gape</label><input id="agape_valor" type="text" class="w-full rounded-lg border px-3 py-2 text-sm"></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">GestÃ£o de referÃªncia</label><input id="gestao_referencia" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Ex.: 2026/2027"></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Natureza da sessÃ£o</label><select id="natureza_sessao" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="ordinaria">OrdinÃ¡ria</option><option value="extraordinaria">ExtraordinÃ¡ria</option><option value="magna">Magna</option></select></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Formato da sessÃ£o</label><select id="formato_sessao" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="templo">Templo</option><option value="a_campo">A campo</option><option value="publica">PÃºblica</option><option value="branca">Branca</option></select></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Finalidade ritual</label><select id="finalidade_ritual" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="economica">EconÃ´mica</option><option value="iniciacao">IniciaÃ§Ã£o</option><option value="elevacao">ElevaÃ§Ã£o</option><option value="exaltacao">ExaltaÃ§Ã£o</option><option value="instalacao">InstalaÃ§Ã£o</option><option value="outra">Outra</option></select></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">Templo/local</label><input id="templo_local" type="text" class="w-full rounded-lg border px-3 py-2 text-sm"></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">ObservaÃ§Ã£o interna</label><textarea id="observacao_interna" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">ObservaÃ§Ã£o para relatÃ³rio</label><textarea id="observacao_relatorio" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea></div>
                    <label class="inline-flex items-center gap-2 text-sm"><input id="sessao_branca" type="checkbox"> SessÃ£o branca/festiva</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input id="sessao_a_campo" type="checkbox"> SessÃ£o a campo</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input id="conta_relatorio_potencia" type="checkbox" checked> Conta no relatÃ³rio da potÃªncia</label>
                </div>
            </details>

            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">Salvar sessÃ£o</button>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="card rounded-2xl p-4"><div class="text-sm font-semibold">Confirmados</div><div id="lista-confirmados" class="mt-3 space-y-2 text-sm"></div></div>
            <div class="card rounded-2xl p-4"><div class="text-sm font-semibold">Ãgape</div><div id="lista-agape" class="mt-3 space-y-2 text-sm"></div></div>
        </div>

        <div class="card rounded-2xl p-4"><div class="text-sm font-semibold">HistÃ³rico recente</div><div id="lista-historico" class="mt-3 space-y-2 text-sm"></div></div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Trabalhos recentes</div>
                <div class="mt-3 space-y-2">
                    <input id="trabalho-titulo" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="TÃ­tulo do trabalho">
                    <select id="trabalho-tipo" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="peca_arquitetura">PeÃ§a de arquitetura</option><option value="instrucao">InstruÃ§Ã£o</option><option value="prancha">Prancha</option><option value="trabalho">Trabalho</option></select>
                    <button id="btn-salvar-trabalho" class="w-full rounded-xl bg-blue-700 px-3 py-3 text-sm font-medium text-white">Registrar trabalho</button>
                </div>
                <div id="lista-trabalhos" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div id="card-balaustre" class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Balaustres recentes</div>
                <div class="mt-3 space-y-2">
                    <div class="rounded-xl border border-slate-200 bg-white/70 p-3 text-xs text-slate-600">
                        Durante a sessÃ£o, salve em rascunho quantas vezes precisar. SÃ³ finalize quando estiver pronto para votaÃ§Ã£o.
                    </div>
                    <input id="balaustre-numero" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="NÃºmero do balaÃºstre">
                    <textarea id="balaustre-texto" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Resumo do balaÃºstre"></textarea>
                    <button id="btn-salvar-balaustre" class="w-full rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Salvar rascunho do balaÃºstre</button>
                    <div class="grid grid-cols-3 gap-2">
                        <button id="btn-balaustre-apto" class="rounded-xl bg-amber-700 px-3 py-2 text-xs font-medium text-white">Finalizar</button>
                        <button id="btn-balaustre-abrir" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-medium text-white">Abrir votaÃ§Ã£o</button>
                        <button id="btn-balaustre-encerrar" class="rounded-xl bg-rose-700 px-3 py-2 text-xs font-medium text-white">Encerrar</button>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white/70 p-3 space-y-2">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Votar no balaÃºstre em foco</div>
                        <select id="balaustre-voto" class="w-full rounded-lg border px-3 py-2 text-sm">
                            <option value="aprovar">Aprovar</option>
                            <option value="pedir_correcao">Pedir correÃ§Ã£o</option>
                            <option value="rejeitar">Rejeitar</option>
                        </select>
                        <input id="balaustre-justificativa" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Justificativa (opcional)">
                        <button id="btn-balaustre-votar" class="w-full rounded-xl border border-erp-navy px-3 py-2 text-sm font-medium text-slate-800">Votar</button>
                    </div>
                </div>
                <div id="lista-balaustres" class="mt-3 space-y-2 text-sm"></div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">PublicaÃ§Ãµes oficiais</div>
            <div class="mt-3 space-y-2">
                <input id="publicacao-titulo" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="TÃ­tulo da publicaÃ§Ã£o">
                <select id="publicacao-tipo" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="agenda_oficial">Agenda oficial</option><option value="proxima_sessao">PrÃ³xima sessÃ£o</option><option value="informativo_potencia">Informativo da potÃªncia</option><option value="convite_externo">Convite externo</option><option value="comunicado">Comunicado</option></select>
                <select id="publicacao-canal" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="grupo">Grupo</option><option value="web">Web</option><option value="interno">Interno</option><option value="misto">Misto</option></select>
                <select id="publicacao-status" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="rascunho">Rascunho</option><option value="publicado">Publicado</option><option value="cancelado">Cancelado</option></select>
                <textarea id="publicacao-conteudo" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="ConteÃºdo"></textarea>
                <input id="publicacao-observacao" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="ObservaÃ§Ã£o (opcional)">
                <button id="btn-salvar-publicacao" class="w-full rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Registrar publicaÃ§Ã£o</button>
            </div>
            <div id="lista-publicacoes" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">RelatÃ³rio anual</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">SessÃµes</div><div id="rel-sessoes" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Visitantes</div><div id="rel-visitantes" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Visitas externas</div><div id="rel-visitas" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Congressos/Palestras</div><div id="rel-eventos" class="mt-1 text-lg font-semibold"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();
let dashboard = null;
let sessaoAtualId = null;

function esc(v) { return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
function setValue(id, value, fallback = '') { document.getElementById(id).value = value ?? fallback; }

async function api(url, options = {}) {
    const finalOptions = { ...options };
    finalOptions.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
    if (finalOptions.body && typeof finalOptions.body !== 'string') {
        finalOptions.body = JSON.stringify({ ...finalOptions.body, initData: tg.initData });
    }
    const joiner = url.includes('?') ? '&' : '?';
    const response = await fetch(url + joiner + 'initData=' + encodeURIComponent(tg.initData), finalOptions);
    const json = await response.json();
    if (!json.ok) throw new Error(json.erro || 'NÃ£o conseguimos concluir sua solicitaÃ§Ã£o agora. Tente novamente em alguns minutos.');
    return json;
}

function renderLista(id, itens, vazio) {
    const root = document.getElementById(id);
    root.innerHTML = '';
    if (!itens || itens.length === 0) {
        root.innerHTML = `<div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-gray-500">${esc(vazio)}</div>`;
        return;
    }
    itens.forEach(item => {
        const div = document.createElement('div');
        div.className = 'rounded-xl border border-gray-200 bg-white/70 px-3 py-2';
        div.innerHTML = `<div class="font-medium">${esc(item.nome || item.acao || 'Item')}</div>${item.cim ? `<div class="text-xs text-gray-500">CIM ${esc(item.cim)}</div>` : ''}${item.observacao ? `<div class="text-xs text-gray-500 mt-1">${esc(item.observacao)}</div>` : ''}${item.autor_nome ? `<div class="text-xs text-gray-500 mt-1">${esc(item.autor_nome)} Â· ${esc(item.created_at || '')}</div>` : ''}`;
        root.appendChild(div);
    });
}

function preencherFormulario(sessao = null) {
    document.getElementById('form-sessao-id').value = sessao?.id || '';
    setValue('titulo', sessao?.titulo);
    setValue('data_hora_inicio', sessao?.data_hora_inicio ? String(sessao.data_hora_inicio).slice(0, 16) : '');
    setValue('data_hora_fim', sessao?.data_hora_fim ? String(sessao.data_hora_fim).slice(0, 16) : '');
    setValue('grau_sessao', sessao?.grau_sessao, 'Aprendiz');
    setValue('grau_personalizado', sessao?.grau_personalizado);
    setValue('tipo_sessao_principal', sessao?.tipo_sessao_principal, 'economica');
    setValue('tipo_sessao_subtipo', sessao?.tipo_sessao_subtipo, 'economica_1');
    setValue('tipo_sessao_personalizado', sessao?.tipo_sessao_personalizado);
    setValue('agape_modalidade', sessao?.agape_modalidade, 'nao_havera');
    setValue('ordem_dia', sessao?.ordem_dia);
    setValue('traje_tipo', sessao?.traje_tipo, 'maconico');
    setValue('traje_personalizado', sessao?.traje_personalizado);
    setValue('agape_modelo_financeiro', sessao?.agape_modelo_financeiro, 'oficial_loja');
    setValue('agape_valor', sessao?.agape_valor);
    setValue('gestao_referencia', sessao?.gestao_referencia);
    setValue('natureza_sessao', sessao?.natureza_sessao, 'ordinaria');
    setValue('formato_sessao', sessao?.formato_sessao, 'templo');
    setValue('finalidade_ritual', sessao?.finalidade_ritual, 'economica');
    setValue('templo_local', sessao?.templo_local);
    setValue('observacao_interna', sessao?.observacao_interna);
    setValue('observacao_relatorio', sessao?.observacao_relatorio);
    document.getElementById('sessao_branca').checked = Boolean(sessao?.sessao_branca);
    document.getElementById('sessao_a_campo').checked = Boolean(sessao?.sessao_a_campo);
    document.getElementById('conta_relatorio_potencia').checked = sessao ? Boolean(sessao.conta_relatorio_potencia) : true;
    document.getElementById('form-titulo').textContent = sessao ? 'Editar sessÃ£o' : 'Nova sessÃ£o';
    document.getElementById('form-sessao').classList.remove('hidden');
}

function render() {
    if (!dashboard) return;
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');
    document.getElementById('cad-total').textContent = dashboard.resumo_cadastros.total ?? 0;
    document.getElementById('cad-alerta').textContent = dashboard.resumo_cadastros.com_alerta ?? 0;
    document.getElementById('cad-ativos').textContent = dashboard.resumo_cadastros.ativos ?? 0;
    document.getElementById('cad-bot').textContent = dashboard.resumo_cadastros.com_telegram ?? 0;

    const sessao = dashboard.sessao_foco;
    document.getElementById('sessao-titulo').textContent = sessao ? (sessao.titulo || sessao.tipo_descricao || 'SessÃ£o') : 'Sem sessÃ£o em foco';
    document.getElementById('sessao-meta').textContent = sessao ? `${sessao.data_hora_inicio || ''} Â· ${sessao.status || ''}` : 'Sem dados';
    document.getElementById('sessao-confirmados').textContent = sessao ? (sessao.total_confirmados ?? 0) : 0;
    document.getElementById('sessao-ausentes').textContent = sessao ? (sessao.total_ausentes ?? 0) : 0;
    document.getElementById('sessao-agape').textContent = sessao ? (sessao.total_agape ?? 0) : 0;
    document.getElementById('btn-editar-sessao').disabled = !sessao;

    const select = document.getElementById('sessao-select');
    select.innerHTML = '';
    (dashboard.sessoes || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.titulo || item.tipo_descricao || 'SessÃ£o'} Â· ${item.status}`;
        if (sessao && item.id === sessao.id) option.selected = true;
        select.appendChild(option);
    });

    renderLista('lista-confirmados', dashboard.confirmados, 'Sem confirmados nesta sessÃ£o.');
    renderLista('lista-agape', dashboard.participantes_agape, 'Sem participantes confirmados para o Ã¡gape.');
    renderLista('lista-historico', dashboard.historico, 'Sem histÃ³rico recente.');
    renderLista('lista-trabalhos', (dashboard.trabalhos_recentes || []).map(item => ({ nome: item.titulo || 'Trabalho', observacao: `${item.sessao_titulo || 'SessÃ£o'} Â· ${item.status_envio_potencia || 'pendente'}` })), 'Sem trabalhos recentes.');
    renderLista('lista-balaustres', (dashboard.balaustres_recentes || []).map(item => ({ nome: item.numero_balaustre || 'BalaÃºstre sem nÃºmero', observacao: `${item.sessao_titulo || 'SessÃ£o'} Â· ${item.status || ''}` })), 'Sem balaÃºstres recentes.');
    renderLista('lista-publicacoes', (dashboard.publicacoes_recentes || []).map(item => ({ nome: item.titulo || 'PublicaÃ§Ã£o', observacao: `${item.tipo_publicacao || 'comunicado'} Â· ${item.status_publicacao || 'rascunho'}${item.sessao_titulo ? ' Â· ' + item.sessao_titulo : ''}` })), 'Sem publicaÃ§Ãµes recentes.');

    document.getElementById('rel-sessoes').textContent = dashboard.relatorio_anual?.sessoes ?? 0;
    document.getElementById('rel-visitantes').textContent = dashboard.relatorio_anual?.visitantes ?? 0;
    document.getElementById('rel-visitas').textContent = dashboard.relatorio_anual?.visitas_externas ?? 0;
    document.getElementById('rel-eventos').textContent = `${dashboard.relatorio_anual?.congressos ?? 0} / ${dashboard.relatorio_anual?.palestras ?? 0}`;
}

async function carregar(sessaoId = null) {
    try {
        const query = sessaoId ? ('?sessao_id=' + encodeURIComponent(sessaoId)) : '';
        const json = await api('/api/miniapp/secretaria/dashboard' + query, { method: 'GET' });
        dashboard = json.dados;
        sessaoAtualId = dashboard.sessao_foco?.id || null;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

async function acaoSessao(url) {
    if (!sessaoAtualId) return;
    try {
        await api(url, { method: 'POST', body: { sessao_id: sessaoAtualId } });
        tg.showAlert('OperaÃ§Ã£o realizada com sucesso.');
        await carregar(sessaoAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
}

async function acaoMiniapp(url, body, sucesso) {
    try {
        await api(url, { method: 'POST', body });
        tg.showAlert(sucesso);
        await carregar(sessaoAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
}

document.getElementById('sessao-select').addEventListener('change', (event) => carregar(event.target.value));
document.getElementById('btn-publicar').addEventListener('click', () => acaoSessao('/api/miniapp/secretaria/sessao/publicar'));
document.getElementById('btn-cancelar').addEventListener('click', () => acaoSessao('/api/miniapp/secretaria/sessao/cancelar'));
document.getElementById('btn-reabrir').addEventListener('click', () => acaoSessao('/api/miniapp/secretaria/sessao/reabrir'));
document.getElementById('toggle-form').addEventListener('click', () => preencherFormulario());
document.getElementById('btn-editar-sessao').addEventListener('click', () => preencherFormulario(dashboard?.sessao_foco || null));
document.getElementById('cancel-form').addEventListener('click', () => document.getElementById('form-sessao').classList.add('hidden'));

document.getElementById('form-sessao').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        const payload = {
            sessao_id: document.getElementById('form-sessao-id').value || null,
            titulo: document.getElementById('titulo').value,
            data_hora_inicio: document.getElementById('data_hora_inicio').value,
            data_hora_fim: document.getElementById('data_hora_fim').value,
            grau_sessao: document.getElementById('grau_sessao').value,
            grau_personalizado: document.getElementById('grau_personalizado').value,
            tipo_sessao_principal: document.getElementById('tipo_sessao_principal').value,
            tipo_sessao_subtipo: document.getElementById('tipo_sessao_subtipo').value,
            tipo_sessao_personalizado: document.getElementById('tipo_sessao_personalizado').value,
            traje_tipo: document.getElementById('traje_tipo').value,
            traje_personalizado: document.getElementById('traje_personalizado').value,
            agape_modalidade: document.getElementById('agape_modalidade').value,
            agape_modelo_financeiro: document.getElementById('agape_modelo_financeiro').value,
            agape_valor: document.getElementById('agape_valor').value,
            gestao_referencia: document.getElementById('gestao_referencia').value,
            natureza_sessao: document.getElementById('natureza_sessao').value,
            formato_sessao: document.getElementById('formato_sessao').value,
            finalidade_ritual: document.getElementById('finalidade_ritual').value,
            templo_local: document.getElementById('templo_local').value,
            ordem_dia: document.getElementById('ordem_dia').value,
            observacao_interna: document.getElementById('observacao_interna').value,
            observacao_relatorio: document.getElementById('observacao_relatorio').value,
            sessao_branca: document.getElementById('sessao_branca').checked,
            sessao_a_campo: document.getElementById('sessao_a_campo').checked,
            conta_relatorio_potencia: document.getElementById('conta_relatorio_potencia').checked
        };
        const json = await api('/api/miniapp/secretaria/sessao/salvar', { method: 'POST', body: payload });
        document.getElementById('form-sessao').classList.add('hidden');
        tg.showAlert('SessÃ£o salva com sucesso.');
        await carregar(json.sessao_id || null);
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('btn-salvar-trabalho').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/trabalho/salvar', {
    sessao_id: sessaoAtualId,
    titulo: document.getElementById('trabalho-titulo').value,
    tipo_trabalho: document.getElementById('trabalho-tipo').value
}, 'Trabalho registrado com sucesso.'));

document.getElementById('btn-salvar-balaustre').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/salvar', {
    sessao_id: sessaoAtualId,
    numero_balaustre: document.getElementById('balaustre-numero').value,
    texto_final: document.getElementById('balaustre-texto').value
}, 'Rascunho do balaÃºstre salvo com sucesso.'));

document.getElementById('btn-balaustre-apto').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/apto', {
    balaustre_id: dashboard?.balaustre_foco?.id || 0
}, 'BalaÃºstre finalizado e marcado como apto para votaÃ§Ã£o.'));

document.getElementById('btn-balaustre-abrir').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/abrir-votacao', {
    balaustre_id: dashboard?.balaustre_foco?.id || 0
}, 'VotaÃ§Ã£o aberta com sucesso.'));

document.getElementById('btn-balaustre-encerrar').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/encerrar-votacao', {
    balaustre_id: dashboard?.balaustre_foco?.id || 0
}, 'VotaÃ§Ã£o encerrada com sucesso.'));

document.getElementById('btn-balaustre-votar').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/votar', {
    balaustre_id: dashboard?.balaustre_foco?.id || 0,
    voto: document.getElementById('balaustre-voto').value,
    justificativa: document.getElementById('balaustre-justificativa').value
}, 'Voto registrado com sucesso.'));

document.getElementById('btn-salvar-publicacao').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/publicacao/salvar', {
    sessao_id: sessaoAtualId,
    tipo_publicacao: document.getElementById('publicacao-tipo').value,
    titulo: document.getElementById('publicacao-titulo').value,
    canal_destino: document.getElementById('publicacao-canal').value,
    status_publicacao: document.getElementById('publicacao-status').value,
    conteudo: document.getElementById('publicacao-conteudo').value,
    observacao: document.getElementById('publicacao-observacao').value
}, 'PublicaÃ§Ã£o registrada com sucesso.'));

carregar();

const foco = new URLSearchParams(window.location.search).get('foco');
if (foco === 'balaustre') {
    setTimeout(() => {
        const alvo = document.getElementById('card-balaustre');
        if (alvo) {
            alvo.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 250);
}
</script>
</body>
</html>
