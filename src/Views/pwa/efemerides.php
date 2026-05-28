<?php
declare(strict_types=1);

$registros       = is_array($registros ?? null) ? $registros : [];
$registroEditar  = is_array($registroEditar ?? null) ? $registroEditar : null;
$tipos           = is_array($tipos ?? null) ? $tipos : [];
$vinculos        = is_array($vinculos ?? null) ? $vinculos : [];
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro    = $mensagemErro ?? null;

$pwaPageTitle      = 'Efemérides';
$pwaShowBackButton = true;
$pwaBackUrl        = '/pwa/chancelaria';
$pwaActiveTab      = 'cargo';

ob_start();
?>

<div class="px-4 py-4 space-y-4">

    <!-- Alertas -->
    <?php if ($mensagemSucesso): ?>
        <div class="pwa-alert-success"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="pwa-hero">
        <p class="pwa-eyebrow">Chancelaria</p>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-white">Efemérides da Loja</h2>
        <p class="pwa-muted mt-1.5 text-xs">Gerencie aniversários, datas maçônicas, família e fatos históricos.</p>
    </div>

    <!-- Formulário novo/editar -->
    <div class="pwa-card space-y-3.5">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2 select-none">
            <span class="w-6 h-6 rounded-md bg-amber-500/10 border border-amber-500/20 inline-flex items-center justify-center font-bold text-amber-500">
                <?= $registroEditar ? '✏' : '+' ?>
            </span>
            <?= $registroEditar ? 'Editar efeméride' : 'Nova efeméride' ?>
        </h3>

        <form method="post" action="/pwa/chancelaria/efemerides/salvar" class="space-y-3">
            <input type="hidden" name="id" value="<?= (int) ($registroEditar['id'] ?? 0) ?>">

            <div>
                <label class="pwa-label">Nome / Título *</label>
                <input name="nome" required
                       value="<?= htmlspecialchars((string) ($registroEditar['nome'] ?? '')) ?>"
                       class="pwa-input"
                       placeholder="Ex: João da Silva">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="pwa-label">Tipo *</label>
                    <select name="tipo" required class="pwa-select">
                        <?php foreach ($tipos as $valor => $label): ?>
                            <option value="<?= htmlspecialchars((string) $valor) ?>"
                                <?= (string) ($registroEditar['tipo'] ?? '') === (string) $valor ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="pwa-label">Data *</label>
                    <input type="date" name="data_evento" required
                           value="<?= htmlspecialchars((string) ($registroEditar['data_evento'] ?? '')) ?>"
                           class="pwa-input">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="pwa-label">Vínculo</label>
                    <input name="vinculo"
                           value="<?= htmlspecialchars((string) ($registroEditar['vinculo'] ?? '')) ?>"
                           list="vinculos-efemeride"
                           class="pwa-input"
                           placeholder="Nome do Irmão">
                    <datalist id="vinculos-efemeride">
                        <?php foreach ($vinculos as $vinculo): ?>
                            <option value="<?= htmlspecialchars((string) ($vinculo['nome'] ?? '')) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label class="pwa-label">Parentesco</label>
                    <input name="parentesco"
                           value="<?= htmlspecialchars((string) ($registroEditar['parentesco'] ?? '')) ?>"
                           class="pwa-input"
                           placeholder="Ex: Esposa">
                </div>
            </div>

            <div>
                <label class="pwa-label">Local / Oriente</label>
                <input name="local"
                       value="<?= htmlspecialchars((string) ($registroEditar['local'] ?? '')) ?>"
                       class="pwa-input"
                       placeholder="Ex: Curitiba - PR">
            </div>

            <div>
                <label class="pwa-label">Mensagem personalizada</label>
                <textarea name="mensagem_custom" rows="3" class="pwa-textarea"
                          placeholder="Texto a ser usado no card da efeméride..."><?= htmlspecialchars((string) ($registroEditar['mensagem_custom'] ?? '')) ?></textarea>
            </div>

            <button type="submit" class="pwa-btn-primary mt-2 select-none">
                <?= $registroEditar ? 'Salvar alterações' : 'Registrar efeméride' ?>
            </button>

            <?php if ($registroEditar): ?>
                <a href="/pwa/chancelaria/efemerides" class="pwa-btn-secondary text-xs select-none">
                    Cancelar edição
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Filtro de busca -->
    <form method="get" action="/pwa/chancelaria/efemerides" class="flex gap-2 select-none">
        <input name="q"
               value="<?= htmlspecialchars((string) ($_GET['q'] ?? '')) ?>"
               class="pwa-input flex-1"
               placeholder="Buscar efemérides...">
        <button type="submit" class="pwa-btn-primary py-0 w-auto px-4 text-xs">
            Filtrar
        </button>
    </form>

    <!-- Lista de registros -->
    <div class="space-y-3 pb-4">
        <div class="flex items-center gap-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Registros Cadastrados
            </p>
            <div class="flex-1 h-[1px] bg-white/5"></div>
        </div>

        <?php if (empty($registros)): ?>
            <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-2xl border border-dashed border-white/10 select-none">
                <div class="text-xl mb-1.5 select-none">📅</div>
                <p>Nenhuma efeméride encontrada.</p>
            </div>
        <?php else: ?>
            <?php foreach ($registros as $registro): ?>
                <?php $ativo = (bool) ($registro['ativo'] ?? false); ?>
                <div class="pwa-card border border-white/5 flex flex-col gap-3">
                    <!-- Cabeçalho do card -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-xs font-bold text-slate-200 truncate">
                                <?= htmlspecialchars((string) ($registro['nome'] ?? 'Efeméride')) ?>
                            </h3>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                <?= htmlspecialchars((string) ($registro['tipo'] ?? '')) ?>
                                <?php if (!empty($registro['data_evento'])): ?>
                                    · <?= htmlspecialchars((string) $registro['data_evento']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="pwa-badge <?= $ativo ? 'pwa-badge-success' : 'pwa-badge-muted' ?> shrink-0 select-none">
                            <?= $ativo ? 'Ativa' : 'Inativa' ?>
                        </span>
                    </div>

                    <!-- Mensagem custom (se houver) -->
                    <?php if (!empty($registro['mensagem_custom'])): ?>
                        <p class="text-[11px] text-slate-400 bg-slate-950 border border-white/5 rounded-lg p-2.5 leading-relaxed">
                            <?= nl2br(htmlspecialchars((string) $registro['mensagem_custom'])) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Ações -->
                    <div class="grid grid-cols-3 gap-2 mt-1 select-none">
                        <a href="/pwa/chancelaria/efemerides?editar=<?= (int) ($registro['id'] ?? 0) ?>"
                           class="pwa-btn-secondary py-1.5 px-1 text-center font-bold text-[10px] truncate bg-amber-500/10 border-amber-500/20 text-amber-400 active:scale-95 transition-transform">
                            Editar
                        </a>
                        <form method="post" action="/pwa/chancelaria/efemerides/desativar" class="w-full">
                            <input type="hidden" name="id" value="<?= (int) ($registro['id'] ?? 0) ?>">
                            <button class="pwa-btn-secondary py-1.5 px-1 font-bold text-[10px] truncate w-full select-none">
                                <?= $ativo ? 'Desativar' : 'Reativar' ?>
                            </button>
                        </form>
                        <form method="post" action="/pwa/chancelaria/efemerides/excluir"
                              onsubmit="return confirm('Excluir esta efeméride permanentemente?')" class="w-full">
                            <input type="hidden" name="id" value="<?= (int) ($registro['id'] ?? 0) ?>">
                            <button class="pwa-btn-secondary py-1.5 px-1 font-bold text-[10px] truncate w-full select-none border-red-500/20 bg-red-500/10 hover:bg-red-500/20 text-red-300">
                                Excluir
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
