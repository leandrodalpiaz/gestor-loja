<?php
declare(strict_types=1);

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$formatDateTime = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($valor))->format('d/m/Y H:i');
    } catch (Throwable) {
        return $valor;
    }
};
$linhasObreiros = array_pad($palavrasObreirosBalaustre ?? [], max(3, count($palavrasObreirosBalaustre ?? [])), []);
$linhasVisitantes = array_pad($visitantesBalaustre ?? [], max(3, count($visitantesBalaustre ?? [])), []);
$blocos = is_array($blocosBalaustre ?? null) ? $blocosBalaustre : [];

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Balaústres';
$appShellDescription = 'Redação oficial por blocos, prévia canônica e encaminhamento para votação.';
$appShellActiveHref = '/secretaria/balaustres';
require __DIR__ . '/_sidebar.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<style>
    /* Modo Pergaminho (Para a Prévia) */
    .parchment-view {
        background: #e8dcc4;
        background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.8%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22 opacity=%220.06%22/%3E%3C/svg%3E');
        box-shadow: inset 0 0 60px rgba(139, 69, 19, 0.15), 0 10px 25px rgba(0,0,0,0.3);
        border: 1px solid #c2a77d;
        border-radius: 4px;
        color: #3e2e1c;
        position: relative;
    }
    .parchment-view::before {
        content: '';
        position: absolute;
        inset: 4px;
        border: 1px solid rgba(139, 69, 19, 0.1);
        pointer-events: none;
    }
</style>

