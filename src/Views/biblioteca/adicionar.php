<?php
declare(strict_types=1);

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Adicionar Novo Título';
$appShellDescription = 'Cadastre um novo livro ou documento no acervo da Loja. Use a busca por ISBN para preencher automaticamente os dados.';
$appShellActiveHref = '/biblioteca';
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Formulário Principal (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Cadastro Rápido por ISBN -->
        <div class="card depth-1 p-6 border border-warning/20 bg-warning/5 text-warning">
            <div class="card-header border-b border-warning/10 pb-3 mb-4">
                <h2 class="card-title text-warning flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h.01M16 20h2M4 4h16v16H4V4z"></path></svg>
                    Busca Automática por ISBN
                </h2>
                <p class="card-subtitle text-warning/80 mt-1">Preencha título, autor, capa e resumo instantaneamente informando o ISBN.</p>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 items-end">
                    <div class="w-full">
                        <label for="isbn_lookup" class="form-label text-warning/80">Código ISBN</label>
                        <input type="text" id="isbn_lookup" class="form-input w-full bg-black/35 border-warning/20 text-white placeholder-warning/40" placeholder="Ex: 9788572420311">
                    </div>
                    <button type="button" id="btnBuscarIsbn" class="btn border border-warning/35 text-warning hover:bg-warning/15 py-2.5 px-6 font-bold text-xs uppercase tracking-wider h-10">
                        Consultar ISBN
                    </button>
                </div>
                <p id="isbn_status" class="mt-2.5 text-xs text-slate-400">Pronto para buscar.</p>
            </div>
        </div>

        <!-- Formulário de Detalhes -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-6">
                <h2 class="card-title text-white">Dados do Livro</h2>
                <p class="card-subtitle mt-0.5">Campos marcados com * são obrigatórios.</p>
            </div>
            
            <form action="/biblioteca/adicionar" method="POST" class="space-y-6">
                <?= \App\Core\Http\WebGuards::csrfField() ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Coluna 1 -->
                    <div class="space-y-4">
                        <div>
                            <label for="titulo" class="form-label">Título *</label>
                            <input type="text" id="titulo" name="titulo" required class="form-input w-full" placeholder="Título completo do livro">
                        </div>
                        <div>
                            <label for="autor" class="form-label">Autor *</label>
                            <input type="text" id="autor" name="autor" required class="form-input w-full" placeholder="Nome do autor da obra">
                        </div>
                        <div>
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" id="isbn" name="isbn" class="form-input w-full" placeholder="ISBN de referência">
                        </div>
                        <div>
                            <label for="capa_url" class="form-label">URL da Capa (Imagem)</label>
                            <input type="url" id="capa_url" name="capa_url" class="form-input w-full" placeholder="https://exemplo.com/capa.jpg">
                        </div>
                    </div>

                    <!-- Coluna 2 -->
                    <div class="space-y-4">
                        <div>
                            <label for="tipo" class="form-label">Tipo de Item *</label>
                            <select id="tipo" name="tipo" required class="form-select w-full">
                                <option value="Livro Fisico">Livro Físico</option>
                                <option value="Digital (PDF)">Digital (PDF)</option>
                                <option value="Ritual">Ritual / Livro de Loja</option>
                            </select>
                        </div>
                        <div>
                            <label for="quantidade_disponivel" class="form-label">Quantidade de Exemplares *</label>
                            <input type="number" id="quantidade_disponivel" name="quantidade_disponivel" min="1" value="1" required class="form-input w-full">
                        </div>
                        <div>
                            <label for="grau_recomendado" class="form-label">Grau Recomendado</label>
                            <select id="grau_recomendado" name="grau_recomendado" class="form-select w-full">
                                <option value="Livre">Livre / Geral</option>
                                <option value="Aprendiz">Aprendiz</option>
                                <option value="Companheiro">Companheiro</option>
                                <option value="Mestre">Mestre</option>
                            </select>
                        </div>
                        <div>
                            <label for="nota_instrucao" class="form-label">Nota de Instrução (Vigilantes)</label>
                            <input type="text" id="nota_instrucao" name="nota_instrucao" class="form-input w-full" placeholder="Ex: Recomendado para o Grau de Companheiro.">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="resumo" class="form-label">Resumo / Sinopse</label>
                    <textarea id="resumo" name="resumo" rows="4" class="form-textarea w-full" placeholder="Breve resumo da obra para orientação dos obreiros..."></textarea>
                </div>

                <div class="pt-4 border-t border-white/5 flex justify-end gap-3">
                    <a href="/biblioteca" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2.5 px-6 text-xs font-semibold rounded-xl">Cancelar</a>
                    <button type="submit" class="btn btn-primary py-2.5 px-6 text-xs font-bold rounded-xl">Salvar Título</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        <!-- Importar via CSV -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Importação em Lote (CSV)</h2>
                <p class="card-subtitle mt-0.5">Cadastre múltiplos livros de uma vez só.</p>
            </div>
            <form action="/biblioteca/importar" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= \App\Core\Http\WebGuards::csrfField() ?>
                <div>
                    <label class="form-label">Selecione o arquivo .csv</label>
                    <input type="file" name="arquivo_acervo" accept=".csv,text/csv" required class="form-input w-full text-xs">
                </div>
                <div class="alert alert-info text-[10px] leading-relaxed">
                    Certifique-se de exportar sua planilha no formato CSV (separado por vírgulas ou ponto e vírgula). Cabeçalho esperado: <em>titulo, autor, isbn, quantidade, grau_recomendado, resumo, observacao</em>.
                </div>
                <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full py-2.5 text-xs font-semibold rounded-xl">
                    Importar CSV
                </button>
            </form>
        </div>

        <!-- Instruções de Cadastro -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Ajuda ao Bibliotecário</h2>
            </div>
            <div class="card-body text-xs text-slate-400 space-y-3">
                <p>Boas práticas para organizar a biblioteca:</p>
                <ul class="list-disc pl-4 space-y-2">
                    <li>Sempre que possível utilize o ISBN. Ele garante os dados oficiais e o link da capa.</li>
                    <li>Para arquivos digitais (PDF), defina a quantidade disponível como alta e o tipo como <strong>Digital (PDF)</strong>.</li>
                    <li>Utilize a Nota de Instrução para sinalizar quais lições o Aprendiz ou Companheiro deve focar ao ler.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btnBuscarIsbn');
    var lookup = document.getElementById('isbn_lookup');
    var status = document.getElementById('isbn_status');
    if (!btn || !lookup || !status) return;

    btn.addEventListener('click', function () {
        var isbn = lookup.value.trim();
        if (!isbn) {
            status.textContent = 'Informe um ISBN para buscar.';
            status.className = 'mt-2.5 text-xs text-red-400';
            return;
        }
        status.textContent = 'Buscando dados do ISBN...';
        status.className = 'mt-2.5 text-xs text-yellow-400 font-semibold';
        
        fetch('/biblioteca/isbn?isbn=' + encodeURIComponent(isbn), {headers: {'Accept': 'application/json'}})
            .then(function (response) { return response.json().then(function (body) { return {ok: response.ok, body: body}; }); })
            .then(function (result) {
                if (!result.ok || !result.body.ok) {
                    status.textContent = result.body.erro || 'ISBN não encontrado no banco de dados literário.';
                    status.className = 'mt-2.5 text-xs text-red-400';
                    return;
                }
                var dados = result.body.dados || {};
                var campos = {
                    titulo: dados.titulo || '',
                    autor: dados.autor || '',
                    isbn: dados.isbn || isbn,
                    capa_url: dados.capa_url || '',
                    resumo: dados.resumo || ''
                };
                Object.keys(campos).forEach(function (id) {
                    var campo = document.getElementById(id);
                    if (campo && campos[id]) campo.value = campos[id];
                });
                status.textContent = 'Dados carregados com sucesso! Por favor, revise as informações.';
                status.className = 'mt-2.5 text-xs text-emerald-400 font-semibold';
            })
            .catch(function () {
                status.textContent = 'Não foi possível consultar os servidores do ISBN agora.';
                status.className = 'mt-2.5 text-xs text-red-400';
            });
    });
});
</script>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
