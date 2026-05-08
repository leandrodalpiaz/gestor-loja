<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
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
$cardsEnabled = !empty($cardsEnabled);
$cards = is_array($cards ?? null) ? $cards : [];
$categoriasCards = is_array($categoriasCards ?? null) ? $categoriasCards : [];

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Chancelaria';
$appShellTitle = 'Efemérides e Mensagem do Dia';
$appShellDescription = 'Operação de mensagem diária, cadastro de eventos e manutenção de registros da Loja.';
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
                <p class="card-subtitle">Esta edição altera somente a mensagem de hoje. Os registros oficiais não são modificados aqui.</p>
            </div>
            <div class="card-body">
                <div class="telegram-format mb-4 p-4 rounded-lg bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                    <?= $previewRender ?>
                </div>
                <form method="POST" action="/chancelaria/efemerides/salvar-previa">
                    <textarea name="mensagem_preview" class="form-input h-60" placeholder="A mensagem gerada aparecerá aqui para revisão..."><?= htmlspecialchars($previewRaw) ?></textarea>
                    <p class="form-hint">Mantém tags HTML do Telegram (ex: &lt;b&gt; e &lt;i&gt;).</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="submit" class="btn btn-primary">Salvar Mensagem</button>
                        <button type="button" onclick="copiarPreview('<?= htmlspecialchars($previewRaw, ENT_QUOTES) ?>')" class="btn btn-secondary">Copiar Texto</button>
                    </div>
                </form>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-3">
                    <form method="POST" action="/chancelaria/efemerides/enviar-previa" onsubmit="return confirm('Enviar a prévia para o Telegram privado do chanceler?');">
                        <button type="submit" class="btn btn-secondary bg-indigo-600 text-white hover:bg-indigo-700">Enviar Prévia no Privado</button>
                    </form>
                    <form method="POST" action="/chancelaria/efemerides/enviar-grupo" onsubmit="return confirm('Confirmar envio da mensagem no grupo oficial?');">
                        <button type="submit" class="btn btn-primary bg-green-600 hover:bg-green-700">Enviar no Grupo Oficial</button>
                    </form>
                </div>
                <p class="form-hint mt-2">Sugestão: revise, envie no privado para conferência e, só então, publique no grupo.</p>
            </div>
        </div>
        <?php if ($cardsEnabled): ?>
        <div class="card" id="secao-cards">
            <div class="card-header">
                <h2 class="card-title">Esteira de Homologação de Cards</h2>
                <p class="card-subtitle">1 evento = 1 card, com prévia desktop-first.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="/chancelaria/efemerides/cards-template-categorias" class="mb-4 p-3 rounded-lg border border-gray-200">
                    <p class="text-sm font-semibold mb-2">Template padrão por categorias (selecionadas em tela)</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                        <?php foreach ($categoriasCards as $categoria): ?>
                            <label class="inline-flex items-center gap-2 text-xs">
                                <input type="checkbox" name="categorias[]" value="<?= htmlspecialchars($categoria) ?>">
                                <span><?= htmlspecialchars($categoria) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex gap-2">
                        <select name="template_slug" class="form-select text-xs">
                            <?php foreach ([
                                'card_irmao_pop.png','card_cunhada_solar.png','card_familia_kids.png','card_sobrinho_jovem.png','card_sobrinho_adulto.png','card_sobrinha_adulta.png',
                                'card_grau_iniciacao.png','card_grau_elevacao.png','card_grau_exaltacao.png','card_grau_instalacao.png',
                                'card_memorial_eterno.png','card_historia_sepia.png','card_oficial_sessao.png','card_oficial_convite.png','card_especial_filiacao.png','card_especial_honorario.png','card_especial_grao_mestre.png'
                            ] as $tpl): ?>
                                <option value="<?= htmlspecialchars($tpl) ?>"><?= htmlspecialchars($tpl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary text-xs">Aplicar nas Categorias Selecionadas</button>
                    </div>
                </form>
                <form method="POST" action="/chancelaria/efemerides/cards-aprovar-todos" class="mb-4">
                    <button type="submit" class="btn btn-primary">Aprovar Todos</button>
                </form>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php if (empty($cards)): ?>
                        <div class="col-span-full rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            Nenhum card gerado para hoje. Verifique se há registros ativos em efemérides para a data atual.
                        </div>
                    <?php endif; ?>
                    <?php foreach ($cards as $card): ?>
                        <article class="rounded-xl border border-gray-200 p-3 bg-white card-item" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>">
                            <div class="aspect-[9/16] rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden mb-2">
                                <?php if (!empty($card['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($card['image_url']) ?>" alt="Card" class="w-full h-full object-cover card-image">
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">Prévia indisponível</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm font-semibold card-title"><?= htmlspecialchars($card['titulo'] ?? '-') ?></p>
                            <p class="text-xs text-gray-600 card-template"><?= htmlspecialchars($card['template'] ?? '-') ?></p>
                            <div class="mt-2 space-y-2">
                                <p class="text-xs text-green-700 hidden card-status">Prévia atualizada.</p>
                                <label class="inline-flex items-center gap-2 text-xs">
                                    <input type="checkbox" class="card-toggle-idade" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>" <?= !empty($card['ocultar_idade']) ? 'checked' : '' ?>>
                                    <span>Ocultar idade</span>
                                </label>
                                <textarea class="form-input text-xs card-texto-custom" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>" rows="2" placeholder="Texto custom do card"><?= htmlspecialchars($card['texto_custom_card'] ?? '') ?></textarea>
                                <select class="form-select text-xs card-template-select" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>">
                                    <?php
                                    $templates = [
                                        'card_irmao_pop.png','card_cunhada_solar.png','card_familia_kids.png','card_sobrinho_jovem.png','card_sobrinho_adulto.png','card_sobrinha_adulta.png',
                                        'card_grau_iniciacao.png','card_grau_elevacao.png','card_grau_exaltacao.png','card_grau_instalacao.png',
                                        'card_memorial_eterno.png','card_historia_sepia.png','card_oficial_sessao.png','card_oficial_convite.png','card_especial_filiacao.png','card_especial_honorario.png','card_especial_grao_mestre.png'
                                    ];
                                    foreach ($templates as $tpl): ?>
                                        <option value="<?= htmlspecialchars($tpl) ?>" <?= (($card['template'] ?? '') === $tpl) ? 'selected' : '' ?>><?= htmlspecialchars($tpl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="flex gap-2">
                                    <?php if (!empty($card['image_url'])): ?>
                                        <a href="<?= htmlspecialchars($card['image_url']) ?>" download class="btn btn-secondary text-xs card-download">Salvar Imagem</a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary text-xs card-open-modal">Abrir</button>
                                    <button type="button" class="btn btn-secondary text-xs card-btn-preview" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>">Atualizar Prévia</button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Registros -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Registros de Efemérides (<?= count($registrosRecentes) ?>)</h2>
                <p class="card-subtitle">Consulte e gerencie os registros que alimentam as mensagens automáticas.</p>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" action="/chancelaria/efemerides" class="space-y-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="filtro-termo" class="form-label">Pesquisar</label>
                            <input id="filtro-termo" type="text" name="termo" value="<?= htmlspecialchars($filtroTermo) ?>" class="form-input" placeholder="Nome, vínculo...">
                        </div>
                        <div>
                            <label for="filtro-irmao" class="form-label">Irmão</label>
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
                                <th>Vínculo</th>
                                <th>Status</th>
                                <th>Ações</th>
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
                    <label for="form-vinculo" class="form-label">Vínculo</label>
                    <select id="form-vinculo" name="vinculo" class="form-select">
                        <option value="">Sem vínculo</option>
                        <?php foreach ($vinculosPadrao as $vinculo): ?>
                            <option value="<?= htmlspecialchars($vinculo['nome']) ?>" <?= ($registroEdicao['vinculo'] ?? '') === $vinculo['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($vinculo['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="form-parentesco" class="form-label">Irmão Relacionado (Parentesco)</label>
                    <input id="form-parentesco" type="text" name="parentesco" value="<?= htmlspecialchars($registroEdicao['parentesco'] ?? '') ?>" class="form-input" placeholder="Ex: Leandro Dalpiaz">
                </div>
                <div>
                    <label for="form-local" class="form-label">Local</label>
                    <input id="form-local" type="text" name="local" value="<?= htmlspecialchars($registroEdicao['local'] ?? '') ?>" class="form-input" placeholder="Ex: Loja Renascença nº 270">
                </div>
                <div>
                    <label for="form-mensagem" class="form-label">Mensagem Complementar</label>
                    <textarea id="form-mensagem" name="mensagem_custom" rows="3" class="form-input" placeholder="Para 'História', informe o texto completo aqui."><?= htmlspecialchars($registroEdicao['mensagem_custom'] ?? '') ?></textarea>
                </div>
                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="btn btn-primary"><?= $registroEdicao ? 'Salvar Alterações' : 'Adicionar Registro' ?></button>
                    <?php if ($registroEdicao): ?>
                        <a href="/chancelaria/efemerides" class="btn btn-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="card-modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-3 max-w-md w-full">
        <div class="flex justify-between items-center mb-2">
            <p class="font-semibold text-sm">Visualização do Card</p>
            <button type="button" id="card-modal-close" class="btn btn-secondary text-xs">Fechar</button>
        </div>
        <div class="aspect-[9/16] rounded-lg bg-gray-100 overflow-hidden">
            <img id="card-modal-image" src="" alt="Card ampliado" class="w-full h-full object-contain">
        </div>
    </div>
</div>

<script>
    function copiarPreview(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Texto copiado para a área de transferência.');
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

        document.querySelectorAll('.card-btn-preview').forEach((button) => {
            button.addEventListener('click', async () => {
                const registroId = button.getAttribute('data-registro-id');
                const hideInput = document.querySelector(`.card-toggle-idade[data-registro-id="${registroId}"]`);
                const textInput = document.querySelector(`.card-texto-custom[data-registro-id="${registroId}"]`);
                const templateInput = document.querySelector(`.card-template-select[data-registro-id="${registroId}"]`);
                const response = await fetch('/chancelaria/efemerides/cards-configurar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        registro_id: registroId || '',
                        ocultar_idade: hideInput && hideInput.checked ? '1' : '',
                        texto_custom_card: textInput ? textInput.value : '',
                        template_card: templateInput ? templateInput.value : ''
                    }).toString()
                });
                const data = await response.json();
                if (!data || !data.ok || !data.card) return;
                const item = document.querySelector(`.card-item[data-registro-id="${registroId}"]`);
                if (!item) return;
                const image = item.querySelector('.card-image');
                if (image && data.card.image_url) image.src = `${data.card.image_url}?t=${Date.now()}`;
                const template = item.querySelector('.card-template');
                if (template && data.card.template) template.textContent = data.card.template;
                if (templateInput && data.card.template) templateInput.value = data.card.template;
                const download = item.querySelector('.card-download');
                if (download && data.card.image_url) download.setAttribute('href', data.card.image_url);
                const status = item.querySelector('.card-status');
                if (status) {
                    status.classList.remove('hidden');
                    setTimeout(() => status.classList.add('hidden'), 1800);
                }
            });
        });

        const modal = document.getElementById('card-modal');
        const modalImage = document.getElementById('card-modal-image');
        const modalClose = document.getElementById('card-modal-close');
        document.querySelectorAll('.card-open-modal').forEach((button) => {
            button.addEventListener('click', () => {
                const item = button.closest('.card-item');
                const image = item ? item.querySelector('.card-image') : null;
                if (!modal || !modalImage || !image) return;
                modalImage.src = image.getAttribute('src') || '';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });
        if (modal && modalClose) {
            modalClose.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }
        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        }
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    });
</script>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>


