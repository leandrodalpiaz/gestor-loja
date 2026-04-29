<?php
declare(strict_types=1);

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E HELPERS
// #############################################################################

$focoTela = trim((string) ($focoEfemeride ?? ''));
$vinculosPadrao = is_array($vinculosPadrao ?? null) ? $vinculosPadrao : [];
$registrosRecentes = is_array($registrosRecentes ?? []) ? $registrosRecentes : [];
$tiposEfemeride = is_array($tiposEfemeride ?? []) ? $tiposEfemeride : [];
$obreirosFiltro = is_array($obreirosFiltro ?? []) ? $obreirosFiltro : [];

$filtroIrmaoRef = trim((string) ($filtroIrmaoRef ?? ''));
$filtroTermo = trim((string) ($filtroTermo ?? ''));
$filtroTipo = trim((string) ($filtroTipo ?? ''));
$filtroVinculo = trim((string) ($filtroVinculo ?? ''));
$filtroRegular = trim((string) ($filtroRegular ?? '1'));
$filtroDataIni = trim((string) ($filtroDataIni ?? ''));
$filtroDataFim = trim((string) ($filtroDataFim ?? ''));

$formatarDataVisual = static fn (?string $valor): string =>
    empty(trim((string) $valor)) ? '-' : (new DateTimeImmutable(trim((string) $valor)))->format('d/m/Y');

$previewRaw = (string) ($mensagemPreview ?? '');
$previewRender = nl2br(strip_tags($previewRaw, '<b><i><u><strong><em>'), false);

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Chancelaria';
$appShellTitle = 'EfemÃ©rides e Mensagem do Dia';
$appShellDescription = 'OperaÃ§Ã£o de mensagem diÃ¡ria, cadastro de eventos e manutenÃ§Ã£o de registros da Loja.';
$appShellActiveHref = '/chancelaria/efemerides';
$appShellActions = [
    ['label' => 'Voltar ao Painel', 'href' => '/dashboard'],
    ['label' => 'Emitir Certificado', 'href' => '/chancelaria/certificado', 'primary' => true],
];

require __DIR__ . '/partials/erp_shell_open.php';
?>

