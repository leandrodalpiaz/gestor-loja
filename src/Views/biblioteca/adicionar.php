<?php
declare(strict_types=1);

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Adicionar Novo Título';
$appShellDescription = 'Cadastre um novo item no acervo da Loja, com busca automática de capa e resumo por ISBN.';
$appShellActiveHref = '/biblioteca';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<div class="card">
    <form action="/biblioteca/adicionar" method="POST" class="card-body space-y-6">
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

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>