<div class="balaustre-container">
    <?php if ($mensagemSucesso): ?>
        <div class="mb-8 p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 font-bold text-sm shadow-lg"><?= htmlspecialchars($mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="mb-8 p-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 font-bold text-sm shadow-lg"><?= htmlspecialchars($mensagemErro) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <!-- Lado Esquerdo: Formulários e Edição em Blocos -->
        <div class="xl:col-span-7 space-y-6">
            
            <!-- Seleção de Fonte (Sessão Vinculada) -->
            <div class="card">
                <div class="card-header">
                    <h2 class="font-cinzel text-xl text-white tracking-widest uppercase">Fonte do Balaústre</h2>
                    <p class="text-xs text-slate-400 mt-1">Vincule uma sessão para herdar dados ou redija um documento independente.</p>
                </div>
                <div class="p-6">
                    <form method="GET" action="/secretaria/balaustres" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="form-label">Sessão Vinculada</label>
                            <select name="sessao_resumo" class="form-select">
                                <option value="0" class="text-black">Balaústre independente</option>
                                <?php foreach ($sessoes as $sessaoOpcao): ?>
                                    <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= (int) ($sessaoResumo['id'] ?? 0) === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?> class="text-black">
                                        <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end"><button type="submit" class="btn-secondary w-full py-[11px]">Carregar Dados</button></div>
                    </form>
                </div>
            </div>

            <form method="POST" action="/secretaria/balaustres/salvar" class="space-y-6">
                <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoResumo['id'] ?? 0) ?>">
                <input type="hidden" name="balaustre_id" value="<?= (int) ($balaustreSessao['id'] ?? 0) ?>">
                <?php if (!empty($modoBalaustreIndependente)): ?><input type="hidden" name="balaustre_independente" value="1"><?php endif; ?>

                <!-- Redação por Blocos -->
                <div class="card">
                    <div class="card-header flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="font-cinzel text-xl text-[#C9A227] tracking-widest uppercase"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?? 'Balaústre Independente')) ?></h2>
                            <p class="text-xs text-slate-400 mt-1"><?= $formatDateTime($sessaoResumo['data_hora_inicio'] ?? null) ?> · <?= (int) ($sessaoResumo['total_confirmados'] ?? 0) ?> presenças estimadas</p>
                        </div>
                        <?php if (!empty($balaustreSessao)): ?>
                            <span class="px-3 py-1 bg-white/5 border border-white/10 rounded-full text-[10px] uppercase tracking-widest text-[#C9A227]">
                                <?= htmlspecialchars((string) ($balaustreSessao['status'] ?? 'rascunho')) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Número do Balaústre</label>
                                <input name="numero_balaustre" value="<?= htmlspecialchars((string) ($balaustreSessao['numero_balaustre'] ?? '')) ?>" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Modelo Base</label>
                                <input name="template_versao" value="<?= htmlspecialchars((string) ($balaustreSessao['template_versao'] ?? 'oficial-v1')) ?>" class="form-input">
                            </div>
                        </div>

                        <div class="space-y-6 pt-4 border-t border-white/5">
                            <h3 class="font-cinzel text-sm text-white tracking-widest uppercase mb-4">Estrutura Canônica (Blocos)</h3>
                            <?php
                            $ajudaCampos = [
                                'abertura' => [
                                    'ajuda' => 'Abertura ritualística da sessão, registro de data/hora de início, grau de funcionamento e presidência.',
                                    'placeholder' => 'Ex: Às 20h00, no Templo da A.R.L.S. Renascença, sob a presidência do Venerável Mestre, foram abertos os trabalhos no grau de Aprendiz...'
                                ],
                                'balaustre' => [
                                    'ajuda' => 'Leitura, discussão, votação e assinatura da ata (balaústre) da sessão ordinária anterior.',
                                    'placeholder' => 'Ex: Foi lido o balaústre da sessão anterior de data..., o qual foi posto em discussão e aprovado sem restrições...'
                                ],
                                'expediente' => [
                                    'ajuda' => 'Registro de pranchas (correspondências) recebidas de Lojas Co-Irmãs, circulares da Potência, correspondência expedida e outras comunicações oficiais.',
                                    'placeholder' => 'Ex: Foram lidas as circulares nº 12 e 13 da Potência, prancha de agradecimento da Loja... e convite para a sessão magna da Loja...'
                                ],
                                'saco_propostas' => [
                                    'ajuda' => 'Relatar se o Saco de Propostas e Informações colheu alguma prancha, proposta ou notícia.',
                                    'placeholder' => 'Ex: O Saco de Propostas e Informações circulou e produziu um pedaço de prancha contendo solicitação de auxílio...'
                                ],
                                'ordem_dia' => [
                                    'ajuda' => 'Apresentação de trabalhos escritos (peças de arquitetura), palestras, votações de propostas e debates do dia.',
                                    'placeholder' => 'Ex: O Irmão... apresentou uma rica peça de arquitetura intitulada "O Templo Interior". Em seguida, procedeu-se à votação...'
                                ],
                                'tronco_solidariedade' => [
                                    'ajuda' => 'Registro da circulação do Tronco de Solidariedade, valor total arrecadado e destinação para a Hospitália (beneficência).',
                                    'placeholder' => 'Ex: O Tronco de Solidariedade circulou e arrecadou a importância de R$ 150,00, que foi entregue ao Irmão Hospitaleiro...'
                                ],
                                'conclusoes_orador' => [
                                    'ajuda' => 'Parecer final do Irmão Orador sobre a justeza e a perfeição dos trabalhos realizados na sessão.',
                                    'placeholder' => 'Ex: O Irmão Orador usou da palavra para agradecer a presença dos visitantes e dar o seu parecer legal de que os trabalhos transcorreram justos e perfeitos...'
                                ],
                                'encerramento' => [
                                    'ajuda' => 'Fechamento ritualístico dos trabalhos, agradecimentos finais e encerramento das atividades do dia.',
                                    'placeholder' => 'Ex: Nada mais havendo a tratar, o Venerável Mestre encerrou os trabalhos às 22h00 na forma da lei...'
                                ],
                                'assinaturas' => [
                                    'ajuda' => 'Identificação de quem assina fisicamente ou valida digitalmente o balaústre.',
                                    'placeholder' => 'Ex: Secretário              Guarda da Lei              Venerável Mestre'
                                ],
                            ];
                            ?>
                            <?php foreach ([
                                'abertura' => 'Abertura',
                                'balaustre' => 'Leitura do Balaústre Anterior',
                                'expediente' => 'Expediente',
                                'saco_propostas' => 'Saco de Propostas e Informações',
                                'ordem_dia' => 'Ordem do Dia',
                                'tronco_solidariedade' => 'Tronco de Solidariedade',
                                'conclusoes_orador' => 'Conclusões do Orador',
                                'encerramento' => 'Encerramento',
                                'assinaturas' => 'Assinaturas Oficiais',
                            ] as $campo => $label): 
                                $ajuda = $ajudaCampos[$campo]['ajuda'] ?? '';
                                $place = $ajudaCampos[$campo]['placeholder'] ?? '';
                            ?>
                                <div class="space-y-1">
                                    <label class="form-label mb-0.5"><?= htmlspecialchars($label) ?></label>
                                    <span class="text-[11px] text-slate-400 block mb-1.5"><?= htmlspecialchars($ajuda) ?></span>
                                    <textarea name="bloco_<?= htmlspecialchars($campo) ?>" rows="<?= $campo === 'ordem_dia' ? 8 : 4 ?>" class="form-textarea text-sm" placeholder="<?= htmlspecialchars($place) ?>"><?= htmlspecialchars((string) ($blocos[$campo] ?? ($campo === 'assinaturas' ? 'Secretário              Guarda da Lei              Venerável Mestre' : ''))) ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Gestão de Palavra e Presenças Extraordinárias -->
                <div class="card">
                    <div class="card-header"><h2 class="font-cinzel text-xl text-white tracking-widest uppercase">Uso da Palavra & Ocupantes</h2></div>
                    <div class="p-6 space-y-8">
                        
                        <!-- Ocupantes -->
                        <div class="space-y-4">
                            <h3 class="form-label border-b border-white/5 pb-2">Cargos em Loja</h3>
                            <?php foreach ($cargosBalaustreSessao as $cargoSessao): ?>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/10 grid grid-cols-1 lg:grid-cols-4 gap-4 items-center">
                                    <input type="hidden" name="cargo_sessao_codigo[]" value="<?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?>">
                                    <input type="hidden" name="cargo_sessao_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '')) ?>">
                                    <input type="hidden" name="cargo_sessao_titular_oficial[]" value="<?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '')) ?>">
                                    
                                    <div><span class="text-[9px] text-slate-500 uppercase">Ofício</span><p class="text-sm font-bold text-[#C9A227]"><?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '-')) ?></p></div>
                                    <div><span class="text-[9px] text-slate-500 uppercase">Titular</span><p class="text-xs text-white"><?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '-')) ?></p></div>
                                    <div><label class="text-[9px] text-slate-500 uppercase">Irmão Ocupante</label><input name="cargo_sessao_ocupante_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['ocupante_nome'] ?? $cargoSessao['titular_oficial'] ?? '')) ?>" class="form-input !py-1 !text-xs"></div>
                                    <div><label class="text-[9px] text-slate-500 uppercase">Observações</label><input name="cargo_sessao_observacao[]" value="<?= htmlspecialchars((string) ($cargoSessao['observacao'] ?? '')) ?>" class="form-input !py-1 !text-xs"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Palavra Quadro -->
                        <div class="space-y-4">
                            <h3 class="form-label border-b border-white/5 pb-2">Irmãos do Quadro que Falaram</h3>
                            <?php foreach ($linhasObreiros as $palavraObreiro): ?>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/10 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-[9px] text-slate-500 uppercase">Irmão</label>
                                        <select name="palavra_obreiro_id[]" class="form-select !py-1.5 !text-xs text-black">
                                            <option value="" class="text-black">Selecionar...</option>
                                            <?php foreach ($obreiros as $obreiro): ?>
                                                <option value="<?= htmlspecialchars((string) ($obreiro['id'] ?? '')) ?>" <?= (string) ($palavraObreiro['obreiro_id'] ?? '') === (string) ($obreiro['id'] ?? '') ? 'selected' : '' ?> class="text-black">
                                                    <?= htmlspecialchars((string) ($obreiro['nome'] ?? 'Obreiro')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[9px] text-slate-500 uppercase">Registro Adicional</label>
                                        <input name="palavra_obreiro_nome[]" value="<?= htmlspecialchars((string) ($palavraObreiro['nome'] ?? '')) ?>" class="form-input !py-1.5 !text-xs mb-2" placeholder="Nome avulso">
                                        <input name="palavra_obreiro_cargo[]" value="<?= htmlspecialchars((string) ($palavraObreiro['cargo_no_momento'] ?? '')) ?>" class="form-input !py-1.5 !text-xs" placeholder="Cargo que ocupava">
                                    </div>
                                    <div>
                                        <label class="text-[9px] text-slate-500 uppercase">Resumo da Fala</label>
                                        <textarea name="palavra_obreiro_fala[]" rows="3" class="form-textarea !text-xs"><?= htmlspecialchars((string) ($palavraObreiro['fala_resumida'] ?? '')) ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Palavra Visitantes -->
                        <div class="space-y-4">
                            <h3 class="form-label border-b border-white/5 pb-2">Visitantes que Falaram</h3>
                            <?php foreach ($linhasVisitantes as $visitante): ?>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/10 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="text-[9px] text-slate-500 uppercase">Identificação</label>
                                        <input name="palavra_visitante_nome[]" value="<?= htmlspecialchars((string) ($visitante['nome'] ?? '')) ?>" class="form-input !py-1.5 !text-xs mb-2" placeholder="Nome Completo">
                                        <input name="palavra_visitante_grau[]" value="<?= htmlspecialchars((string) ($visitante['grau'] ?? '')) ?>" class="form-input !py-1.5 !text-xs" placeholder="Grau">
                                    </div>
                                    <div>
                                        <label class="text-[9px] text-slate-500 uppercase">Origem</label>
                                        <input name="palavra_visitante_loja[]" value="<?= htmlspecialchars((string) ($visitante['loja'] ?? '')) ?>" class="form-input !py-1.5 !text-xs mb-2" placeholder="A∴R∴L∴S∴">
                                        <input name="palavra_visitante_oriente[]" value="<?= htmlspecialchars((string) ($visitante['oriente'] ?? '')) ?>" class="form-input !py-1.5 !text-xs" placeholder="Oriente">
                                        <input type="hidden" name="palavra_visitante_potencia[]" value="<?= htmlspecialchars((string) ($visitante['potencia'] ?? '')) ?>">
                                        <input type="hidden" name="palavra_visitante_dia_reuniao[]" value="<?= htmlspecialchars((string) ($visitante['dia_reuniao'] ?? '')) ?>">
                                    </div>
                                    <div>
                                        <label class="text-[9px] text-slate-500 uppercase">Resumo da Fala</label>
                                        <textarea name="palavra_visitante_fala[]" rows="3" class="form-textarea !text-xs"><?= htmlspecialchars((string) ($visitante['fala_resumida'] ?? '')) ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>

                <div class="flex flex-wrap gap-4 pt-4">
                    <button type="submit" class="btn-primary px-10 py-4 text-sm tracking-widest uppercase">Salvar Rascunho Oficial</button>
                    <?php if (!empty($balaustreSessao['id'])): ?>
                        <a href="/secretaria/balaustres/visualizar?id=<?= (int) $balaustreSessao['id'] ?>" class="btn-secondary px-10 py-4 text-sm tracking-widest uppercase text-center">Ver Impressão Limpa</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Lado Direito: Prévia no formato de Pergaminho e Recentes -->
        <div class="xl:col-span-5 space-y-6">
            
            <div class="parchment-view">
                <div class="p-6 border-b border-[#8b4513]/10 flex items-center gap-3">
                    <svg class="w-5 h-5 text-[#8b4513]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <h2 class="font-cinzel text-lg font-bold text-[#5c3a21] tracking-widest uppercase">Prévia do Balaústre</h2>
                </div>
                <div class="p-8 max-h-[800px] overflow-y-auto custom-scrollbar">
                    
                    <?php if ($previewTextoOficialBalaustre !== ''): ?>
                        <!-- Timbrado Oficial (Template) -->
                        <img src="/assets/images/templates/efemerides/balaustre.png" alt="Timbrado Oficial" class="w-full h-auto mb-6 mix-blend-multiply opacity-90 pointer-events-none" onerror="this.style.display='none'">
                        
                        <!-- Cabeçalho Dinâmico e Digitável -->
                        <div class="text-center text-[#2c2014] mb-6" style="font-family: 'Times New Roman', Times, serif;">
                            <p class="font-bold text-[13px] md:text-[14px]">Balaústre nº <?= htmlspecialchars((string) ($balaustreSessao['numero_balaustre'] ?? '___/____')) ?></p>
                            <p class="font-bold text-[13px] md:text-[14px] uppercase underline mt-2 tracking-wide"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?? 'SESSÃO ECONÔMICA DE 1º GRAU')) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="font-serif text-[13px] md:text-[14px] text-[#2c2014] leading-[1.65] text-justify whitespace-pre-wrap" style="font-family: 'Times New Roman', Times, serif;">
                        <?php 
                            if ($previewTextoOficialBalaustre !== '') {
                                echo strip_tags($previewTextoOficialBalaustre, '<b><u><br>');
                            } else {
                                echo 'Redija os blocos à esquerda e salve o rascunho para gerar a prévia do documento canônico e contínuo.';
                            }
                        ?>
                    </div>
                </div>
                <?php if (!empty($balaustreSessao['id'])): ?>
                    <div class="p-6 border-t border-[#8b4513]/10 bg-[#8b4513]/5 rounded-b-4">
                        <form method="POST" action="/secretaria/balaustres/apto">
                            <input type="hidden" name="balaustre_id" value="<?= (int) $balaustreSessao['id'] ?>">
                            <button type="submit" class="w-full py-3 bg-[#5c3a21] text-[#e8dcc4] font-cinzel font-bold tracking-widest uppercase text-xs rounded hover:bg-[#4a2e1a] transition-colors shadow-lg">Carimbar Apto para Votação</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Arquivo Recente -->
            <div class="card">
                <div class="card-header"><h2 class="font-cinzel text-sm text-[#C9A227] tracking-widest uppercase">Balaústres Recentes</h2></div>
                <div class="p-6 space-y-3">
                    <?php if(empty($balaustres)): ?>
                        <p class="text-xs text-slate-500 italic">Nenhum balaústre encontrado.</p>
                    <?php else: ?>
                        <?php foreach ($balaustres as $item): ?>
                            <a href="/secretaria/balaustres<?= !empty($item['sessao_id']) ? '?sessao_resumo=' . (int) $item['sessao_id'] : '?balaustre_sem_sessao=1' ?>" class="block bg-white/5 rounded-xl p-4 border border-white/5 hover:border-[#C9A227]/50 transition-all">
                                <p class="font-cinzel text-sm font-bold text-white mb-1"><?= htmlspecialchars((string) ($item['numero_balaustre'] ?: 'Sem número')) ?></p>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest"><?= htmlspecialchars((string) ($item['sessao_titulo'] ?? 'Independente')) ?> · <span class="text-[#C9A227]"><?= htmlspecialchars((string) ($item['status'] ?? 'rascunho')) ?></span></p>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Custom Scrollbar for Parchment */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(139, 69, 19, 0.05);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(139, 69, 19, 0.2);
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 69, 19, 0.4);
}
</style>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

