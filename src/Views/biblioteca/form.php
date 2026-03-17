<?php
// src/Views/biblioteca/form.php
include __DIR__ . '/../header.php';

$isEdit = isset($livro) || isset($item);
$dados = $livro ?? $item ?? [];
$action = $isEdit ? '/biblioteca/editar' : '/biblioteca/adicionar';
$title = $isEdit ? 'Editar Título' : 'Adicionar Novo Título';
?>
<div class="container mt-4">
    <h2><?= $title ?></h2>
    <form action="<?= $action ?>" method="POST">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($dados['id']) ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($dados['titulo'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="autor" class="form-label">Autor</label>
            <input type="text" class="form-control" id="autor" name="autor" value="<?= htmlspecialchars($dados['autor'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="categoria" class="form-label">Categoria</label>
            <input type="text" class="form-control" id="categoria" name="categoria" value="<?= htmlspecialchars($dados['categoria'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="tipo" class="form-label">Tipo</label>
            <select class="form-select" id="tipo" name="tipo" required>
                <option value="fisico" <?= (isset($dados['tipo']) && $dados['tipo'] === 'fisico') ? 'selected' : '' ?>>Físico</option>
                <option value="digital" <?= (isset($dados['tipo']) && $dados['tipo'] === 'digital') ? 'selected' : '' ?>>Digital</option>
                <option value="ritual" <?= (isset($dados['tipo']) && $dados['tipo'] === 'ritual') ? 'selected' : '' ?>>Ritual</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="arquivo_url" class="form-label">Link do Arquivo (PDF)</label>
            <input type="text" class="form-control" id="arquivo_url" name="arquivo_url" value="<?= htmlspecialchars($dados['arquivo_url'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="disponivel" <?= (isset($dados['status']) && $dados['status'] === 'disponivel') ? 'selected' : '' ?>>Disponível</option>
                <option value="emprestado" <?= (isset($dados['status']) && $dados['status'] === 'emprestado') ? 'selected' : '' ?>>Emprestado</option>
                <option value="indisponivel" <?= (isset($dados['status']) && $dados['status'] === 'indisponivel') ? 'selected' : '' ?>>Indisponível</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantidade_disponivel" class="form-label">Quantidade Disponível</label>
            <input type="number" class="form-control" id="quantidade_disponivel" name="quantidade_disponivel" min="0" value="<?= htmlspecialchars($dados['quantidade_disponivel'] ?? 1) ?>">
        </div>
        <button type="submit" class="btn btn-success">Salvar</button>
        <a href="/biblioteca" class="btn btn-secondary">Voltar</a>
    </form>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
