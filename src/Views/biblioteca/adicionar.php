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

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-body { @apply p-6; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-input, .form-select, .form-textarea { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }
    
    .btn { @apply px-4 py-2 rounded-md text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-900; }
    .btn-primary { @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500; }
    .btn-secondary { @apply bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500; }
</style>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


