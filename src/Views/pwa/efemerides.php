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

<div class="pwa-premium-page">

    <!-- Alertas -->
    <?php if ($mensagemSucesso): ?>
        <div class="pwa-alert-success" style="margin-bottom:1rem;">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="pwa-alert-error" style="margin-bottom:1rem;">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="pwa-hero" style="padding:1.25rem;margin-bottom:1.25rem;">
        <p class="pwa-eyebrow">Chancelaria</p>
        <h2 style="font-size:1.375rem;font-weight:800;color:#f8fafc;margin:0.375rem 0 0;letter-spacing:-0.02em;">
            Efemérides da Loja
        </h2>
        <p class="pwa-muted" style="font-size:0.8125rem;margin:0.375rem 0 0;line-height:1.5;">
            Aniversários, datas maçônicas, família e fatos históricos.
        </p>
    </div>

    <!-- Formulário novo/editar -->
    <div class="pwa-card" style="padding:1.25rem;margin-bottom:1.25rem;">
        <h3 style="font-size:0.875rem;font-weight:700;color:#f1f5f9;margin:0 0 1rem;display:flex;align-items:center;gap:0.5rem;">
            <span style="
                width:26px;height:26px;border-radius:6px;
                background:rgba(201,162,39,0.18);border:1px solid rgba(201,162,39,0.3);
                display:inline-flex;align-items:center;justify-content:center;
                font-size:0.75rem;color:#C9A227;
            "><?= $registroEditar ? '✏' : '+' ?></span>
            <?= $registroEditar ? 'Editar efeméride' : 'Nova efeméride' ?>
        </h3>

        <form method="post" action="/pwa/chancelaria/efemerides/salvar"
              style="display:flex;flex-direction:column;gap:0.75rem;">
            <input type="hidden" name="id" value="<?= (int) ($registroEditar['id'] ?? 0) ?>">

            <div>
                <label class="pwa-label">Nome / Título *</label>
                <input name="nome" required
                       value="<?= htmlspecialchars((string) ($registroEditar['nome'] ?? '')) ?>"
                       class="pwa-input"
                       placeholder="Ex: João da Silva">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
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

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
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

            <button type="submit" class="pwa-btn-primary" style="margin-top:0.25rem;">
                <?= $registroEditar ? 'Salvar alterações' : 'Registrar efeméride' ?>
            </button>

            <?php if ($registroEditar): ?>
                <a href="/pwa/chancelaria/efemerides" class="pwa-btn-secondary" style="text-align:center;font-size:0.8125rem;">
                    Cancelar edição
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Filtro de busca -->
    <form method="get" action="/pwa/chancelaria/efemerides"
          style="display:flex;gap:0.5rem;margin-bottom:1.25rem;">
        <input name="q"
               value="<?= htmlspecialchars((string) ($_GET['q'] ?? '')) ?>"
               class="pwa-input"
               style="flex:1;"
               placeholder="Buscar efemérides...">
        <button type="submit" class="pwa-btn-primary" style="width:auto;padding:0 1rem;white-space:nowrap;">
            Filtrar
        </button>
    </form>

    <!-- Lista de registros -->
    <div style="display:flex;flex-direction:column;gap:0.625rem;">
        <?php if (empty($registros)): ?>
            <div style="
                border:1px solid rgba(255,255,255,0.08);
                background:rgba(255,255,255,0.03);
                border-radius:1.125rem;
                padding:2rem 1rem;
                text-align:center;
            ">
                <div style="font-size:2rem;margin-bottom:0.5rem;">📅</div>
                <p style="font-size:0.875rem;color:#94a3b8;margin:0;">Nenhuma efeméride encontrada.</p>
            </div>
        <?php else: ?>
        <?php foreach ($registros as $registro): ?>
        <?php $ativo = (bool) ($registro['ativo'] ?? false); ?>
        <div class="pwa-card" style="padding:1rem;">
            <!-- Cabeçalho do card -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.75rem;">
                <div style="min-width:0;flex:1;">
                    <h3 style="font-size:0.9375rem;font-weight:700;color:#f1f5f9;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars((string) ($registro['nome'] ?? 'Efeméride')) ?>
                    </h3>
                    <p style="font-size:0.75rem;color:#94a3b8;margin:0.2rem 0 0;">
                        <?= htmlspecialchars((string) ($registro['tipo'] ?? '')) ?>
                        <?php if (!empty($registro['data_evento'])): ?>
                            · <?= htmlspecialchars((string) $registro['data_evento']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="pwa-badge <?= $ativo ? 'pwa-badge-success' : 'pwa-badge-muted' ?>" style="flex-shrink:0;">
                    <?= $ativo ? 'Ativa' : 'Inativa' ?>
                </span>
            </div>

            <!-- Mensagem custom (se houver) -->
            <?php if (!empty($registro['mensagem_custom'])): ?>
                <p style="
                    font-size:0.8rem;color:#94a3b8;
                    background:rgba(255,255,255,0.04);
                    border:1px solid rgba(255,255,255,0.07);
                    border-radius:0.625rem;
                    padding:0.625rem 0.875rem;
                    margin:0 0 0.75rem;
                    line-height:1.5;
                ">
                    <?= nl2br(htmlspecialchars((string) $registro['mensagem_custom'])) ?>
                </p>
            <?php endif; ?>

            <!-- Ações -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.4rem;">
                <a href="/pwa/chancelaria/efemerides?editar=<?= (int) ($registro['id'] ?? 0) ?>"
                   style="
                       display:flex;align-items:center;justify-content:center;
                       padding:0.5rem 0.25rem;
                       background:rgba(201,162,39,0.15);
                       border:1px solid rgba(201,162,39,0.25);
                       border-radius:0.625rem;
                       font-size:0.72rem;font-weight:700;
                       color:#fde68a;text-decoration:none;
                   ">
                    Editar
                </a>
                <form method="post" action="/pwa/chancelaria/efemerides/desativar">
                    <input type="hidden" name="id" value="<?= (int) ($registro['id'] ?? 0) ?>">
                    <button style="
                        width:100%;
                        padding:0.5rem 0.25rem;
                        background:rgba(148,163,184,0.10);
                        border:1px solid rgba(148,163,184,0.2);
                        border-radius:0.625rem;
                        font-size:0.72rem;font-weight:700;
                        color:#94a3b8;cursor:pointer;font-family:inherit;
                    ">
                        <?= $ativo ? 'Desativar' : 'Reativar' ?>
                    </button>
                </form>
                <form method="post" action="/pwa/chancelaria/efemerides/excluir"
                      onsubmit="return confirm('Excluir esta efeméride permanentemente?')">
                    <input type="hidden" name="id" value="<?= (int) ($registro['id'] ?? 0) ?>">
                    <button style="
                        width:100%;
                        padding:0.5rem 0.25rem;
                        background:rgba(248,113,113,0.12);
                        border:1px solid rgba(248,113,113,0.25);
                        border-radius:0.625rem;
                        font-size:0.72rem;font-weight:700;
                        color:#fca5a5;cursor:pointer;font-family:inherit;
                    ">
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
