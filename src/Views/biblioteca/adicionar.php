<?php
declare(strict_types=1);

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Adicionar Novo Título';
$appShellDescription = 'Cadastre um novo item no acervo da Loja, com busca automática de capa e resumo por ISBN.';
$appShellActiveHref = '/biblioteca';
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<div class="card">
    <form action="/biblioteca/adicionar" method="POST" class="card-body space-y-6">
        <div class="rounded-xl border border-erp-border bg-erp-surface-2 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                <div>
                    <label for="isbn_lookup" class="form-label">Cadastrar por ISBN</label>
                    <input type="text" id="isbn_lookup" class="form-input" placeholder="Digite ou cole o ISBN para preencher os campos">
                </div>
                <button type="button" id="btnBuscarIsbn" class="btn btn-secondary">Buscar ISBN</button>
            </div>
            <p id="isbn_status" class="mt-2 text-xs text-gray-500">A busca preenche título, autor, capa e resumo quando houver dados disponíveis.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coluna 1 -->
            <div class="space-y-6">
                <div>
                    <label for="titulo" class="form-label">Título *</label>
                    <input type="text" id="titulo" name="titulo" required class="form-input">
                </div>
                <div>
                    <label for="autor" class="form-label">Autor *</label>
                    <input type="text" id="autor" name="autor" required class="form-input">
                </div>
                <div>
                    <label for="isbn" class="form-label">ISBN</label>
                    <input type="text" id="isbn" name="isbn" class="form-input" placeholder="Opcional, para buscar dados">
                </div>
                <div>
                    <label for="capa_url" class="form-label">URL da Capa</label>
                    <input type="url" id="capa_url" name="capa_url" class="form-input" placeholder="Opcional, se não usar ISBN">
                </div>
            </div>

            <!-- Coluna 2 -->
            <div class="space-y-6">
                <div>
                    <label for="tipo" class="form-label">Tipo de Item *</label>
                    <select id="tipo" name="tipo" required class="form-select">
                        <option value="Livro Fisico">Livro Físico</option>
                        <option value="Digital (PDF)">Digital (PDF)</option>
                        <option value="Ritual">Ritual</option>
                    </select>
                </div>
                <div>
                    <label for="quantidade_disponivel" class="form-label">Quantidade Disponível *</label>
                    <input type="number" id="quantidade_disponivel" name="quantidade_disponivel" min="1" value="1" required class="form-input">
                </div>
                <div>
                    <label for="grau_recomendado" class="form-label">Grau Recomendado</label>
                    <select id="grau_recomendado" name="grau_recomendado" class="form-select">
                        <option value="Livre">Livre</option>
                        <option value="Aprendiz">Aprendiz</option>
                        <option value="Companheiro">Companheiro</option>
                        <option value="Mestre">Mestre</option>
                    </select>
                </div>
                <div>
                    <label for="nota_instrucao" class="form-label">Nota de Instrução</label>
                    <input type="text" id="nota_instrucao" name="nota_instrucao" class="form-input" placeholder="Ex: Leitura obrigatória para o Grau 2">
                </div>
            </div>
        </div>

        <!-- Campos de Texto Grandes -->
        <div class="space-y-6">
            <div>
                <label for="resumo" class="form-label">Resumo</label>
                <textarea id="resumo" name="resumo" rows="4" class="form-textarea" placeholder="Opcional, pode ser preenchido via ISBN"></textarea>
            </div>
        </div>

        <!-- Ações do Formulário -->
        <div class="pt-4 flex justify-end gap-3">
            <a href="/biblioteca" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Título</button>
        </div>
    </form>
</div>

<div class="card mt-6">
    <div class="card-header">
        <h2 class="card-title">Importar acervo por CSV</h2>
        <p class="card-subtitle">Use colunas como titulo, autor, isbn, quantidade, grau_recomendado, resumo e observacao.</p>
    </div>
    <form action="/biblioteca/importar" method="POST" enctype="multipart/form-data" class="card-body space-y-4">
        <input type="file" name="arquivo_acervo" accept=".csv,text/csv" class="form-input" required>
        <div class="alert alert-info text-xs">Para planilhas Excel ou Google Sheets, exporte como CSV antes de importar nesta primeira versão.</div>
        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary">Importar CSV</button>
        </div>
    </form>
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
            return;
        }
        status.textContent = 'Buscando dados do ISBN...';
        fetch('/biblioteca/isbn?isbn=' + encodeURIComponent(isbn), {headers: {'Accept': 'application/json'}})
            .then(function (response) { return response.json().then(function (body) { return {ok: response.ok, body: body}; }); })
            .then(function (result) {
                if (!result.ok || !result.body.ok) {
                    status.textContent = result.body.erro || 'ISBN não encontrado.';
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
                status.textContent = 'Dados encontrados. Revise antes de salvar.';
            })
            .catch(function () {
                status.textContent = 'Não foi possível consultar o ISBN agora.';
            });
    });
});
</script>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>



