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

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


