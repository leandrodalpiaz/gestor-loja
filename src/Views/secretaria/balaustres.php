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
    .form-input.\!bg-white,
    .form-select.\!bg-white,
    .form-textarea.\!bg-white {
        background-color: #fff !important;
        color: var(--erp-text) !important;
        border-color: var(--erp-border) !important;
    }
</style>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-8 depth-1"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-8 depth-1"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2 space-y-8">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h2 class="text-xl font-black text-erp-navy tracking-tight">Fonte do balaústre</h2>
                <p class="text-sm text-erp-muted mt-1 font-medium">Vincule uma sessão para consumir dados da agenda ou redija de forma independente.</p>
            </div>
            <div class="card-body p-6">
                <form method="GET" action="/secretaria/balaustres" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Sessão vinculada</label>
                        <select name="sessao_resumo" class="form-select shadow-sm !bg-white">
                            <option value="0">Balaústre independente</option>
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= (int) ($sessaoResumo['id'] ?? 0) === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end"><button type="submit" class="btn btn-primary w-full py-3">Carregar</button></div>
                </form>
            </div>
        </div>

        <form method="POST" action="/secretaria/balaustres/salvar" class="space-y-8">
            <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoResumo['id'] ?? 0) ?>">
            <input type="hidden" name="balaustre_id" value="<?= (int) ($balaustreSessao['id'] ?? 0) ?>">
            <?php if (!empty($modoBalaustreIndependente)): ?><input type="hidden" name="balaustre_independente" value="1"><?php endif; ?>

            <div class="card depth-1">
                <div class="card-header border-b border-erp-border/50 p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-erp-navy tracking-tight"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?? 'Balaústre independente')) ?></h2>
                            <p class="text-sm text-erp-muted mt-1 font-medium"><?= $formatDateTime($sessaoResumo['data_hora_inicio'] ?? null) ?> · <?= (int) ($sessaoResumo['total_confirmados'] ?? 0) ?> confirmações</p>
                        </div>
                        <?php if (!empty($balaustreSessao)): ?><span class="badge badge-warning text-[10px] font-black uppercase tracking-widest"><?= htmlspecialchars((string) ($balaustreSessao['status'] ?? 'rascunho')) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-6 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Número do balaústre</label><input name="numero_balaustre" value="<?= htmlspecialchars((string) ($balaustreSessao['numero_balaustre'] ?? '')) ?>" class="form-input shadow-sm !bg-white"></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Modelo</label><input name="template_versao" value="<?= htmlspecialchars((string) ($balaustreSessao['template_versao'] ?? 'oficial-v1')) ?>" class="form-input shadow-sm !bg-white"></div>
                    </div>

                    <div class="space-y-6">
                        <?php foreach ([
                            'abertura' => 'Abertura',
                            'balaustre' => 'Balaústre anterior',
                            'expediente' => 'Expediente',
                            'saco_propostas' => 'Saco de Propostas e Informações',
                            'ordem_dia' => 'Ordem do Dia',
                            'tronco_solidariedade' => 'Tronco de Solidariedade',
                            'conclusoes_orador' => 'Conclusões do Orador',
                            'encerramento' => 'Encerramento',
                            'assinaturas' => 'Assinaturas',
                        ] as $campo => $label): ?>
                            <div>
                                <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1"><?= htmlspecialchars($label) ?></label>
                                <textarea name="bloco_<?= htmlspecialchars($campo) ?>" rows="<?= $campo === 'ordem_dia' ? 8 : 4 ?>" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocos[$campo] ?? ($campo === 'assinaturas' ? 'Secretário              Guarda da Lei              Venerável Mestre' : ''))) ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card depth-1">
                <div class="card-header border-b border-erp-border/50 p-6"><h2 class="text-xl font-black text-erp-navy tracking-tight">Cargos, presenças e palavra</h2></div>
                <div class="card-body p-6 space-y-8">
                    <div class="space-y-4">
                        <h3 class="text-sm font-black text-erp-navy uppercase tracking-widest">Cargos e ocupantes</h3>
                        <?php foreach ($cargosBalaustreSessao as $cargoSessao): ?>
                            <div class="bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/30 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <input type="hidden" name="cargo_sessao_codigo[]" value="<?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?>">
                                <input type="hidden" name="cargo_sessao_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '')) ?>">
                                <input type="hidden" name="cargo_sessao_titular_oficial[]" value="<?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '')) ?>">
                                <div><span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Cargo</span><p class="text-xs font-black text-erp-navy"><?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '-')) ?></p></div>
                                <div><span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Titular</span><p class="text-xs font-black text-erp-navy"><?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '-')) ?></p></div>
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Ocupante</label><input name="cargo_sessao_ocupante_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['ocupante_nome'] ?? $cargoSessao['titular_oficial'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white"></div>
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Obs.</label><input name="cargo_sessao_observacao[]" value="<?= htmlspecialchars((string) ($cargoSessao['observacao'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-sm font-black text-erp-navy uppercase tracking-widest">Fizeram uso da palavra · quadro</h3>
                        <?php foreach ($linhasObreiros as $palavraObreiro): ?>
                            <div class="bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/30 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Obreiro</label><select name="palavra_obreiro_id[]" class="form-select !py-1.5 !text-xs !bg-white"><option value="">Selecionar...</option><?php foreach ($obreiros as $obreiro): ?><option value="<?= htmlspecialchars((string) ($obreiro['id'] ?? '')) ?>" <?= (string) ($palavraObreiro['obreiro_id'] ?? '') === (string) ($obreiro['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars((string) ($obreiro['nome'] ?? 'Obreiro')) ?></option><?php endforeach; ?></select></div>
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Nome manual/cargo</label><input name="palavra_obreiro_nome[]" value="<?= htmlspecialchars((string) ($palavraObreiro['nome'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white"><input name="palavra_obreiro_cargo[]" value="<?= htmlspecialchars((string) ($palavraObreiro['cargo_no_momento'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white mt-2" placeholder="Cargo no momento"></div>
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Transcrição/resumo</label><textarea name="palavra_obreiro_fala[]" rows="3" class="form-textarea !text-xs !bg-white"><?= htmlspecialchars((string) ($palavraObreiro['fala_resumida'] ?? '')) ?></textarea></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-sm font-black text-erp-navy uppercase tracking-widest">Fizeram uso da palavra · visitantes</h3>
                        <?php foreach ($linhasVisitantes as $visitante): ?>
                            <div class="bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/30 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Visitante</label><input name="palavra_visitante_nome[]" value="<?= htmlspecialchars((string) ($visitante['nome'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white"><input name="palavra_visitante_grau[]" value="<?= htmlspecialchars((string) ($visitante['grau'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white mt-2" placeholder="Grau"></div>
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Loja/oriente</label><input name="palavra_visitante_loja[]" value="<?= htmlspecialchars((string) ($visitante['loja'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white"><input name="palavra_visitante_oriente[]" value="<?= htmlspecialchars((string) ($visitante['oriente'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white mt-2" placeholder="Oriente"><input type="hidden" name="palavra_visitante_potencia[]" value="<?= htmlspecialchars((string) ($visitante['potencia'] ?? '')) ?>"><input type="hidden" name="palavra_visitante_dia_reuniao[]" value="<?= htmlspecialchars((string) ($visitante['dia_reuniao'] ?? '')) ?>"></div>
                                <div><label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Transcrição/resumo</label><textarea name="palavra_visitante_fala[]" rows="3" class="form-textarea !text-xs !bg-white"><?= htmlspecialchars((string) ($visitante['fala_resumida'] ?? '')) ?></textarea></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-4">
                <button type="submit" class="btn btn-primary px-10 py-4">Salvar rascunho oficial</button>
                <?php if (!empty($balaustreSessao['id'])): ?><a href="/secretaria/balaustres/visualizar?id=<?= (int) $balaustreSessao['id'] ?>" class="btn btn-secondary px-10 py-4">Prévia Oficial</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6"><h2 class="text-lg font-black text-erp-navy tracking-tight">Prévia oficial</h2></div>
            <div class="card-body p-6">
                <pre class="whitespace-pre-wrap text-sm font-mono text-erp-navy bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/30 max-h-[520px] overflow-y-auto"><?= htmlspecialchars($previewTextoOficialBalaustre !== '' ? $previewTextoOficialBalaustre : 'Salve o rascunho para gerar a prévia oficial.') ?></pre>
                <?php if (!empty($balaustreSessao['id'])): ?>
                    <form method="POST" action="/secretaria/balaustres/apto" class="mt-4"><input type="hidden" name="balaustre_id" value="<?= (int) $balaustreSessao['id'] ?>"><button type="submit" class="btn btn-primary w-full py-3">Marcar apto para votação</button></form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6"><h2 class="text-lg font-black text-erp-navy tracking-tight">Recentes</h2></div>
            <div class="card-body p-6 space-y-3">
                <?php foreach ($balaustres as $item): ?>
                    <a href="/secretaria/balaustres<?= !empty($item['sessao_id']) ? '?sessao_resumo=' . (int) $item['sessao_id'] : '?balaustre_sem_sessao=1' ?>" class="block bg-erp-surface-2 rounded-xl p-4 border border-erp-border/30">
                        <p class="text-sm font-black text-erp-navy"><?= htmlspecialchars((string) ($item['numero_balaustre'] ?: 'Sem número')) ?></p>
                        <p class="text-xs font-bold text-erp-muted"><?= htmlspecialchars((string) ($item['sessao_titulo'] ?? 'Independente')) ?> · <?= htmlspecialchars((string) ($item['status'] ?? 'rascunho')) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
