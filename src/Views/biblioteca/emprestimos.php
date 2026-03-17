<?php
// src/Views/biblioteca/emprestimos.php
include __DIR__ . '/../header.php';
?>
<div class="container mt-4">
    <h2>Controle de Empréstimos</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Livro</th>
                <th>Obreiro</th>
                <th>Data Empréstimo</th>
                <th>Previsão Devolução</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($emprestimos)): ?>
            <?php foreach ($emprestimos as $emp): ?>
                <tr>
                    <td><?= htmlspecialchars($emp['id']) ?></td>
                    <td><?= htmlspecialchars($emp['titulo'] ?? $emp['acervo_id']) ?></td>
                    <td><?= htmlspecialchars($emp['obreiro_nome'] ?? $emp['obreiro_id']) ?></td>
                    <td><?= htmlspecialchars($emp['data_emprestimo']) ?></td>
                    <td><?= htmlspecialchars($emp['data_devolucao_prevista']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($emp['status'])) ?></td>
                    <td>
                        <?php if ($emp['status'] === 'pendente'): ?>
                        <form action="/biblioteca/devolver" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">Registrar Devolução</button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">Nenhum empréstimo pendente ou atrasado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <a href="/biblioteca" class="btn btn-secondary">Voltar ao Catálogo</a>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
