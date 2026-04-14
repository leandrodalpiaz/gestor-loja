<?php
// src/Views/tesouraria_comprovantes.php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}
$configuracaoLoja = $configuracaoLoja ?? [];
$categoriasEntrada = $categoriasEntrada ?? [];
$pixTipo = (string) ($configuracaoLoja['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoLoja['pix_chave_valor'] ?? '');
$pixBeneficiario = (string) ($configuracaoLoja['pix_beneficiario'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validacao de Comprovantes - Tesouraria</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <header class="mb-6 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d6b672,transparent_30%),linear-gradient(135deg,#162033,#223145)] px-6 py-7 text-white shadow-xl">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Tesouraria</p>
                    <h1 class="mt-2 text-3xl font-semibold">Caixa de Entrada - Comprovantes PIX</h1>
                    <p class="mt-2 max-w-3xl text-sm text-slate-200">Validacao clara dos comprovantes recebidos, com prioridade total para pendencias.</p>
                </div>
                <a href="/dashboard" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Voltar ao dashboard</a>
            </div>
        </header>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <article class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-800">Prioridade</p>
                <p class="mt-2 text-base font-semibold text-amber-950">Comece pelos comprovantes pendentes</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm md:col-span-2">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">PIX oficial da Loja</p>
                <p class="mt-2 text-sm text-slate-700"><strong><?php echo htmlspecialchars($pixTipo); ?> <?php echo htmlspecialchars($pixValor); ?></strong><?php if ($pixBeneficiario !== ''): ?> • <?php echo htmlspecialchars($pixBeneficiario); ?><?php endif; ?></p>
            </article>
        </section>

        <div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-2">
            <button type="button" data-status="pendente" onclick="filtrarStatus('pendente')" class="tab-status rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                Pendentes (<span id="count-pendentes">0</span>)
            </button>
            <button type="button" data-status="aprovado" onclick="filtrarStatus('aprovado')" class="tab-status rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                Aprovados (<span id="count-aprovados">0</span>)
            </button>
            <button type="button" data-status="rejeitado" onclick="filtrarStatus('rejeitado')" class="tab-status rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                Rejeitados (<span id="count-rejeitados">0</span>)
            </button>
        </div>

        <div id="comprovantes-container" class="space-y-4">
            <p class="text-center text-gray-500">Carregando...</p>
        </div>
    </div>

    <div id="modal-validacao" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="max-h-96 w-full max-w-2xl overflow-y-auto rounded-lg bg-white shadow-lg">
            <div class="sticky top-0 border-b border-gray-200 bg-gray-50 p-6">
                <h2 class="text-lg font-bold">Validar Comprovante</h2>
            </div>
            <form id="form-validacao" class="space-y-4 p-6">
                <input type="hidden" id="comprovante-id">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Obreiro</label>
                        <input type="text" id="obreiro-info" class="w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" readonly>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Valor informado (R$)</label>
                        <input type="text" id="valor-informado" class="w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Periodo informado</label>
                        <input type="text" id="periodo-informado" class="w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" readonly>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Data do envio</label>
                        <input type="text" id="data-envio" class="w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" readonly>
                    </div>
                </div>

                <hr>

                <div>
                    <label class="mb-1 block text-sm font-medium">Valor validado (R$) *</label>
                    <input type="number" id="valor-validado" step="0.01" min="0" class="w-full rounded border border-gray-300 px-3 py-2" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Mes de referencia *</label>
                    <select id="mes-validado" class="w-full rounded border border-gray-300 px-3 py-2" required>
                        <option value="">Selecionar</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Marco</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Ano de referencia *</label>
                    <select id="ano-validado" class="w-full rounded border border-gray-300 px-3 py-2" required>
                        <option value="">Selecionar</option>
                        <?php
                        $anoAtual = (int) date('Y');
                        for ($a = $anoAtual - 1; $a <= $anoAtual; $a++) {
                            echo "<option value=\"$a\">$a</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Rotulo do pagamento</label>
                    <input type="text" id="rotulo-pagamento" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="Ex.: Mensalidade 05/2026 + Biblioteca">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Categoria financeira</label>
                    <select id="categoria-id" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option value="">Selecionar</option>
                        <?php foreach ($categoriasEntrada as $categoria): ?>
                            <option value="<?php echo (int) $categoria['id']; ?>"><?php echo htmlspecialchars((string) $categoria['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Baixar em obrigacao aberta</label>
                    <select id="obrigacao-parcela-id" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option value="">Lancar sem vincular parcela especifica</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalValidacao()" class="flex-1 rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">Cancelar</button>
                    <button type="button" onclick="rejeitarComprovante()" class="flex-1 rounded bg-red-700 px-4 py-2 text-white hover:bg-red-800">Rejeitar</button>
                    <button type="submit" class="flex-1 rounded bg-green-700 px-4 py-2 text-white hover:bg-green-800">Aprovar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-rejeicao" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
            <h2 class="mb-4 text-lg font-bold">Motivo da rejeicao</h2>
            <textarea id="motivo-rejeicao" class="mb-4 h-24 w-full rounded border border-gray-300 px-3 py-2" placeholder="Explique por que este comprovante esta sendo rejeitado..."></textarea>
            <div class="flex gap-2">
                <button type="button" onclick="fecharModalRejeicao()" class="flex-1 rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">Cancelar</button>
                <button type="button" onclick="confirmarRejeicao()" class="flex-1 rounded bg-red-700 px-4 py-2 text-white hover:bg-red-800">Confirmar Rejeicao</button>
            </div>
        </div>
    </div>

    <script>
        let statusAtual = 'pendente';
        let comprovanteSendoRejeitado = null;

        function formatarMoeda(valor) {
            return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function aplicarTabAtiva() {
            document.querySelectorAll('.tab-status').forEach((aba) => {
                const ativa = aba.dataset.status === statusAtual;
                aba.classList.toggle('border-blue-200', ativa);
                aba.classList.toggle('bg-blue-50', ativa);
                aba.classList.toggle('text-blue-700', ativa);
                aba.classList.toggle('font-semibold', ativa);
                aba.classList.toggle('border-slate-200', !ativa);
                aba.classList.toggle('bg-white', !ativa);
                aba.classList.toggle('text-gray-600', !ativa);
            });
        }

        async function carregarComprovantes() {
            try {
                const res = await fetch('/api/tesouraria/comprovantes');
                const json = await res.json();
                atualizarLista(json.comprovantes || []);
                atualizarContadores(json.comprovantes || []);
                aplicarTabAtiva();
            } catch (err) {
                console.error('Erro ao carregar:', err);
            }
        }

        function filtrarStatus(status) {
            statusAtual = status;
            aplicarTabAtiva();
            carregarComprovantes();
        }

        function atualizarContadores(comprovantes) {
            const pendentes = comprovantes.filter(c => c.status === 'pendente').length;
            const aprovados = comprovantes.filter(c => c.status === 'aprovado').length;
            const rejeitados = comprovantes.filter(c => c.status === 'rejeitado').length;

            document.getElementById('count-pendentes').textContent = pendentes;
            document.getElementById('count-aprovados').textContent = aprovados;
            document.getElementById('count-rejeitados').textContent = rejeitados;
        }

        function atualizarLista(comprovantes) {
            const filtrados = comprovantes.filter(c => c.status === statusAtual);
            const container = document.getElementById('comprovantes-container');

            if (filtrados.length === 0) {
                container.innerHTML = `<p class="text-center text-gray-500">Nenhum comprovante com status "${statusAtual}"</p>`;
                return;
            }

            container.innerHTML = filtrados.map(c => `
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-gray-900">${c.obreiro_nome || 'ID Telegram: ' + c.telegram_user_id}</h3>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold ${
                                    c.status === 'pendente'
                                        ? 'bg-amber-100 text-amber-800'
                                        : c.status === 'aprovado'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-red-100 text-red-700'
                                }">${c.status}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">Valor informado: <strong>${formatarMoeda(c.valor_informado)}</strong></p>
                            <p class="text-sm text-gray-600">Rotulo informado: <strong>${c.rotulo_pagamento || c.descricao_usuario || '-'}</strong></p>
                            <p class="text-sm text-gray-600">Periodo: <strong>${c.mes_ref_informado ? String(c.mes_ref_informado).padStart(2, '0') : '?'}/${c.ano_ref_informado || '?'}</strong></p>
                            <p class="mt-2 text-xs text-gray-500">Recebido em: ${new Date(c.criado_em).toLocaleString('pt-BR')}</p>
                            ${c.status === 'aprovado' ? `<p class="mt-2 text-xs text-green-700"><strong>Aprovado:</strong> ${formatarMoeda(c.valor_validado)} em ${String(c.mes_ref_validado || '').padStart(2, '0')}/${c.ano_ref_validado || '?'}</p>` : ''}
                            ${c.status === 'rejeitado' ? `<p class="mt-2 text-xs text-red-600"><strong>Motivo:</strong> ${c.motivo_rejeicao || '-'}</p>` : ''}
                        </div>
                        <div class="flex gap-2">
                            ${c.status === 'pendente' ? `
                                <button onclick="abrirValidacao(${c.id})" class="rounded bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-800">
                                    Validar
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function abrirValidacao(id) {
            try {
                const res = await fetch(`/api/tesouraria/comprovantes/${id}`);
                const json = await res.json();
                const c = json.comprovante;

                document.getElementById('comprovante-id').value = c.id;
                document.getElementById('obreiro-info').value = c.obreiro_nome || ('Telegram: ' + c.telegram_user_id);
                document.getElementById('valor-informado').value = formatarMoeda(c.valor_informado);
                document.getElementById('periodo-informado').value = `${String(c.mes_ref_informado || '').padStart(2, '0')}/${c.ano_ref_informado || '?'}`;
                document.getElementById('data-envio').value = new Date(c.criado_em).toLocaleString('pt-BR');
                document.getElementById('valor-validado').value = c.valor_informado || '';
                document.getElementById('mes-validado').value = c.mes_ref_informado || new Date().getMonth() + 1;
                document.getElementById('ano-validado').value = c.ano_ref_informado || new Date().getFullYear();
                document.getElementById('rotulo-pagamento').value = c.rotulo_pagamento || c.descricao_usuario || '';
                document.getElementById('categoria-id').value = c.categoria_id || '';
                await carregarObrigacoesAbertas(c.obreiro_id, c.obrigacao_parcela_id || '');

                const modal = document.getElementById('modal-validacao');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } catch (err) {
                console.error('Erro:', err);
            }
        }

        async function carregarObrigacoesAbertas(obreiroId, selecionada) {
            const select = document.getElementById('obrigacao-parcela-id');
            select.innerHTML = '<option value="">Lancar sem vincular parcela especifica</option>';
            if (!obreiroId) return;

            try {
                const res = await fetch(`/api/tesouraria/obrigacoes-abertas?obreiro_id=${encodeURIComponent(obreiroId)}`);
                const json = await res.json();
                const parcelas = json.parcelas || [];
                parcelas.forEach((parcela) => {
                    const option = document.createElement('option');
                    option.value = parcela.id;
                    option.textContent = `${parcela.titulo} • ${parcela.competencia_label || '-'} • ${formatarMoeda(parcela.valor_previsto)}`;
                    if (String(selecionada) === String(parcela.id)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            } catch (err) {
                console.error('Erro ao carregar obrigacoes abertas:', err);
            }
        }

        function fecharModalValidacao() {
            const modal = document.getElementById('modal-validacao');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('form-validacao').reset();
        }

        function rejeitarComprovante() {
            comprovanteSendoRejeitado = document.getElementById('comprovante-id').value;
            const modal = document.getElementById('modal-rejeicao');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function fecharModalRejeicao() {
            const modal = document.getElementById('modal-rejeicao');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('motivo-rejeicao').value = '';
        }

        async function confirmarRejeicao() {
            const motivo = document.getElementById('motivo-rejeicao').value;
            if (!motivo.trim()) {
                alert('Por favor, informe um motivo');
                return;
            }

            try {
                const res = await fetch('/api/tesouraria/comprovantes/rejeitar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: comprovanteSendoRejeitado, motivo })
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalRejeicao();
                    fecharModalValidacao();
                    carregarComprovantes();
                }
            } catch (err) {
                console.error('Erro:', err);
            }
        }

        document.getElementById('form-validacao').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('comprovante-id').value,
                valor: parseFloat(document.getElementById('valor-validado').value),
                mes: parseInt(document.getElementById('mes-validado').value),
                ano: parseInt(document.getElementById('ano-validado').value),
                rotulo_pagamento: document.getElementById('rotulo-pagamento').value,
                categoria_id: document.getElementById('categoria-id').value ? parseInt(document.getElementById('categoria-id').value) : null,
                obrigacao_parcela_id: document.getElementById('obrigacao-parcela-id').value ? parseInt(document.getElementById('obrigacao-parcela-id').value) : null
            };

            try {
                const res = await fetch('/api/tesouraria/comprovantes/aprovar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalValidacao();
                    carregarComprovantes();
                }
            } catch (err) {
                console.error('Erro:', err);
            }
        });

        carregarComprovantes();
        setInterval(carregarComprovantes, 10000);
    </script>
</body>
</html>
