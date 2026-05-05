<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$formatDate = static fn(?string $val): string => !empty(trim((string) $val)) ? (new DateTimeImmutable(trim((string) $val)))->format('d/m/Y') : '-';

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Relatório Anual';
$appShellDescription = 'Consolidação anual da atividade da Loja sob responsabilidade da Secretaria.';
$appShellActiveHref = '/secretaria/relatorio-anual';
if (($_SERVER['REQUEST_URI'] ?? '') === '/secretaria/relatorio-gestao') {
    $appShellTitle = 'Relatório de Gestão';
    $appShellActiveHref = '/secretaria/relatorio-gestao';
}
require __DIR__ . '/_sidebar.php';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Filtro de Ano -->
<div class="card mb-8">
    <div class="card-body">
        <form method="GET" action="/secretaria/relatorio-anual" class="flex flex-col sm:flex-row sm:items-end sm:gap-4">
            <div>
                <label for="ano" class="form-label">Ano de referência</label>
                <select name="ano" id="ano" class="form-select">
                    <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                        <option value="<?= (int) $anoOpcao ?>" <?= (int) $anoOpcao === (int) $relatorio['ano'] ? 'selected' : '' ?>>
                            <?= (int) $anoOpcao ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mt-4 sm:mt-0">Atualizar Relatório</button>
        </form>
    </div>
</div>

