<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$lista = $itens ?? [];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';

$bibliotecaPermissions = is_array($bibliotecaPermissions ?? null) ? $bibliotecaPermissions : [];
$podeGerenciar = !empty($bibliotecaPermissions['biblioteca.manage']);
$podeClassificar = !empty($bibliotecaPermissions['biblioteca.classificar']);
$redeConfigTela = is_array($bibliotecaRedeConfig ?? null) ? $bibliotecaRedeConfig : [];
$redeCompartilha = !empty($redeConfigTela['compartilhar_acervo']);
$redePermiteEmprestimo = !empty($redeConfigTela['permitir_emprestimo_cruzado']);

$formatGrau = static fn($grau) => $grau ? ucfirst(strtolower($grau)) : 'Livre';

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Estante Digital & Acervo';
$appShellDescription = 'Consulte a biblioteca da loja, realize buscas instantâneas, filtre por graus de instrução e solicite empréstimos físicos ou digitais.';
$appShellActiveHref = '/biblioteca';
$appShellActions = [
    ['label' => 'Painel', 'href' => '/dashboard'],
];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
    <div class="alert alert-success mb-6">
        <?= htmlspecialchars((string) $_SESSION['mensagem_sucesso']) ?>
    </div>
    <?php unset($_SESSION['mensagem_sucesso']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['mensagem_erro'])): ?>
    <div class="alert alert-danger mb-6">
        <?= htmlspecialchars((string) $_SESSION['mensagem_erro']) ?>
    </div>
    <?php unset($_SESSION['mensagem_erro']); ?>
<?php endif; ?>

<!-- Barra de Ações Rápidas e Busca Integrada -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Pesquisa Interna -->
    <div class="card depth-1 p-6 lg:col-span-2">
        <div class="card-header border-b border-white/5 pb-3 mb-4">
            <h2 class="card-title text-white">Pesquisa de Documentos e Publicações</h2>
            <p class="card-subtitle mt-0.5">Acesse atas, balaústres e peças de arquitetura arquivadas.</p>
        </div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a class="flex items-center justify-between p-4 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/5 transition text-sm text-slate-300 font-semibold" href="/biblioteca/trabalhos">
                <span>Peças de Arquitetura & Trabalhos</span>
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
            <a class="flex items-center justify-between p-4 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/5 transition text-sm text-slate-300 font-semibold" href="/biblioteca/balaustres">
                <span>Arquivo de Balaústres da Loja</span>
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </div>

    <!-- Navegação Administrativa da Biblioteca -->
    <div class="card depth-1 p-6">
        <div class="card-header border-b border-white/5 pb-3 mb-4">
            <h2 class="card-title text-white">Administração</h2>
            <p class="card-subtitle mt-0.5">Operações e controle de leitores.</p>
        </div>
        <div class="card-body flex flex-col gap-3">
            <a href="/biblioteca/meus-emprestimos" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2.5 w-full text-center text-xs font-semibold">
                Meus Empréstimos & Histórico
            </a>
            <?php if ($podeGerenciar): ?>
                <a href="/biblioteca/emprestimos" class="btn border border-warning/20 bg-warning/5 text-warning hover:bg-warning/10 py-2.5 w-full text-center text-xs font-bold">
                    Gerenciar Solicitações / Fila
                </a>
                <a href="/biblioteca/adicionar" class="btn btn-primary py-2.5 w-full text-center text-xs font-bold">
                    Novo Livro no Acervo
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Configuração de Rede (Visível para o Bibliotecário/Administrador) -->
<?php if ($podeGerenciar): ?>
    <div class="card depth-1 mb-8">
        <form action="/biblioteca/configurar-rede" method="POST" class="card-body p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h2 class="text-base font-bold text-white uppercase tracking-wider">Rede de Bibliotecas Macônicas</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Disponibilize o acervo para intercâmbio de conhecimento com Lojas federadas.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 rounded-xl border border-white/5 bg-white/[0.01] p-4 text-xs cursor-pointer hover:bg-white/[0.03] transition">
                        <input type="checkbox" name="compartilhar_acervo" value="1" class="form-checkbox mt-0.5 text-primary" <?= $redeCompartilha ? 'checked' : '' ?>>
                        <span>
                            <span class="block font-bold text-white">Compartilhar Acervo</span>
                            <span class="block text-slate-400 mt-0.5">Livros desta loja aparecem na busca interlojas.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-xl border border-white/5 bg-white/[0.01] p-4 text-xs cursor-pointer hover:bg-white/[0.03] transition">
                        <input type="checkbox" name="permitir_emprestimo_cruzado" value="1" class="form-checkbox mt-0.5 text-primary" <?= $redePermiteEmprestimo ? 'checked' : '' ?>>
                        <span>
                            <span class="block font-bold text-white">Permitir Empréstimo Cruzado</span>
                            <span class="block text-slate-400 mt-0.5">Obreiros de outras lojas podem solicitar empréstimos.</span>
                        </span>
                    </label>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary font-bold text-xs py-2 px-6">Salvar Configurações</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- INFO DE GRAU -->
