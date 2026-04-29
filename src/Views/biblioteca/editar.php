<?php
declare(strict_types=1);

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Editar Título';
$appShellDescription = 'Atualize as informações de um item existente no acervo da Loja.';
$appShellActiveHref = '/biblioteca';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="card">
    <form action="/biblioteca/editar" method="POST" class="card-body space-y-6">
        <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coluna 1 -->
            <div class="space-y-6">
                <div>
                    <label for="titulo" class="form-label">Título *</label>
                    <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?>" class="form-input">
                </div>
                <div>
                    <label for="autor" class="form-label">Autor *</label>
                    <input type="text" id="autor" name="autor" required value="<?= htmlspecialchars((string) ($item['autor'] ?? '')) ?>" class="form-input">
                </div>
                <div>
                    <label for="isbn" class="form-label">ISBN</label>
                    <input type="text" id="isbn" name="isbn" value="<?= htmlspecialchars((string) ($item['isbn'] ?? '')) ?>" class="form-input">
                </div>
                <div>
                    <label for="capa_url" class="form-label">URL da Capa</label>
                    <input type="url" id="capa_url" name="capa_url" value="<?= htmlspecialchars((string) ($item['capa_url'] ?? '')) ?>" class="form-input">
                </div>
            </div>

            <!-- Coluna 2 -->
            <div class="space-y-6">
                <div>
                    <label for="tipo" class="form-label">Tipo de Item *</label>
                    <select id="tipo" name="tipo" required class="form-select">
                        <?php $tipo = (string) ($item['tipo'] ?? 'Livro Fisico'); ?>
                        <option value="Livro Fisico" <?= $tipo === 'Livro Fisico' ? 'selected' : '' ?>>Livro Físico</option>
                        <option value="Digital (PDF)" <?= $tipo === 'Digital (PDF)' ? 'selected' : '' ?>>Digital (PDF)</option>
                        <option value="Ritual" <?= $tipo === 'Ritual' ? 'selected' : '' ?>>Ritual</option>
                    </select>
                </div>
                <div>
                    <label for="quantidade_disponivel" class="form-label">Quantidade Disponível *</label>
                    <input type="number" id="quantidade_disponivel" name="quantidade_disponivel" min="0" value="<?= (int) ($item['quantidade_disponivel'] ?? 0) ?>" required class="form-input">
                </div>
                <div>
                    <label for="grau_recomendado" class="form-label">Grau Recomendado</label>
                    <select id="grau_recomendado" name="grau_recomendado" class="form-select">
                        <?php $grau = (string) ($item['grau_recomendado'] ?? 'Livre'); ?>
                        <option value="Livre" <?= $grau === 'Livre' ? 'selected' : '' ?>>Livre</option>
                        <option value="Aprendiz" <?= $grau === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                        <option value="Companheiro" <?= $grau === 'Companheiro' ? 'selected' : '' ?>>Companheiro</option>
                        <option value="Mestre" <?= $grau === 'Mestre' ? 'selected' : '' ?>>Mestre</option>
                    </select>
                </div>
                <div>
                    <label for="nota_instrucao" class="form-label">Nota de Instrução</label>
                    <input type="text" id="nota_instrucao" name="nota_instrucao" value="<?= htmlspecialchars((string) ($item['nota_instrucao'] ?? '')) ?>" class="form-input">
                </div>
            </div>
        </div>

        <!-- Campos de Texto Grandes -->
        <div>
            <label for="resumo" class="form-label">Resumo</label>
            <textarea id="resumo" name="resumo" rows="4" class="form-textarea"><?= htmlspecialchars((string) ($item['resumo'] ?? '')) ?></textarea>
        </div>

        <!-- Ações do Formulário -->
        <div class="pt-4 flex justify-end gap-3">
            <a href="/biblioteca" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
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

