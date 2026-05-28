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

<?php
$historiasRecentes = is_array($historiasRecentes ?? []) ? $historiasRecentes : [];
$palavrasDia = is_array($palavrasDia ?? []) ? $palavrasDia : [];
?>

<div class="mb-8 border-b border-white/10">
    <nav class="flex space-x-8" aria-label="Tabs">
        <button type="button" onclick="switchTab('diaria')" id="btn-tab-diaria" class="border-[#C9A227] text-[#C9A227] border-b-2 py-4 px-1 text-sm font-medium uppercase tracking-wider tab-btn">
            Dia a Dia (Efemérides)
        </button>
        <button type="button" onclick="switchTab('historia')" id="btn-tab-historia" class="border-transparent text-slate-400 hover:text-white hover:border-white/20 border-b-2 py-4 px-1 text-sm font-medium uppercase tracking-wider tab-btn">
            Nossa História
        </button>
    </nav>
</div>

<div id="pane-tab-diaria" class="tab-content-pane">
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
                <div class="telegram-format mb-4 p-4 rounded-lg bg-white/5 border border-white/10 text-white">
                    <?= $previewRender ?>
                </div>

                <div class="bg-warning/5 border-l-4 border-warning p-4 mb-6 rounded-xl border border-warning/20 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl mt-0.5">💡</div>
                        <div class="text-sm text-slate-300">
                            <p class="font-bold text-base mb-1 text-warning">Modo Mais Simples:</p>
                            <p>Role para baixo até os <b>Cards de Imagem</b> abaixo, cole seu texto LIMPO (sem códigos) na caixa do card e salve. O sistema atualizará a imagem e o texto do Telegram automaticamente, mantendo a formatação bonita pra você!</p>
                        </div>
                    </div>
                </div>

                <!-- NOVO FLUXO UNIFICADO -->
                <div class="mb-6 bg-success/10 border border-success/20 rounded-xl p-5 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-success text-lg flex items-center gap-2">
                            🚀 Publicação Consolidada
                        </h4>
                        <p class="text-sm text-slate-300">
                            Aprova o texto final e dispara <b>Texto + Imagens</b> juntos para o Grupo Oficial.
                        </p>
                    </div>
                    <form method="POST" action="/chancelaria/efemerides/aprovar-e-enviar-tudo" onsubmit="return confirm('Confirmar aprovação final e envio imediato de Texto + Imagens para o Grupo do Telegram?');" class="flex-shrink-0">
                         <button type="submit" class="btn bg-success text-[#0E2640] hover:bg-success/80 shadow-md px-6 py-4 text-base font-bold uppercase tracking-wider flex items-center gap-2">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                             Aprovar e Enviar Tudo
                         </button>
                    </form>
                </div>
                <!-- FIM NOVO FLUXO -->

                <details class="group bg-white/[0.02] border border-white/10 rounded-lg overflow-hidden">
                    <summary class="flex items-center justify-between cursor-pointer p-4 bg-white/5 text-slate-300 hover:text-white font-semibold text-sm list-none select-none">
                        <span class="flex items-center gap-2">
                            ⚙️ Edição Avançada do Telegram (Com Códigos/Tags)
                        </span>
                        <span class="transition group-open:rotate-180">
                            <svg fill="none" height="20" width="20" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </span>
                    </summary>
                    <div class="p-4 border-t border-white/10 text-slate-300">
                        <form method="POST" action="/chancelaria/efemerides/salvar-previa">
                            <textarea name="mensagem_preview" class="form-input h-60 font-mono text-sm" placeholder="A mensagem gerada aparecerá aqui para revisão..."><?= htmlspecialchars($previewRaw) ?></textarea>
                            <p class="form-hint mt-1">Edite aqui somente se quiser manipular diretamente o HTML do Telegram.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary text-xs">Salvar Modificação Manual</button>
                                <button type="button" onclick="copiarPreview('<?= htmlspecialchars($previewRaw, ENT_QUOTES) ?>')" class="btn btn-secondary text-xs">Copiar Texto Bruto</button>
                            </div>
                        </form>
                        <div class="mt-3 pt-3 border-t border-white/10 flex flex-wrap gap-2">
                            <form method="POST" action="/chancelaria/efemerides/enviar-previa" onsubmit="return confirm('Enviar a prévia para o Telegram privado do chanceler?');">
                                <button type="submit" class="btn btn-secondary text-xs bg-indigo-600 text-white hover:bg-indigo-700">Enviar Prévia Privada</button>
                            </form>
                            <form method="POST" action="/chancelaria/efemerides/enviar-grupo" onsubmit="return confirm('Confirmar envio isolado no grupo oficial?');">
                                <button type="submit" class="btn btn-primary text-xs bg-success text-[#0E2640] hover:bg-success/80">Enviar Apenas Texto no Grupo</button>
                            </form>
                        </div>
                    </div>
                </details>
            </div>
        </div>
        <?php if ($cardsEnabled): ?>
        <div class="card" id="secao-cards">
            <div class="card-header">
                <h2 class="card-title">Esteira de Homologação de Cards</h2>
                <p class="card-subtitle">1 evento = 1 card, com prévia desktop-first.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="/chancelaria/efemerides/cards-template-categorias" class="mb-4 p-3 rounded-lg border border-white/10 bg-white/[0.01]">
                    <p class="text-sm font-semibold text-white mb-2">Template padrão por categorias (selecionadas em tela)</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                        <?php foreach ($categoriasCards as $categoria): ?>
                            <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                <input type="checkbox" name="categorias[]" value="<?= htmlspecialchars($categoria) ?>" class="form-checkbox">
                                <span><?= htmlspecialchars($categoria) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex gap-2">
                        <select name="template_slug" class="form-select text-xs">
                            <?php foreach ([
                                'card_irmao_bedrock.png','card_cunhada_solar.png','card_familia_kids.png','card_sobrinho_jovem.png','card_sobrinho_adulto.png','card_sobrinha_adulta.png',
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
                        <div class="col-span-full rounded-lg border border-warning/20 bg-warning/5 p-3 text-sm text-warning">
                            Nenhum card gerado para hoje. Verifique se há registros ativos em efemérides para a data atual.
                        </div>
                    <?php endif; ?>
                    <?php foreach ($cards as $card): ?>
                        <article class="rounded-xl border border-white/10 p-3 bg-white/5 card-item" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>">
                            <div class="aspect-[9/16] rounded-lg bg-black/20 flex items-center justify-center overflow-hidden mb-2">
                                <?php if (!empty($card['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($card['image_url']) ?>" alt="Card" class="w-full h-full object-cover card-image">
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Prévia indisponível</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm font-semibold card-title text-white"><?= htmlspecialchars($card['titulo'] ?? '-') ?></p>
                            <p class="text-xs text-slate-400 card-template"><?= htmlspecialchars($card['template'] ?? '-') ?></p>
                            <div class="mt-2 space-y-2">
                                <p class="text-xs text-success hidden card-status">Prévia atualizada.</p>
                                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" class="card-toggle-idade form-checkbox" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>" <?= !empty($card['ocultar_idade']) ? 'checked' : '' ?>>
                                    <span>Ocultar idade</span>
                                </label>
                                <div class="pt-2 border-t border-white/10">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Texto para este card:</label>
                                    <textarea class="form-input text-xs card-texto-custom w-full font-sans leading-snug" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>" rows="5" placeholder="Texto customizado para este card..."><?= htmlspecialchars($card['texto_custom_card'] ?? '') ?></textarea>
                                </div>
                                <div class="pt-1">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Template Visual:</label>
                                    <select class="form-select text-xs card-template-select w-full" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>">
                                    <?php
                                    $templates = [
                                        'card_irmao_bedrock.png','card_cunhada_solar.png','card_familia_kids.png','card_sobrinho_jovem.png','card_sobrinho_adulto.png','card_sobrinha_adulta.png',
                                        'card_grau_iniciacao.png','card_grau_elevacao.png','card_grau_exaltacao.png','card_grau_instalacao.png',
                                        'card_memorial_eterno.png','card_historia_sepia.png','card_oficial_sessao.png','card_oficial_convite.png','card_especial_filiacao.png','card_especial_honorario.png','card_especial_grao_mestre.png'
                                    ];
                                    foreach ($templates as $tpl): ?>
                                        <option value="<?= htmlspecialchars($tpl) ?>" <?= (($card['template'] ?? '') === $tpl) ? 'selected' : '' ?>><?= htmlspecialchars($tpl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="grid grid-cols-2 gap-2 pt-2">
                                    <button type="button" class="btn btn-primary text-xs font-bold w-full card-btn-preview" data-registro-id="<?= (int) ($card['registro_id'] ?? 0) ?>">
                                        💾 Salvar & Atualizar
                                    </button>
                                    <button type="button" class="btn btn-secondary text-xs w-full card-open-modal">🔍 Ampliar</button>
                                    <?php if (!empty($card['image_url'])): ?>
                                        <a href="<?= htmlspecialchars($card['image_url']) ?>" download class="btn btn-secondary text-[10px] w-full text-center card-download col-span-2">Baixar Arquivo</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- REPLICA DO BOTÃO DE ENVIO NO FINAL DA ESTEIRA PARA MELHOR UX -->
                <div class="mt-8 bg-success/10 border border-success/20 rounded-xl p-5 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-success text-lg flex items-center gap-2">
                            🚀 Conferência Concluída?
                        </h4>
                        <p class="text-sm text-slate-300">
                            Dispare a aprovação definitiva de Texto + Imagens agora.
                        </p>
                    </div>
                    <form method="POST" action="/chancelaria/efemerides/aprovar-e-enviar-tudo" onsubmit="return confirm('Confirmar aprovação final e envio imediato de Texto + Imagens para o Grupo do Telegram?');" class="flex-shrink-0">
                         <button type="submit" class="btn bg-success text-[#0E2640] hover:bg-success/80 shadow-md px-8 py-4 text-base font-bold uppercase tracking-wider flex items-center gap-2">
                             🚀 Publicar Tudo no Grupo
                         </button>
                    </form>
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
                                    <td>
                                        <?php
                                        $vinculoExibido = (string) ($r['vinculo'] ?? '-');
                                        if (strtolower($vinculoExibido) === 'esposa') {
                                            $vinculoExibido = 'Cunhada';
                                        }
                                        echo htmlspecialchars($vinculoExibido . ($r['parentesco'] ? ' (' . $r['parentesco'] . ')' : ''));
                                        ?>
                                    </td>
                                    <td><span class="badge-status <?= !empty($r['ativo']) ? 'badge-status-success' : 'badge-status-danger' ?>"><?= !empty($r['ativo']) ? 'Regular' : 'Afastado' ?></span></td>
                                    <td>
                                        <?php if (empty($r['origem_fixa']) && !empty($r['ativo'])): ?>
                                            <a href="/chancelaria/efemerides?foco=dados&editar=<?= (int)($r['id'] ?? 0) ?>#secao-dados" class="btn btn-secondary text-xs">Editar</a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-500">N/A</span>
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
                            <?php
                            $nomeVinculo = (string) ($vinculo['nome'] ?? '');
                            $labelVinculo = $nomeVinculo;
                            if (strtolower($nomeVinculo) === 'esposa') {
                                $labelVinculo = 'Cunhada';
                            }
                            ?>
                            <option value="<?= htmlspecialchars($nomeVinculo) ?>" <?= ($registroEdicao['vinculo'] ?? '') === $nomeVinculo ? 'selected' : '' ?>><?= htmlspecialchars(mb_convert_case($labelVinculo, MB_CASE_TITLE, "UTF-8")) ?></option>
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
                <div class="flex flex-wrap gap-3 pt-4 border-t border-white/10">
                    <button type="submit" class="btn btn-primary"><?= $registroEdicao ? 'Salvar Alterações' : 'Adicionar Registro' ?></button>
                    <?php if ($registroEdicao): ?>
                        <a href="/chancelaria/efemerides" class="btn btn-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div> <!-- Fim grid pane-tab-diaria -->
</div> <!-- Fim pane-tab-diaria -->

<!-- TELA: NOSSA HISTÓRIA -->
<div id="pane-tab-historia" class="tab-content-pane hidden">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Listagem Visual -->
        <div class="lg:col-span-8 space-y-6">
            <div class="card border-t-4 border-t-[#C9A227]">
                <div class="card-header flex items-center justify-between bg-gradient-to-r from-[#0f1c2e] to-[#162a42] border-b-0 rounded-t-xl py-6 px-8">
                    <div>
                         <h2 class="font-cinzel text-xl text-white tracking-wider mb-1">Acervo da Memória da Loja</h2>
                         <p class="text-xs text-slate-400">Toda a linha do tempo registrada que alimentará os cards históricos da dashboard.</p>
                    </div>
                </div>
                <div class="card-body p-6 md:p-8">
                    <?php if (empty($historiasRecentes)): ?>
                        <div class="p-12 border-2 border-dashed border-white/10 rounded-2xl text-center">
                            <div class="text-4xl mb-4 opacity-30">📜</div>
                            <h3 class="font-cinzel text-lg font-bold text-white">Nenhuma memória registrada</h3>
                            <p class="text-sm text-slate-400 mt-1">Utilize o formulário ao lado para inaugurar o acervo histórico.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach($historiasRecentes as $hist): ?>
                                <div class="group border border-white/10 rounded-xl overflow-hidden bg-[#162a42] shadow-sm transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-[#C9A227]/50">
                                    <div class="flex h-full">
                                        <!-- Capa Visual (Sépia) -->
                                        <div class="w-1/3 bg-[#fdf8f0] relative flex items-center justify-center p-3 border-r border-white/10">
                                             <img src="/assets/images/templates/efemerides/card_historia_sepia.png" class="w-full h-full object-contain drop-shadow-md transition-transform group-hover:scale-105">
                                             <div class="absolute top-2 right-2 bg-black/60 text-white text-[8px] px-1.5 rounded font-bold">PREVIEW</div>
                                        </div>
                                        <!-- Conteúdo -->
                                        <div class="w-2/3 p-5 flex flex-col">
                                             <div class="flex items-center justify-between mb-2">
                                                 <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-warning/10 text-warning uppercase tracking-widest">
                                                     <?= sprintf('%02d/%02d', $hist['dia'], $hist['mes']) ?><?= !empty($hist['ano_ref']) ? ' / ' . $hist['ano_ref'] : '' ?>
                                                 </span>
                                                 <?php if(empty($hist['ativo'])): ?>
                                                     <span class="text-[9px] bg-red-100 text-red-800 px-1.5 rounded">Inativa</span>
                                                 <?php endif; ?>
                                             </div>
                                             <h3 class="font-cinzel text-sm font-bold text-white mb-2 leading-tight group-hover:text-[#C9A227] transition-colors">
                                                 <?= htmlspecialchars($hist['titulo'] ?? '') ?>
                                             </h3>
                                             <p class="text-xs text-slate-300 line-clamp-4 leading-relaxed flex-grow">
                                                 <?= htmlspecialchars($hist['texto'] ?? '') ?>
                                             </p>
                                             <div class="mt-4 pt-3 border-t border-white/10 flex justify-between items-center">
                                                  <a href="/chancelaria/efemerides?editar_historia=<?= (int)($hist['id'] ?? 0) ?>" class="inline-flex items-center gap-1 text-xs font-bold text-info hover:underline decoration-2">
                                                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                      Editar
                                                  </a>
                                                  <form method="POST" action="/chancelaria/historias/excluir" onsubmit="return confirm('Excluir esta memória permanentemente do registro?');" class="m-0">
                                                      <input type="hidden" name="id" value="<?= (int)($hist['id'] ?? 0) ?>">
                                                      <button type="submit" class="text-[10px] text-red-500 hover:text-red-700 font-medium opacity-60 group-hover:opacity-100 transition-opacity">Excluir</button>
                                                  </form>
                                             </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar CRUD -->
        <div class="lg:col-span-4">
            <?php 
                $histEdit = null;
                if (isset($_GET['editar_historia'])) {
                    foreach($historiasRecentes as $h) {
                         if (($h['id'] ?? 0) == $_GET['editar_historia']) { 
                             $histEdit = $h; 
                             break; 
                         }
                    }
                }
                $actionHist = $histEdit ? '/chancelaria/historias/atualizar' : '/chancelaria/historias/salvar';
            ?>
            <div class="card sticky top-6 bg-[#162a42] shadow-lg border border-white/10">
                <div class="card-header border-b border-white/10 p-6">
                    <h3 class="font-cinzel text-lg font-bold text-white">
                        <?= $histEdit ? 'Editar Fato Histórico' : 'Nova Memória da Loja' ?>
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Preencha os dados que serão impressos no pergaminho.</p>
                </div>
                <form method="POST" action="<?= $actionHist ?>" class="card-body p-6 space-y-5">
                    <?php if($histEdit): ?>
                        <input type="hidden" name="id" value="<?= (int)$histEdit['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label for="hist-titulo" class="form-label text-xs font-bold uppercase tracking-wider text-slate-400">Título do Evento *</label>
                        <input id="hist-titulo" type="text" name="titulo" required class="form-input rounded-lg" placeholder="Ex: Fundação da Oficina" value="<?= htmlspecialchars($histEdit['titulo'] ?? '') ?>">
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="form-label text-xs font-bold uppercase tracking-wider text-slate-400">Dia *</label>
                            <input type="number" name="dia" min="1" max="31" required class="form-input text-center font-bold" value="<?= (int)($histEdit['dia'] ?? date('d')) ?>">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold uppercase tracking-wider text-slate-400">Mês *</label>
                            <input type="number" name="mes" min="1" max="12" required class="form-input text-center font-bold" value="<?= (int)($histEdit['mes'] ?? date('m')) ?>">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold uppercase tracking-wider text-slate-400">Ano</label>
                            <input type="number" name="ano_ref" class="form-input text-center text-slate-300 placeholder-slate-500" placeholder="AAAA" value="<?= !empty($histEdit['ano_ref']) ? (int)$histEdit['ano_ref'] : '' ?>">
                        </div>
                    </div>

                    <div>
                        <label for="hist-texto" class="form-label text-xs font-bold uppercase tracking-wider text-slate-400">Narrativa da História *</label>
                        <textarea id="hist-texto" name="texto" rows="8" required class="form-input text-sm leading-relaxed rounded-lg bg-transparent" placeholder="Neste dia importante em nossa jornada..."><?= htmlspecialchars($histEdit['texto'] ?? '') ?></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">O motor gráfico quebrará as linhas automaticamente no pergaminho.</p>
                    </div>

                    <div>
                        <label for="hist-fonte" class="form-label text-xs font-bold uppercase tracking-wider text-slate-400">Fonte de Referência</label>
                        <input id="hist-fonte" type="text" name="fonte" class="form-input text-xs placeholder-slate-500" placeholder="Ex: Livro de Arquitetura Vol. 1" value="<?= htmlspecialchars($histEdit['fonte'] ?? '') ?>">
                    </div>

                    <div class="pt-4 flex flex-col gap-3 border-t border-white/10">
                        <button type="submit" class="btn btn-primary py-3 text-sm font-bold uppercase tracking-widest w-full">
                            <?= $histEdit ? 'Salvar Alterações' : 'Publicar Memória' ?>
                        </button>
                        <?php if($histEdit): ?>
                            <a href="/chancelaria/efemerides?foco=historia" class="btn btn-secondary text-center py-3 text-sm">Cancelar Edição</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="card-modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4">
    <div class="bg-erp-surface border border-white/10 rounded-xl p-4 max-w-md w-full">
        <div class="flex justify-between items-center mb-3">
            <p class="font-semibold text-sm text-white">Visualização do Card</p>
            <button type="button" id="card-modal-close" class="btn btn-secondary text-xs">Fechar</button>
        </div>
        <div class="aspect-[9/16] rounded-lg bg-black/20 overflow-hidden">
            <img id="card-modal-image" src="" alt="Card ampliado" class="w-full h-full object-contain">
        </div>
    </div>
</div>

<script>
    window.switchTab = function(tabId) {
        // Oculta todos os paineis
        document.querySelectorAll('.tab-content-pane').forEach(el => el.classList.add('hidden'));
        
        // Remove classes ativas dos botões
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-[#C9A227]', 'text-[#C9A227]');
            btn.classList.add('border-transparent', 'text-slate-400');
        });

        // Ativa o selecionado
        const activePane = document.getElementById('pane-tab-' + tabId);
        if (activePane) activePane.classList.remove('hidden');

        const activeBtn = document.getElementById('btn-tab-' + tabId);
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'text-slate-400');
            activeBtn.classList.add('border-[#C9A227]', 'text-[#C9A227]');
        }

        // Salva na URL sem recarregar para persistência opcional ou deixa padrão
        const url = new URL(window.location.href);
        url.searchParams.set('active_tab', tabId);
        window.history.replaceState({}, '', url);
    };

    function copiarPreview(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Texto copiado para a área de transferência.');
        }).catch(err => {
            console.error('Erro ao copiar texto: ', err);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Restaurar scroll prévio após recarregamento (se salvo)
        const savedScroll = localStorage.getItem('efemerides_scroll_pos');
        if (savedScroll) {
            window.scrollTo(0, parseInt(savedScroll, 10));
            localStorage.removeItem('efemerides_scroll_pos');
        }

        const urlParams = new URLSearchParams(window.location.search);
        
        // Auto-seleção de abas baseada em URL ou Edição
        const urlTab = urlParams.get('active_tab');
        const editarHistoria = urlParams.get('editar_historia');
        const foco = urlParams.get('foco');
        
        if (editarHistoria || foco === 'historia' || urlTab === 'historia') {
            window.switchTab('historia');
        } else if (urlTab) {
            window.switchTab(urlTab);
        }

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
                    // Recarrega para sincronizar a prévia consolidada do topo automaticamente após 1 segundo.
                    setTimeout(() => {
                        localStorage.setItem('efemerides_scroll_pos', window.scrollY);
                        window.location.reload();
                    }, 800);
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