<div class="alert alert-info mb-8 text-xs">
    <strong>Nota de Instrução:</strong> O grau recomendado é uma diretriz de mentoria para apoiar a formação ritual dos irmãos nas suas respectivas trilhas de estudo, não constituindo limitação sistêmica ou restrição de leitura.
</div>

<!-- FILTROS E BUSCA DO ACERVO -->
<div class="card depth-1 p-6 mb-8">
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <!-- Abas de Filtros de Grau -->
        <div class="flex flex-wrap gap-2 w-full md:w-auto" id="filterTabs">
            <button onclick="filterAcervo('all')" id="tab-all" class="btn-tab-acervo active text-xs font-bold py-1.5 px-4 rounded-xl transition">
                Todos
            </button>
            <button onclick="filterAcervo('aprendiz')" id="tab-aprendiz" class="btn-tab-acervo text-xs font-bold py-1.5 px-4 rounded-xl transition">
                Aprendiz
            </button>
            <button onclick="filterAcervo('companheiro')" id="tab-companheiro" class="btn-tab-acervo text-xs font-bold py-1.5 px-4 rounded-xl transition">
                Companheiro
            </button>
            <button onclick="filterAcervo('mestre')" id="tab-mestre" class="btn-tab-acervo text-xs font-bold py-1.5 px-4 rounded-xl transition">
                Mestre
            </button>
            <button onclick="filterAcervo('livre')" id="tab-livre" class="btn-tab-acervo text-xs font-bold py-1.5 px-4 rounded-xl transition">
                Livre / Geral
            </button>
        </div>

        <!-- Barra de Busca -->
        <div class="relative w-full md:w-80">
            <input type="text" id="acervoSearch" oninput="searchAcervo()" placeholder="Buscar por título, autor, código..." class="form-input w-full pl-9 py-2 text-xs">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>
</div>