<?php if (!empty($sucessoMensagem)): ?>
<div class="alert alert-success mb-6"><?= htmlspecialchars($sucessoMensagem) ?></div>
<?php endif; ?>
<?php if (!empty($erroMensagem)): ?>
<div class="alert alert-danger mb-6"><?= htmlspecialchars($erroMensagem) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal: Mensagem e Registros -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Mensagem do Dia -->
        <div class="card" id="secao-mensagem">
            <div class="card-header">
                <h2 class="card-title">Revisar Mensagem do Dia</h2>
                <p class="card-subtitle">Esta ediÃ§Ã£o altera somente a mensagem de hoje. Os registros oficiais nÃ£o sÃ£o modificados aqui.</p>
            </div>
            <div class="card-body">
                <div class="telegram-format mb-4 p-4 rounded-lg bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                    <?= $previewRender ?>
                </div>
                <form method="POST" action="/chancelaria/efemerides/salvar-previa">
                    <textarea name="mensagem_preview" class="form-input h-60" placeholder="A mensagem gerada aparecerÃ¡ aqui para revisÃ£o..."><?= htmlspecialchars($previewRaw) ?></textarea>
                    <p class="form-hint">MantÃ©m tags HTML do Telegram (ex: &lt;b&gt; e &lt;i&gt;).</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="btn btn-primary">Salvar Mensagem</button>
                        <button type="button" onclick="copiarPreview('<?= htmlspecialchars($previewRaw, ENT_QUOTES) ?>')" class="btn btn-secondary">Copiar Texto</button>
                    </div>
                </form>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-3">
                    <form method="POST" action="/chancelaria/efemerides/enviar-previa" onsubmit="return confirm('Enviar a prÃ©via para o Telegram privado do chanceler?');">
                        <button type="submit" class="btn btn-secondary bg-indigo-600 text-white hover:bg-indigo-700">Enviar PrÃ©via no Privado</button>
                    </form>
                    <form method="POST" action="/chancelaria/efemerides/enviar-grupo" onsubmit="return confirm('Confirmar envio da mensagem no grupo oficial?');">
                        <button type="submit" class="btn btn-primary bg-green-600 hover:bg-green-700">Enviar no Grupo Oficial</button>
                    </form>
                </div>
                <p class="form-hint mt-2">SugestÃ£o: revise, envie no privado para conferÃªncia e, sÃ³ entÃ£o, publique no grupo.</p>
            </div>
        </div>

        <!-- Lista de Registros -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Registros de EfemÃ©rides (<?= count($registrosRecentes) ?>)</h2>
                <p class="card-subtitle">Consulte e gerencie os registros que alimentam as mensagens automÃ¡ticas.</p>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="/chancelaria/efemerides" class="space-y-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="filtro-termo" class="form-label">Pesquisar</label>
                            <input id="filtro-termo" type="text" name="termo" value="<?= htmlspecialchars($filtroTermo) ?>" class="form-input" placeholder="Nome, vÃ­nculo...">
                        </div>
                        <div>
                            <label for="filtro-irmao" class="form-label">IrmÃ£o</label>
                            <select id="filtro-irmao" name="irmao_ref" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($obreirosFiltro as $obreiro): ?>
                                    <option value="<?= htmlspecialchars($obreiro['nome']) ?>" <?= $filtroIrmaoRef === $obreiro['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($obreiro['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="filtro-tipo" class="form-label">Tipo</label>
                            <select id="filtro-tipo" name="tipo" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($tiposEfemeride as $tipo): ?>
                                    <option value="<?= htmlspecialchars($tipo) ?>" <?= $filtroTipo === $tipo ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <a href="/chancelaria/efemerides" class="btn btn-secondary">Limpar Filtros</a>
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    </div>
                </form>

                <!-- Tabela de Registros -->
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>VÃ­nculo</th>
                                <th>Status</th>
                                <th>AÃ§Ãµes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrosRecentes)): ?>
                                <tr><td colspan="6" class="text-center py-10">Nenhum registro encontrado.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($registrosRecentes as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($formatarDataVisual($r['data_evento'] ?? null)) ?></td>
                                    <td><?= htmlspecialchars($r['nome'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
                                    <td><?= htmlspecialchars(($r['vinculo'] ?? '-') . ($r['parentesco'] ? ' (' . $r['parentesco'] . ')' : '')) ?></td>
                                    <td><span class="badge-status <?= !empty($r['ativo']) ? 'badge-status-success' : 'badge-status-danger' ?>"><?= !empty($r['ativo']) ? 'Regular' : 'Afastado' ?></span></td>
                                    <td>
                                        <?php if (empty($r['origem_fixa']) && !empty($r['ativo'])): ?>
                                            <a href="/chancelaria/efemerides?foco=dados&editar=<?= (int)($r['id'] ?? 0) ?>#secao-dados" class="btn btn-secondary text-xs">Editar</a>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral: Adicionar/Editar Registro -->
    <div class="space-y-8">
        <div class="card" id="secao-dados">
            <div class="card-header">
                <h2 class="card-title">Adicionar / Editar Registro</h2>
                <p class="card-subtitle">Os dados salvos aqui atualizam a base para futuros envios.</p>
            </div>
            <form method="POST" action="/chancelaria/efemerides/salvar" class="card-body space-y-4">
                <?php
                $registroEdicao = null;
                if (isset($_GET['editar'])) {
                    foreach ($registrosRecentes as $reg) {
                        if (($reg['id'] ?? null) == $_GET['editar']) {
                            $registroEdicao = $reg;
                            break;
                        }
                    }
                }
                ?>
                <input type="hidden" name="id" value="<?= (int)($registroEdicao['id'] ?? 0) ?>">

                <div>
                    <label for="form-nome" class="form-label">Nome *</label>
                    <input id="form-nome" type="text" name="nome" value="<?= htmlspecialchars($registroEdicao['nome'] ?? '') ?>" required class="form-input">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="form-tipo" class="form-label">Tipo *</label>
                        <select id="form-tipo" name="tipo" required class="form-select">
                            <?php foreach ($tiposEfemeride as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo) ?>" <?= ($registroEdicao['tipo'] ?? '') === $tipo ? 'selected' : '' ?>><?= htmlspecialchars($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="form-data" class="form-label">Data do Evento *</label>
                        <input id="form-data" type="date" name="data_evento" value="<?= htmlspecialchars($registroEdicao['data_evento'] ?? '') ?>" required class="form-input">
                    </div>
                </div>
                <div>
                    <label for="form-vinculo" class="form-label">VÃ­nculo</label>
                    <select id="form-vinculo" name="vinculo" class="form-select">
                        <option value="">Sem vÃ­nculo</option>
                        <?php foreach ($vinculosPadrao as $vinculo): ?>
                            <option value="<?= htmlspecialchars($vinculo['nome']) ?>" <?= ($registroEdicao['vinculo'] ?? '') === $vinculo['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($vinculo['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="form-parentesco" class="form-label">IrmÃ£o Relacionado (Parentesco)</label>
                    <input id="form-parentesco" type="text" name="parentesco" value="<?= htmlspecialchars($registroEdicao['parentesco'] ?? '') ?>" class="form-input" placeholder="Ex: Leandro Dalpiaz">
                </div>
                <div>
                    <label for="form-local" class="form-label">Local</label>
                    <input id="form-local" type="text" name="local" value="<?= htmlspecialchars($registroEdicao['local'] ?? '') ?>" class="form-input" placeholder="Ex: Loja RenascenÃ§a nÂº 270">
                </div>
                <div>
                    <label for="form-mensagem" class="form-label">Mensagem Complementar</label>
                    <textarea id="form-mensagem" name="mensagem_custom" rows="3" class="form-input" placeholder="Para 'HistÃ³ria', informe o texto completo aqui."><?= htmlspecialchars($registroEdicao['mensagem_custom'] ?? '') ?></textarea>
                </div>
                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="btn btn-primary"><?= $registroEdicao ? 'Salvar AlteraÃ§Ãµes' : 'Adicionar Registro' ?></button>
                    <?php if ($registroEdicao): ?>
                        <a href="/chancelaria/efemerides" class="btn btn-secondary">Cancelar EdiÃ§Ã£o</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function copiarPreview(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Texto copiado para a Ã¡rea de transferÃªncia.');
        }).catch(err => {
            console.error('Erro ao copiar texto: ', err);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const foco = urlParams.get('foco');
        const editarId = urlParams.get('editar');

        if (foco === 'mensagem' || foco === 'dados' || editarId) {
            const targetId = editarId ? 'secao-dados' : `secao-${foco}`;
            document.getElementById(targetId)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>


