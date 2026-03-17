<?php
// src/Views/biblioteca/index.php
include __DIR__ . '/../header.php';
?>
<div class="container mt-4">
    <h2>Catálogo da Biblioteca</h2>
    <div class="mb-3">
        <a href="/biblioteca/form" class="btn btn-primary">Adicionar Novo Título</a>
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Autor</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($acervo)): ?>
            <?php foreach ($acervo as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['id']) ?></td>
                    <td><?= htmlspecialchars($item['titulo']) ?></td>
                    <td><?= htmlspecialchars($item['autor']) ?></td>
                    <td><?= htmlspecialchars($item['categoria'] ?? '') ?></td>
                    <td><?= ucfirst(htmlspecialchars($item['tipo'])) ?></td>
                    <td><?= htmlspecialchars($item['status'] ?? 'Disponível') ?></td>
                    <td>
                        <a href="/biblioteca/form?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <form action="/biblioteca/excluir" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este título?');">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">Nenhum título cadastrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