<!-- ACERVO DIGITAL EM GRID EDITORIAL -->
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6" id="livrosGrid">
    <?php if (empty($lista)): ?>
        <div class="col-span-full py-16 text-center text-slate-400">
            <p class="text-lg">Nenhum título cadastrado no acervo.</p>
        </div>
    <?php else: ?>
        <?php foreach ($lista as $item): 
            $grauRecomendado = strtolower(trim((string) ($item['grau_recomendado'] ?? 'livre')));
            if ($grauRecomendado === '') { $grauRecomendado = 'livre'; }
            $isDisponivel = (bool) ($item['disponivel'] ?? false);
            $detalhesHref = '/biblioteca/detalhes?id=' . (int) ($item['id'] ?? 0) . ((($catalogScope ?? 'minha') === 'rede') ? '&loja_id=' . (int) ($item['loja_id'] ?? 0) : '');
        ?>
            <!-- CARD DO LIVRO -->
            <div class="card-book flex flex-col justify-between" 
                 data-titulo="<?= htmlspecialchars(strtolower((string) ($item['titulo'] ?? ''))) ?>"
                 data-autor="<?= htmlspecialchars(strtolower((string) ($item['autor'] ?? ''))) ?>"
                 data-codigo="<?= htmlspecialchars(strtolower((string) ($item['codigo_acervo'] ?? ''))) ?>"
                 data-grau="<?= $grauRecomendado ?>">
                 
                <!-- Corpo do Card -->
                <div class="relative group">
                    <!-- Capa -->
                    <div class="aspect-[3/4] w-full rounded-xl overflow-hidden bg-black/35 border border-white/5 group-hover:border-white/10 transition relative">
                        <?php if (!empty($item['capa_url'])): ?>
                            <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa de <?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?>" class="h-full w-full object-cover">
                        <?php else: ?>
                            <div class="h-full w-full flex flex-col items-center justify-center text-center p-4 bg-gradient-to-br from-slate-900 to-black">
                                <svg class="w-8 h-8 text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                <span class="text-[10px] text-slate-500 uppercase font-black tracking-wider leading-tight"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Badges de Disponibilidade e Grau sobrepostos -->
                        <div class="absolute top-2 left-2 flex flex-col gap-1.5 z-10">
                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider bg-black/75 border border-white/10 <?= $isDisponivel ? 'text-emerald-400' : 'text-red-400' ?>">
                                <?= $isDisponivel ? 'Disponível' : 'Indisponível' ?>
                            </span>
                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider bg-black/75 border border-white/10 text-erp-gold">
                                <?= $formatGrau($item['grau_recomendado'] ?? '') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Informações do Título -->
                    <div class="mt-3">
                        <h3 class="font-bold text-white text-sm line-clamp-2 leading-tight group-hover:text-erp-gold transition"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></h3>
                        <p class="text-xs text-slate-400 mt-1 truncate"><?= htmlspecialchars((string) ($item['autor'] ?? 'Autor não informado')) ?></p>
                        <p class="text-[9px] text-slate-500 font-mono mt-1">Cód: <?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '-')) ?></p>
                    </div>
                </div>

                <!-- Botões de Ação do Livro -->
                <div class="mt-4 space-y-1.5">
                    <a href="<?= htmlspecialchars($detalhesHref) ?>" class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full text-center py-2 text-xs font-semibold block rounded-xl transition">
                        Ver Detalhes
                    </a>
                    
                    <?php if ($podeGerenciar || $podeClassificar): ?>
                        <div class="flex gap-1.5">
                            <?php if ($podeGerenciar): ?>
                                <a href="/biblioteca/editar?id=<?= (int) ($item['id'] ?? 0) ?>" class="btn border border-white/10 text-slate-400 hover:bg-white/5 py-1 flex-grow text-center text-[10px] font-semibold block rounded-lg transition">
                                    Editar
                                </a>
                            <?php endif; ?>
                            <?php if ($podeClassificar): ?>
                                <button onclick="abrirModalClassificacao(<?= (int) ($item['id'] ?? 0) ?>, '<?= addslashes((string) ($item['titulo'] ?? '')) ?>', '<?= addslashes((string) ($item['grau_recomendado'] ?? 'Livre')) ?>', '<?= addslashes((string) ($item['nota_instrucao'] ?? '')) ?>')" class="btn border border-purple-500/20 text-purple-400 hover:bg-purple-500/5 py-1 flex-grow text-center text-[10px] font-semibold block rounded-lg transition">
                                    Classificar
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal de Classificação -->
<div id="modalClassificacao" class="modal-container hidden">
    <div class="modal-content !bg-slate-900 border border-white/5 rounded-2xl max-w-lg w-full p-6">
        <form action="/biblioteca/classificar" method="POST">
            <div class="modal-header border-b border-white/5 pb-3 mb-4">
                <h3 class="modal-title text-white text-lg font-bold">Definir Grau Recomendado</h3>
                <p id="modal-livro-titulo" class="text-xs text-slate-400 mt-1"></p>
            </div>
            <div class="modal-body space-y-4">
                <input type="hidden" name="livro_id" id="modal-livro-id">
                <div>
                    <label for="modal-grau" class="form-label">Grau Recomendado</label>
                    <select name="grau_recomendado" id="modal-grau" class="form-select w-full">
                        <option value="Livre">Livre / Geral</option>
                        <option value="Aprendiz">Aprendiz</option>
                        <option value="Companheiro">Companheiro</option>
                        <option value="Mestre">Mestre</option>
                    </select>
                </div>
                <div>
                    <label for="modal-nota" class="form-label">Nota de Formação / Orientação</label>
                    <textarea name="nota_instrucao" id="modal-nota" rows="3" class="form-textarea w-full" placeholder="Descreva os temas rituais ou orientações para a leitura deste livro..."></textarea>
                </div>
            </div>
            <div class="modal-footer flex justify-end gap-3 pt-4 border-t border-white/5 mt-4">
                <button type="button" onclick="fecharModal()" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-xs font-semibold rounded-xl">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary py-2 px-6 text-xs font-bold rounded-xl">
                    Salvar Recomendação
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lógica de Filtros e Busca no Cliente -->
<style>
    .btn-tab-acervo {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        color: #94a3b8;
    }
    .btn-tab-acervo.active {
        background: #1d4ed8;
        border-color: #3b82f6;
        color: #ffffff;
    }
</style>

<script>
    let activeFilter = 'all';

    function filterAcervo(grau) {
        activeFilter = grau;
        
        // Atualiza botões
        document.querySelectorAll('#filterTabs button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById('tab-' + grau).classList.add('active');

        applyFilters();
    }

    function searchAcervo() {
        applyFilters();
    }

    function applyFilters() {
        const query = document.getElementById('acervoSearch').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.card-book');

        cards.forEach(card => {
            const cardGrau = card.getAttribute('data-grau');
            const titulo = card.getAttribute('data-titulo');
            const autor = card.getAttribute('data-autor');
            const codigo = card.getAttribute('data-codigo');

            // Filtro de Grau
            const matchesGrau = (activeFilter === 'all') || (cardGrau === activeFilter);
            
            // Filtro de Texto
            const matchesQuery = !query || 
                titulo.includes(query) || 
                autor.includes(query) || 
                codigo.includes(query);

            if (matchesGrau && matchesQuery) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Modal
    const modal = document.getElementById('modalClassificacao');
    function abrirModalClassificacao(id, titulo, grauAtual, notaAtual) {
        document.getElementById('modal-livro-id').value = id;
        document.getElementById('modal-livro-titulo').innerText = titulo;
        document.getElementById('modal-grau').value = grauAtual || 'Livre';
        document.getElementById('modal-nota').value = notaAtual || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function fecharModal() {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
</script>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