<!-- Identificação Institucional -->
<div class="card mb-8">
    <div class="card-body">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div>
                <h2 class="text-xl font-bold">
                    <?= htmlspecialchars((string) (($relatorio['loja']['nome_loja'] ?? '') ?: 'Loja não configurada')) ?>
                </h2>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <p><strong>Potência:</strong> <?= htmlspecialchars(trim((string) (($relatorio['loja']['potencia_nome'] ?? 'não informada') . (!empty($relatorio['loja']['potencia_sigla']) ? ' (' . $relatorio['loja']['potencia_sigla'] . ')' : '')))) ?></p>
                    <p><strong>Oriente:</strong> <?= htmlspecialchars((string) (($relatorio['loja']['oriente'] ?? '') ?: 'não informado')) ?></p>
                    <p><strong>Cidade/UF:</strong> <?= htmlspecialchars(trim((string) (($relatorio['loja']['cidade'] ?? '') . ' / ' . ($relatorio['loja']['uf'] ?? '')), ' /')) ?></p>
                    <p><strong>Rito:</strong> <?= htmlspecialchars((string) (($relatorio['loja']['rito'] ?? '') ?: 'não informado')) ?></p>
                    <p><strong>Fundação:</strong> <?= $formatDate($relatorio['loja']['data_fundacao'] ?? null) ?></p>
                    <p><strong>Instalação:</strong> <?= $formatDate($relatorio['loja']['data_instalacao'] ?? null) ?></p>
                </div>
            </div>
            <?php if (!empty($relatorio['loja']['observacao_relatorios'])): ?>
                <div class="max-w-md alert alert-warning">
                    <strong>Observação:</strong> <?= htmlspecialchars((string) $relatorio['loja']['observacao_relatorios']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Métricas Principais -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Visitantes</p><p class="card-metric-value"><?= (int) ($relatorio['visitantes']['total'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Visitas Externas</p><p class="card-metric-value"><?= (int) ($relatorio['visitas_externas']['total'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Congressos</p><p class="card-metric-value"><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Palestras</p><p class="card-metric-value"><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Sessões</p><p class="card-metric-value"><?= (int) ($relatorio['sessoes_por_grau']['total'] ?? 0) ?></p></div>
</div>

<!-- Métricas do Quadro -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Obreiros no Quadro</p><p class="card-metric-value"><?= (int) ($relatorio['perfil_quadro']['total'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Idade Média</p><p class="card-metric-value"><?= ($relatorio['perfil_quadro']['idade_media'] ?? null) !== null ? round((float)$relatorio['perfil_quadro']['idade_media']) . ' anos' : '-' ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Situação Predominante</p><p class="card-metric-value !text-2xl"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($relatorio['perfil_quadro']['situacoes'][0]['categoria'] ?? 'N/A')))) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Coluna Esquerda -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Visitantes e Visitas Externas</h3><p class="card-description">Frequência de visitantes na Loja e de obreiros em outras Lojas.</p></div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold mb-3">Lojas mais frequentes (Visitantes)</h4>
                    <div class="space-y-2">
                        <?php if (empty($relatorio['visitantes']['lojas_mais_frequentes'])): ?>
                            <p class="text-sm text-gray-500">Nenhum registro.</p>
                        <?php else: ?>
                            <?php foreach ($relatorio['visitantes']['lojas_mais_frequentes'] as $linha): ?>
                                <div class="list-item"><span><?= htmlspecialchars((string) ($linha['loja'] ?? 'N/A')) ?></span><strong><?= (int) ($linha['total'] ?? 0) ?></strong></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fonte: <?= htmlspecialchars((string) ($relatorio['visitantes']['fonte'] ?? 'N/A')) ?></p>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Lojas mais visitadas (Externas)</h4>
                    <div class="space-y-2">
                        <?php if (empty($relatorio['visitas_externas']['lojas_mais_visitadas'])): ?>
                            <p class="text-sm text-gray-500">Nenhum registro.</p>
                        <?php else: ?>
                            <?php foreach ($relatorio['visitas_externas']['lojas_mais_visitadas'] as $linha): ?>
                                <div class="list-item"><span><?= htmlspecialchars((string) ($linha['loja'] ?? 'N/A')) ?></span><strong><?= (int) ($linha['total'] ?? 0) ?></strong></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fonte: <?= htmlspecialchars((string) ($relatorio['visitas_externas']['fonte'] ?? 'N/A')) ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Sessões e Eventos</h3><p class="card-description">Distribuição de sessões por grau e total de eventos.</p></div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold mb-3">Sessões por Grau</h4>
                    <div class="space-y-2">
                        <?php if (empty($relatorio['sessoes_por_grau']['itens'])): ?>
                            <p class="text-sm text-gray-500">Nenhuma sessão no período.</p>
                        <?php else: ?>
                            <?php foreach ($relatorio['sessoes_por_grau']['itens'] as $linha): ?>
                                <div class="list-item"><span><?= htmlspecialchars((string) ($linha['grau_sessao'] ?? 'N/A')) ?></span><strong><?= (int) ($linha['total'] ?? 0) ?></strong></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-400 mt-2"><?= htmlspecialchars((string) ($relatorio['sessoes_por_grau']['regra'] ?? '')) ?></p>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Eventos Registrados</h4>
                    <div class="space-y-4">
                        <div class="list-item flex-col items-start"><div class="flex justify-between w-full"><span>Congressos</span><strong><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></strong></div><p class="text-xs text-gray-400 w-full">Fonte: <?= htmlspecialchars((string) ($relatorio['congressos']['fonte'] ?? 'N/A')) ?></p></div>
                        <div class="list-item flex-col items-start"><div class="flex justify-between w-full"><span>Palestras</span><strong><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></strong></div><p class="text-xs text-gray-400 w-full">Fonte: <?= htmlspecialchars((string) ($relatorio['palestras']['fonte'] ?? 'N/A')) ?></p></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Amostra Cadastral</h3><p class="card-description">Leitura rápida para apoio ao saneamento e conferências.</p></div>
            <div class="card-body space-y-3 max-h-96 overflow-y-auto">
                <?php if (empty($relatorio['perfil_quadro']['amostra_cadastral'])): ?>
                    <p class="text-sm text-gray-500 text-center py-4">Não há obreiros elegíveis no período.</p>
                <?php else: ?>
                    <?php foreach ($relatorio['perfil_quadro']['amostra_cadastral'] as $item): ?>
                        <div class="list-item-report">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($item['nome_exibicao'] ?? '-')) ?></p>
                                <span class="badge badge-secondary text-xs"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($item['situacao_quadro'] ?? 'N/A')))) ?></span>
                            </div>
                            <div class="mt-2 text-xs grid grid-cols-2 gap-1">
                                <p><strong>Grau:</strong> <?= htmlspecialchars((string) ($item['grau'] ?? 'N/A')) ?></p>
                                <p><strong>Profissão:</strong> <?= htmlspecialchars((string) ($item['profissao'] ?? 'N/A')) ?></p>
                                <p><strong>Escolaridade:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($item['escolaridade'] ?? 'N/A')))) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Direita -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Movimentação do Quadro</h3><p class="card-description">Panorama anual da composição do quadro.</p></div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="card-metric-simple"><p class="card-metric-label">Início do ano</p><p class="card-metric-value text-2xl"><?= ($relatorio['quadro']['inicio_ano'] ?? null) !== null ? $relatorio['quadro']['inicio_ano'] : '-' ?></p></div>
                    <div class="card-metric-simple"><p class="card-metric-label">Fim do ano</p><p class="card-metric-value text-2xl"><?= ($relatorio['quadro']['fim_ano'] ?? null) !== null ? $relatorio['quadro']['fim_ano'] : '-' ?></p></div>
                </div>
                <?php if (!empty($relatorio['quadro']['observacao'])): ?>
                    <div class="alert alert-warning mb-4"><?= htmlspecialchars((string) $relatorio['quadro']['observacao']) ?></div>
                <?php endif; ?>
                <?php if (!empty($relatorio['quadro']['movimentacao'])): ?>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach (['filiacoes' => 'Filiações', 'regularizacoes' => 'Regularizações', 'reintegracoes' => 'Reintegrações', 'suspensoes' => 'Suspensões', 'desligamentos' => 'Desligamentos', 'oriente_eterno' => 'Oriente Eterno'] as $chave => $label): ?>
                            <div class="list-item !py-2 !px-3"><span><?= htmlspecialchars($label) ?></span><strong><?= (int) ($relatorio['quadro']['movimentacao'][$chave] ?? 0) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Perfil do Quadro</h3><p class="card-description">Recorte estatístico do cadastro de obreiros.</p></div>
            <div class="card-body space-y-6">
                <div>
                    <h4 class="font-semibold mb-3 text-sm">Escolaridade</h4>
                    <div class="space-y-2">
                        <?php foreach (($relatorio['perfil_quadro']['escolaridade'] ?? []) as $linha): ?>
                            <div class="list-item"><span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($linha['categoria'] ?? 'N/A')))) ?></span><strong><?= (int) ($linha['total'] ?? 0) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3 text-sm">Situação no Quadro</h4>
                    <div class="space-y-2">
                        <?php foreach (($relatorio['perfil_quadro']['situacoes'] ?? []) as $linha): ?>
                            <div class="list-item"><span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($linha['categoria'] ?? 'N/A')))) ?></span><strong><?= (int) ($linha['total'] ?? 0) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mb-3 text-sm">Distribuição por Grau</h4>
                    <div class="space-y-2">
                        <?php foreach (($relatorio['perfil_quadro']['graus'] ?? []) as $linha): ?>
                            <div class="list-item"><span><?= htmlspecialchars((string) ($linha['categoria'] ?? 'N/A')) ?></span><strong><?= (int) ($linha['total'] ?? 0) ?></strong></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Leitura Administrativa</h3></div>
            <div class="card-body">
                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-500">
                    <li>Visitantes refletem os registros estruturados no balaustre.</li>
                    <li>Visitas externas refletem os registros feitos no saco de propostas.</li>
                    <li>Congressos e palestras são contabilizados a partir dos eventos informados no balaustre.</li>
                    <li>Sessões por grau usam as sessões do período com status diferente de 'cancelada'.</li>
                    <li>O quadro anual depende da trilha cadastral dos obreiros; a precisão do indicador melhora com a disciplina de cadastro.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
