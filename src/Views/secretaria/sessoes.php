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
$formatInputDateTime = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($valor))->format('Y-m-d\TH:i');
    } catch (Throwable) {
        return $valor;
    }
};
$badgeStatusSessao = static fn (?string $status): string => in_array($status, ['publicada', 'confirmada', 'ativa', 'alterada'], true) ? 'badge-success' : (($status === 'cancelada') ? 'badge-danger' : 'badge-warning');
$rascunhoEditaSessaoExistente = is_array($sessaoRascunho ?? null) && !empty($sessaoRascunho['id']);

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Sessões';
$appShellDescription = 'Criação, revisão, publicação e manutenção da agenda oficial da Loja.';
$appShellActiveHref = '/secretaria/sessoes';
require __DIR__ . '/_sidebar.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-8 depth-1"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-8 depth-1"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2 space-y-8">
        <?php if ($resumoRascunhoSessao): ?>
            <div class="card depth-1">
                <div class="card-header border-b border-erp-border/50 p-6">
                    <h2 class="text-xl font-black text-erp-navy tracking-tight"><?= $rascunhoEditaSessaoExistente ? 'Revisão final da atualização' : 'Revisão final da nova sessão' ?></h2>
                    <p class="text-sm text-erp-muted mt-1 font-medium">Confirme o texto que será publicado na agenda e usado pelos fluxos de presença, ágape e balaústre.</p>
                </div>
                <div class="card-body p-6 space-y-6">
                    <?php if ($sessaoDuplicada): ?><span class="badge badge-warning text-[10px] font-black uppercase tracking-widest">Sessão semelhante encontrada</span><?php endif; ?>
                    <pre class="whitespace-pre-wrap text-sm font-mono text-erp-navy bg-erp-surface-2 rounded-2xl p-6 border border-erp-border/30"><?= htmlspecialchars((string) $resumoRascunhoSessao) ?></pre>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($acoesConfirmacaoRascunho as $acaoRascunho): ?>
                            <span class="badge bg-erp-navy/5 text-erp-navy border border-erp-navy/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest"><?= htmlspecialchars((string) ($acaoRascunho['label'] ?? '')) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <form method="POST" action="/secretaria/sessoes/publicar-rascunho"><button type="submit" class="btn btn-primary px-8 py-3"><?= $rascunhoEditaSessaoExistente ? 'Confirmar Atualização' : 'Confirmar Publicação' ?></button></form>
                        <form method="POST" action="/secretaria/sessoes/cancelar-rascunho"><button type="submit" class="btn btn-secondary px-8 py-3">Descartar Revisão</button></form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h2 class="text-xl font-black text-erp-navy tracking-tight"><?= $modoEdicaoSessao ? 'Editar sessão' : 'Nova sessão' ?></h2>
                <p class="text-sm text-erp-muted mt-1 font-medium">A publicação alimenta Agenda/Mural, confirmações, ágape, balaústre e relatórios.</p>
            </div>
            <div class="card-body p-6">
                <form method="POST" action="/secretaria/sessoes/salvar" class="space-y-6">
                    <?php if ($modoEdicaoSessao): ?><input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFormulario['id'] ?? 0) ?>"><?php endif; ?>
                    <div>
                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Título da sessão</label>
                        <input type="text" name="titulo" required value="<?= htmlspecialchars((string) ($sessaoEmFormulario['titulo'] ?? '')) ?>" class="form-input shadow-sm !bg-white">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Data e hora de início</label><input type="datetime-local" name="data_hora_inicio" required value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_inicio'] ?? null) ?>" class="form-input shadow-sm !bg-white"></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Encerramento previsto</label><input type="datetime-local" name="data_hora_fim" value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_fim'] ?? null) ?>" class="form-input shadow-sm !bg-white"></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Grau</label><select name="grau_sessao" class="form-select shadow-sm !bg-white"><?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Outro'] as $grau): ?><option value="<?= $grau ?>" <?= (($sessaoEmFormulario['grau_sessao'] ?? '') === $grau) ? 'selected' : '' ?>><?= $grau ?></option><?php endforeach; ?></select></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Grau livre</label><input name="grau_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['grau_personalizado'] ?? '')) ?>" class="form-input shadow-sm !bg-white"></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Tipo principal</label><select name="tipo_sessao_principal" class="form-select shadow-sm !bg-white"><option value="economica" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? 'economica') === 'economica') ? 'selected' : '' ?>>Econômica</option><option value="magna" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'magna') ? 'selected' : '' ?>>Magna</option><option value="outra" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'outra') ? 'selected' : '' ?>>Outra</option></select></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Subtipo</label><select name="tipo_sessao_subtipo" class="form-select shadow-sm !bg-white"><?php foreach (['economica_1' => 'Econômica de 1º Grau', 'economica_2' => 'Econômica de 2º Grau', 'economica_3' => 'Econômica de 3º Grau', 'magna_iniciacao' => 'Magna de Iniciação', 'magna_elevacao' => 'Magna de Elevação', 'magna_exaltacao' => 'Magna de Exaltação', 'magna_instalacao' => 'Magna de Instalação', 'outra' => 'Outra'] as $valor => $label): ?><option value="<?= $valor ?>" <?= (($sessaoEmFormulario['tipo_sessao_subtipo'] ?? '') === $valor) ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                        <div class="md:col-span-2"><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Tipo livre</label><input name="tipo_sessao_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['tipo_sessao_personalizado'] ?? '')) ?>" class="form-input shadow-sm !bg-white"></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Traje</label><select name="traje_tipo" class="form-select shadow-sm !bg-white"><option value="maconico" <?= (($sessaoEmFormulario['traje_tipo'] ?? 'maconico') === 'maconico') ? 'selected' : '' ?>>Maçônico</option><option value="livre" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'livre') ? 'selected' : '' ?>>Livre</option><option value="outro" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'outro') ? 'selected' : '' ?>>Outro</option></select></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Traje livre</label><input name="traje_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['traje_personalizado'] ?? '')) ?>" class="form-input shadow-sm !bg-white"></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Ágape</label><select name="agape_modalidade" class="form-select shadow-sm !bg-white"><option value="nao_havera" <?= (($sessaoEmFormulario['agape_modalidade'] ?? 'nao_havera') === 'nao_havera') ? 'selected' : '' ?>>Não haverá</option><option value="gratuito" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'gratuito') ? 'selected' : '' ?>>Sim, gratuito</option><option value="pago" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'pago') ? 'selected' : '' ?>>Sim, pago</option></select></div>
                        <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Valor do ágape</label><input name="agape_valor" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['agape_valor'] ?? '')) ?>" class="form-input shadow-sm !bg-white"></div>
                    </div>
                    <div><label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Ordem do dia / observações</label><textarea name="ordem_dia" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($sessaoEmFormulario['ordem_dia'] ?? '')) ?></textarea></div>
                    <label class="flex items-center gap-3 bg-erp-surface-2 p-4 rounded-xl border border-erp-border/30 text-sm font-bold text-erp-navy"><input type="checkbox" name="conta_relatorio_potencia" value="1" class="form-checkbox h-5 w-5 rounded text-erp-navy" <?= !array_key_exists('conta_relatorio_potencia', $sessaoEmFormulario) || !empty($sessaoEmFormulario['conta_relatorio_potencia']) ? 'checked' : '' ?>>Contabilizar no relatório oficial da potência</label>
                    <button type="submit" class="btn btn-primary px-10 py-4"><?= $modoEdicaoSessao ? 'Revisar Atualização' : 'Continuar para Revisão' ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6"><h2 class="text-lg font-black text-erp-navy tracking-tight">Agenda de sessões</h2></div>
            <div class="card-body p-6 space-y-4">
                <?php foreach ($sessoes as $sessao): ?>
                    <div class="bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/30">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="font-black text-erp-navy"><?= htmlspecialchars((string) ($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessao['grau_sessao'] ?? '')))) ?></p><p class="text-xs font-bold text-erp-muted mt-1"><?= $formatDateTime($sessao['data_hora_inicio'] ?? null) ?></p></div>
                            <span class="badge <?= $badgeStatusSessao($sessao['status'] ?? null) ?> text-[9px] font-black uppercase tracking-widest"><?= htmlspecialchars((string) ($sessao['status'] ?: 'planejada')) ?></span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="/secretaria/sessoes?editar_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm bg-white border-erp-border/50 text-[10px] font-black uppercase tracking-widest px-4 py-2">Editar</a>
                            <a href="/secretaria/balaustres?sessao_resumo=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm bg-white border-erp-border/50 text-[10px] font-black uppercase tracking-widest px-4 py-2">Preparar Balaústre</a>
                            <?php if (in_array($sessao['status'] ?? '', ['planejada', 'alterada'], true)): ?><form method="POST" action="/secretaria/sessoes/publicar"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button class="btn btn-sm bg-erp-gold text-erp-navy border-none text-[10px] font-black uppercase tracking-widest px-4 py-2">Publicar</button></form><?php endif; ?>
                            <?php if (($sessao['status'] ?? '') !== 'cancelada'): ?><form method="POST" action="/secretaria/sessoes/cancelar"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button class="btn btn-sm bg-danger/10 text-danger border-none text-[10px] font-black uppercase tracking-widest px-4 py-2">Cancelar</button></form><?php else: ?><form method="POST" action="/secretaria/sessoes/reabrir"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button class="btn btn-sm bg-erp-success/10 text-erp-success border-none text-[10px] font-black uppercase tracking-widest px-4 py-2">Reabrir</button></form><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($sessoes)): ?><p class="text-sm text-erp-muted font-medium">Nenhuma sessão futura cadastrada.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
