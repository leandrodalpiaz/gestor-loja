<?php
declare(strict_types=1);

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Editar Título';
$appShellDescription = 'Atualize os metadados, resumo, regras de mentoria e quantidade de exemplares do item selecionado.';
$appShellActiveHref = '/biblioteca';
$item = $item ?? [];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="card depth-1 p-6">
        <div class="card-header border-b border-white/5 pb-4 mb-6">
            <h2 class="card-title text-white">Editar Registro de Acervo</h2>
            <p class="card-subtitle mt-0.5">ID do Livro: <span class="font-mono text-white">#<?= (int) ($item['id'] ?? 0) ?></span> &middot; Código: <span class="font-mono text-white"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></span></p>
        </div>

        <form action="/biblioteca/editar" method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Coluna 1 -->
                <div class="space-y-4">
                    <div>
                        <label for="titulo" class="form-label text-slate-300">Título *</label>
                        <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?>" class="form-input w-full">
                    </div>
                    <div>
                        <label for="autor" class="form-label text-slate-300">Autor *</label>
                        <input type="text" id="autor" name="autor" required value="<?= htmlspecialchars((string) ($item['autor'] ?? '')) ?>" class="form-input w-full">
                    </div>
                    <div>
                        <label for="isbn" class="form-label text-slate-300">ISBN</label>
                        <input type="text" id="isbn" name="isbn" value="<?= htmlspecialchars((string) ($item['isbn'] ?? '')) ?>" class="form-input w-full">
                    </div>
                    <div>
                        <label for="capa_url" class="form-label text-slate-300">URL da Capa</label>
                        <input type="url" id="capa_url" name="capa_url" value="<?= htmlspecialchars((string) ($item['capa_url'] ?? '')) ?>" class="form-input w-full">
                    </div>
                </div>

                <!-- Coluna 2 -->
                <div class="space-y-4">
                    <div>
                        <label for="tipo" class="form-label text-slate-300">Tipo de Item *</label>
                        <select id="tipo" name="tipo" required class="form-select w-full">
                            <?php $tipo = (string) ($item['tipo'] ?? 'Livro Fisico'); ?>
                            <option value="Livro Fisico" <?= $tipo === 'Livro Fisico' ? 'selected' : '' ?>>Livro Físico</option>
                            <option value="Digital (PDF)" <?= $tipo === 'Digital (PDF)' ? 'selected' : '' ?>>Digital (PDF)</option>
                            <option value="Ritual" <?= $tipo === 'Ritual' ? 'selected' : '' ?>>Ritual</option>
                            <option value="Peca de Arquitetura" <?= $tipo === 'Peca de Arquitetura' ? 'selected' : '' ?>>Peça de Arquitetura</option>
                            <option value="Trabalho de Instrucao" <?= $tipo === 'Trabalho de Instrucao' ? 'selected' : '' ?>>Trabalho de Instrução</option>
                        </select>
                    </div>
                    <div>
                        <label for="arquivo_url" class="form-label text-slate-300">URL do Arquivo PDF (para Peças e Trabalhos)</label>
                        <input type="text" id="arquivo_url" name="arquivo_url" value="<?= htmlspecialchars((string) ($item['arquivo_url'] ?? '')) ?>" class="form-input w-full" placeholder="Ex: /assets/uploads/trabalhos/trabalho.pdf">
                    </div>
                    <div>
                        <label for="quantidade_disponivel" class="form-label text-slate-300">Quantidade Disponível *</label>
                        <input type="number" id="quantidade_disponivel" name="quantidade_disponivel" min="0" value="<?= (int) ($item['quantidade_disponivel'] ?? 0) ?>" required class="form-input w-full">
                    </div>
                    <div>
                        <label for="grau_recomendado" class="form-label text-slate-300">Grau Recomendado</label>
                        <select id="grau_recomendado" name="grau_recomendado" class="form-select w-full">
                            <?php $grau = (string) ($item['grau_recomendado'] ?? 'Livre'); ?>
                            <option value="Livre" <?= $grau === 'Livre' ? 'selected' : '' ?>>Livre / Geral</option>
                            <option value="Aprendiz" <?= $grau === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                            <option value="Companheiro" <?= $grau === 'Companheiro' ? 'selected' : '' ?>>Companheiro</option>
                            <option value="Mestre" <?= $grau === 'Mestre' ? 'selected' : '' ?>>Mestre</option>
                        </select>
                    </div>
                    <div>
                        <label for="nota_instrucao" class="form-label text-slate-300">Nota de Instrução</label>
                        <input type="text" id="nota_instrucao" name="nota_instrucao" value="<?= htmlspecialchars((string) ($item['nota_instrucao'] ?? '')) ?>" class="form-input w-full">
                    </div>
                </div>
            </div>

            <!-- Resumo -->
            <div>
                <label for="resumo" class="form-label text-slate-300">Resumo / Sinopse</label>
                <textarea id="resumo" name="resumo" rows="4" class="form-textarea w-full"><?= htmlspecialchars((string) ($item['resumo'] ?? '')) ?></textarea>
            </div>

            <!-- Ações -->
            <div class="pt-4 border-t border-white/5 flex justify-end gap-3">
                <a href="/biblioteca" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2.5 px-6 text-xs font-semibold rounded-xl">Cancelar</a>
                <button type="submit" class="btn btn-primary py-2.5 px-6 text-xs font-bold rounded-xl">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
