<?php
$erpPageTitle = 'Relatorio Anual - Secretaria';
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Relatorio anual';
$appShellDescription = 'Consolidação anual da atividade da Loja sob responsabilidade da Secretaria.';
$appShellActiveHref = '/secretaria/relatorio-anual';
$appShellActions = [
    ['label' => 'Voltar para Secretaria', 'href' => '/secretaria'],
];
$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Painel da Secretaria', 'href' => '/secretaria'],
            ['label' => 'Votação de balaustre', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatorio anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
        ],
    ],
    [
        'title' => 'Navegacao',
        'items' => [
            ['label' => 'Dashboard', 'href' => '/dashboard'],
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
        ],
    ],
];
require __DIR__ . '/../partials/erp_head.php';
?>
<?php require __DIR__ . '/../partials/erp_shell_open.php'; ?>
<div class="space-y-6">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="text-xs uppercase tracking-[0.24em] text-slate-700">Identificacao institucional</div>
                    <h2 class="mt-2 text-2xl font-semibold text-erp-navy">
                        <?= htmlspecialchars((string) (($relatorio['loja']['nome_loja'] ?? '') !== '' ? $relatorio['loja']['nome_loja'] : 'Loja não configurada')) ?>
                    </h2>
                    <p class="mt-2 text-sm text-slate-700">
                        Potencia: <?= htmlspecialchars((string) (($relatorio['loja']['potencia_nome'] ?? '') !== '' ? $relatorio['loja']['potencia_nome'] : 'não informada')) ?>
                        <?php if (!empty($relatorio['loja']['potencia_sigla'])): ?>
                            (<?= htmlspecialchars((string) $relatorio['loja']['potencia_sigla']) ?>)
                        <?php endif; ?>
                    </p>
                    <p class="mt-1 text-sm text-slate-700">
                        Oriente: <?= htmlspecialchars((string) (($relatorio['loja']['oriente'] ?? '') !== '' ? $relatorio['loja']['oriente'] : 'não informado')) ?>
                        <?php if (!empty($relatorio['loja']['cidade']) || !empty($relatorio['loja']['uf'])): ?>
                            | Cidade: <?= htmlspecialchars(trim((string) (($relatorio['loja']['cidade'] ?? '') . ' / ' . ($relatorio['loja']['uf'] ?? '')), ' /')) ?>
                        <?php endif; ?>
                    </p>
                    <p class="mt-1 text-sm text-slate-700">
                        Rito: <?= htmlspecialchars((string) (($relatorio['loja']['rito'] ?? '') !== '' ? $relatorio['loja']['rito'] : 'não informado')) ?>
                        | Fundação: <?= htmlspecialchars((string) (($relatorio['loja']['data_fundacao'] ?? '') !== '' ? $relatorio['loja']['data_fundacao'] : 'não informada')) ?>
                        | Instalação: <?= htmlspecialchars((string) (($relatorio['loja']['data_instalacao'] ?? '') !== '' ? $relatorio['loja']['data_instalacao'] : 'não informada')) ?>
                    </p>
                </div>
                <?php if (!empty($relatorio['loja']['observacao_relatorios'])): ?>
                    <div class="max-w-xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <?= htmlspecialchars((string) $relatorio['loja']['observacao_relatorios']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
            <form method="GET" action="/secretaria/relatorio-anual" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label class="block text-sm font-medium mb-1">Ano de referencia</label>
                    <select name="ano" class="rounded-lg border border-slate-300 px-3 py-2">
                        <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                            <option value="<?= (int) $anoOpcao ?>" <?= (int) $anoOpcao === (int) $relatorio['ano'] ? 'selected' : '' ?>>
                                <?= (int) $anoOpcao ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-sm font-medium text-white">Atualizar relatorio</button>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-5 mb-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Visitantes no periodo</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) ($relatorio['visitantes']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Visitas a outras Lojas</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) ($relatorio['visitas_externas']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Congressos</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Palestras</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Sessões no período</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) ($relatorio['sessoes_por_grau']['total'] ?? 0) ?></div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3 mb-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Obreiros no quadro</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= (int) ($relatorio['perfil_quadro']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Idade media</div>
                <div class="mt-2 text-3xl font-semibold text-erp-navy"><?= htmlspecialchars((string) (($relatorio['perfil_quadro']['idade_media'] ?? null) !== null ? $relatorio['perfil_quadro']['idade_media'] : '-')) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Situacao predominante</div>
                <div class="mt-2 text-2xl font-semibold text-erp-navy">
                    <?= htmlspecialchars((string) (($relatorio['perfil_quadro']['situacoes'][0]['categoria'] ?? 'nao_informado'))) ?>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Visitantes</h2>
                    <p class="mt-1 text-sm text-slate-700">Quantidade total de visitantes extraida dos registros estruturados da palavra a bem da ordem no balaustre.</p>
                    <div class="mt-4 text-xs text-slate-700">Fonte: <?= htmlspecialchars((string) ($relatorio['visitantes']['fonte'] ?? '')) ?></div>

                    <div class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Lojas mais frequentes</h3>
                        <div class="space-y-2">
                            <?php foreach (($relatorio['visitantes']['lojas_mais_frequentes'] ?? []) as $linha): ?>
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <span><?= htmlspecialchars((string) ($linha['loja'] ?? 'Loja não informada')) ?></span>
                                    <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <?php if (($relatorio['visitantes']['lojas_mais_frequentes'] ?? []) === []): ?>
                                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Não há visitantes estruturados no período selecionado.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Visitas a outras Lojas</h2>
                    <p class="mt-1 text-sm text-slate-700">Quantidade de vezes em que membros do quadro da Loja informaram visitas externas no saco de propostas.</p>
                    <div class="mt-4 text-xs text-slate-700">Fonte: <?= htmlspecialchars((string) ($relatorio['visitas_externas']['fonte'] ?? '')) ?></div>

                    <div class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Lojas mais visitadas</h3>
                        <div class="space-y-2">
                            <?php foreach (($relatorio['visitas_externas']['lojas_mais_visitadas'] ?? []) as $linha): ?>
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <span><?= htmlspecialchars((string) ($linha['loja'] ?? 'Loja não informada')) ?></span>
                                    <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <?php if (($relatorio['visitas_externas']['lojas_mais_visitadas'] ?? []) === []): ?>
                                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Não há visitas externas estruturadas no período selecionado.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Sessoes por grau</h2>
                    <p class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($relatorio['sessoes_por_grau']['regra'] ?? '')) ?></p>
                    <div class="mt-5 space-y-2">
                        <?php foreach (($relatorio['sessoes_por_grau']['itens'] ?? []) as $linha): ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                <span><?= htmlspecialchars((string) ($linha['grau_sessao'] ?? 'Não informado')) ?></span>
                                <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <?php if (($relatorio['sessoes_por_grau']['itens'] ?? []) === []): ?>
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Não há sessões no período selecionado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Congressos e palestras</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-700">Congressos realizados</div>
                            <div class="mt-2 text-2xl font-semibold text-erp-navy"><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></div>
                            <div class="mt-2 text-xs text-slate-700">Fonte: <?= htmlspecialchars((string) ($relatorio['congressos']['fonte'] ?? '')) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-700">Palestras realizadas</div>
                            <div class="mt-2 text-2xl font-semibold text-erp-navy"><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></div>
                            <div class="mt-2 text-xs text-slate-700">Fonte: <?= htmlspecialchars((string) ($relatorio['palestras']['fonte'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Quadro da Loja</h2>
                    <p class="mt-1 text-sm text-slate-700">Panorama anual da composicao do quadro com base na trilha cadastral disponivel hoje.</p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-700">Comecaram o ano no quadro</div>
                            <div class="mt-2 text-2xl font-semibold text-erp-navy"><?= htmlspecialchars((string) (($relatorio['quadro']['inicio_ano'] ?? null) !== null ? $relatorio['quadro']['inicio_ano'] : '-')) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-700">Terminaram o ano no quadro</div>
                            <div class="mt-2 text-2xl font-semibold text-erp-navy"><?= htmlspecialchars((string) (($relatorio['quadro']['fim_ano'] ?? null) !== null ? $relatorio['quadro']['fim_ano'] : '-')) ?></div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <?= htmlspecialchars((string) ($relatorio['quadro']['observacao'] ?? '')) ?>
                    </div>

                    <?php if (($relatorio['quadro']['movimentacao'] ?? null) !== null): ?>
                        <div class="mt-5">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Movimentacao do quadro</h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <?php foreach ([
                                    'filiacoes' => 'Filiacoes',
                                    'regularizacoes' => 'Regularizacoes',
                                    'reintegracoes' => 'Reintegracoes',
                                    'suspensoes' => 'Suspensoes',
                                    'desligamentos' => 'Desligamentos',
                                    'oriente_eterno' => 'Oriente Eterno',
                                ] as $chave => $label): ?>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm flex items-center justify-between">
                                        <span><?= htmlspecialchars($label) ?></span>
                                        <strong><?= (int) ($relatorio['quadro']['movimentacao'][$chave] ?? 0) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Leitura administrativa</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700 list-disc pl-5">
                        <li>Visitantes refletem os registros estruturados no balaustre.</li>
                        <li>Visitas externas refletem os registros feitos no saco de propostas durante a sessão.</li>
                        <li>Congressos e palestras sao contabilizados a partir dos eventos informados no balaustre.</li>
                        <li>Sessoes por grau usam as sessões do período com status diferente de cancelada.</li>
                        <li>O quadro anual depende da trilha cadastral dos obreiros; quanto melhor a disciplina de cadastro, melhor a precisao do indicador.</li>
                    </ul>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Perfil do quadro</h2>
                    <p class="mt-1 text-sm text-slate-700">Recorte estatistico do cadastro de obreiros utilizado para relatorios da gestao e relatorios por irmao.</p>

                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Escolaridade</h3>
                            <div class="space-y-2">
                                <?php foreach (($relatorio['perfil_quadro']['escolaridade'] ?? []) as $linha): ?>
                                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                        <span><?= htmlspecialchars((string) ($linha['categoria'] ?? 'nao_informado')) ?></span>
                                        <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Situacao do quadro</h3>
                            <div class="space-y-2">
                                <?php foreach (($relatorio['perfil_quadro']['situacoes'] ?? []) as $linha): ?>
                                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                        <span><?= htmlspecialchars((string) ($linha['categoria'] ?? 'nao_informado')) ?></span>
                                        <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Distribuicao por grau</h3>
                            <div class="space-y-2">
                                <?php foreach (($relatorio['perfil_quadro']['graus'] ?? []) as $linha): ?>
                                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                        <span><?= htmlspecialchars((string) ($linha['categoria'] ?? 'Não informado')) ?></span>
                                        <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-erp-navy">Amostra cadastral</h2>
                    <p class="mt-1 text-sm text-slate-700">Leitura rapida por irmao para apoio ao saneamento cadastral e conferencias do relatorio.</p>

                    <div class="mt-5 space-y-3">
                        <?php foreach (($relatorio['perfil_quadro']['amostra_cadastral'] ?? []) as $item): ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-medium text-erp-navy"><?= htmlspecialchars((string) ($item['nome_exibicao'] ?? '-')) ?></div>
                                    <div class="text-xs uppercase tracking-wide text-slate-700"><?= htmlspecialchars((string) ($item['situacao_quadro'] ?? 'nao_informado')) ?></div>
                                </div>
                                <div class="mt-2 text-sm text-slate-700">
                                    Grau: <?= htmlspecialchars((string) ($item['grau'] ?? 'Não informado')) ?> |
                                    Escolaridade: <?= htmlspecialchars((string) ($item['escolaridade'] ?? 'nao_informado')) ?> |
                                    Profissao: <?= htmlspecialchars((string) ($item['profissao'] ?? 'nao_informada')) ?>
                                </div>
                                <div class="mt-1 text-xs text-slate-700">
                                    Filiacao: <?= htmlspecialchars((string) ($item['data_filiacao'] ?? '-')) ?> |
                                    Regularizacao: <?= htmlspecialchars((string) ($item['data_regularizacao'] ?? '-')) ?> |
                                    Reintegracao: <?= htmlspecialchars((string) ($item['data_reintegracao'] ?? '-')) ?> |
                                    Desligamento: <?= htmlspecialchars((string) ($item['data_desligamento'] ?? '-')) ?> |
                                    Oriente Eterno: <?= htmlspecialchars((string) ($item['data_oriente_eterno'] ?? '-')) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (($relatorio['perfil_quadro']['amostra_cadastral'] ?? []) === []): ?>
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Não há obreiros elegíveis no período selecionado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
</div>
<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
