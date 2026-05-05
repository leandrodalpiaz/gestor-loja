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
$tipoFiltro = trim((string) ($_GET['tipo'] ?? 'todos'));
$convitesFiltrados = array_values(array_filter($convitesExternos ?? [], static function (array $convite) use ($tipoFiltro): bool {
    return $tipoFiltro === 'todos' || (string) ($convite['tipo'] ?? '') === $tipoFiltro;
}));
$fixados = array_values(array_filter($convitesFiltrados, static fn (array $convite): bool => !empty($convite['fixado'])));
$demais = array_values(array_filter($convitesFiltrados, static fn (array $convite): bool => empty($convite['fixado'])));
$renderConvite = static function (array $convite, array $confirmados) use ($formatDateTime): void {
    $conviteId = (int) ($convite['id'] ?? 0);
    $listaConfirmados = implode("\n", array_map(static function (array $item): string {
        $nome = trim((string) ($item['nome'] ?? 'Irmão'));
        $cim = trim((string) ($item['cim'] ?? ''));
        return $cim !== '' ? $nome . ' - CIM ' . $cim : $nome;
    }, $confirmados));
    $textoCopiar = "Confirmados: " . (string) count($confirmados) . "\n" . $listaConfirmados;
    $temAnexo = !empty($convite['anexo_nome']) && empty($convite['anexo_deleted_at']);
    ?>
    <div class="card depth-1">
        <div class="card-body p-5">
            <div class="flex gap-4">
                <div class="w-20 h-20 rounded-2xl bg-erp-surface-2 border border-erp-border/40 flex items-center justify-center shrink-0">
                    <span class="text-[10px] font-black text-erp-muted uppercase tracking-widest"><?= $temAnexo ? (str_contains((string) ($convite['anexo_mime'] ?? ''), 'pdf') ? 'PDF' : 'IMG') : 'Texto' ?></span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-erp-gold/20 text-erp-navy border border-erp-gold/30 text-[9px] font-black uppercase tracking-widest">Convite Externo</span>
                                <?php if (!empty($convite['fixado'])): ?><span class="badge badge-warning text-[9px] font-black uppercase tracking-widest">Fixado</span><?php endif; ?>
                            </div>
                            <h3 class="font-black text-erp-navy leading-tight"><?= htmlspecialchars((string) ($convite['titulo'] ?? 'Convite')) ?></h3>
                            <p class="text-xs font-bold text-erp-muted mt-1"><?= htmlspecialchars((string) ($convite['loja_origem'] ?? '-')) ?> · <?= $formatDateTime($convite['data_hora'] ?? null) ?> · <?= htmlspecialchars((string) ($convite['cidade'] ?? $convite['local'] ?? '-')) ?></p>
                        </div>
                        <span class="badge bg-erp-navy/5 text-erp-navy border border-erp-navy/10 text-[9px] font-black uppercase tracking-widest"><?= htmlspecialchars((string) ($convite['status'] ?? 'rascunho')) ?></span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <form method="POST" action="/secretaria/convites-externos/presenca"><input type="hidden" name="convite_id" value="<?= $conviteId ?>"><input type="hidden" name="status" value="confirmado"><button class="btn btn-primary !py-2 !px-4 text-[10px] font-black uppercase tracking-widest">Confirmar</button></form>
                        <form method="POST" action="/secretaria/convites-externos/presenca"><input type="hidden" name="convite_id" value="<?= $conviteId ?>"><input type="hidden" name="status" value="cancelado"><button class="btn btn-secondary !py-2 !px-4 text-[10px] font-black uppercase tracking-widest">Cancelar</button></form>
                        <details class="flex-1 min-w-full md:min-w-0">
                            <summary class="btn btn-secondary !py-2 !px-4 text-[10px] font-black uppercase tracking-widest cursor-pointer inline-block">Ver</summary>
                            <div class="mt-4 bg-erp-surface-2 rounded-2xl p-5 border border-erp-border/30 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs font-bold text-erp-muted">
                                    <p><strong class="text-erp-navy">Tipo:</strong> <?= htmlspecialchars((string) ($convite['tipo'] ?? '-')) ?></p>
                                    <p><strong class="text-erp-navy">Grau:</strong> <?= htmlspecialchars((string) ($convite['grau'] ?? '-')) ?></p>
                                    <p><strong class="text-erp-navy">Local:</strong> <?= htmlspecialchars((string) ($convite['local'] ?? '-')) ?></p>
                                    <p><strong class="text-erp-navy">Prazo:</strong> <?= $formatDateTime($convite['prazo_confirmacao'] ?? null) ?></p>
                                    <p><strong class="text-erp-navy">Contato:</strong> <?= htmlspecialchars((string) ($convite['contatos'] ?? '-')) ?></p>
                                    <p><strong class="text-erp-navy">Valor/traje:</strong> <?= htmlspecialchars(trim((string) (($convite['valor'] ?? '') . ' ' . ($convite['traje'] ?? ''))) ?: '-') ?></p>
                                </div>
                                <?php if (!empty($convite['descricao'])): ?><p class="text-sm text-erp-text leading-relaxed"><?= nl2br(htmlspecialchars((string) $convite['descricao'])) ?></p><?php endif; ?>
                                <?php if ($temAnexo): ?>
                                    <div class="flex flex-wrap items-center gap-3 text-xs font-bold text-erp-muted">
                                        <span>Anexo: <?= htmlspecialchars((string) $convite['anexo_nome']) ?></span>
                                        <form method="POST" action="/secretaria/convites-externos/remover-anexo"><input type="hidden" name="convite_id" value="<?= $conviteId ?>"><button class="text-danger font-black uppercase tracking-widest">Remover anexo</button></form>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="flex items-center justify-between gap-3 mb-2">
                                        <h4 class="text-[10px] font-black text-erp-navy uppercase tracking-widest">Confirmados (<?= count($confirmados) ?>)</h4>
                                        <button type="button" class="btn btn-secondary !py-1.5 !px-3 text-[9px] font-black uppercase tracking-widest" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('confirmados-<?= $conviteId ?>').value)">Copiar confirmados</button>
                                    </div>
                                    <textarea id="confirmados-<?= $conviteId ?>" readonly rows="5" class="form-textarea !text-xs !bg-white"><?= htmlspecialchars($textoCopiar) ?></textarea>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
};

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Mural Externo';
$appShellDescription = 'Gestão de convites de terceiros para agenda e confirmações.';
$appShellActiveHref = '/secretaria/convites-externos';
require __DIR__ . '/_sidebar.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-8 depth-1"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-8 depth-1"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-1">
        <div class="card depth-1 sticky top-8">
            <div class="card-header border-b border-erp-border/50 p-6"><h2 class="text-xl font-black text-erp-navy tracking-tight">Novo Convite Externo</h2></div>
            <div class="card-body p-6">
                <form method="POST" action="/secretaria/convites-externos/salvar" enctype="multipart/form-data" class="space-y-5">
                    <select name="tipo" class="form-select shadow-sm !bg-white"><option value="sessao_magna">Sessão Magna</option><option value="palestra">Palestra</option><option value="evento">Evento</option><option value="outro">Outro</option></select>
                    <input name="titulo" required class="form-input shadow-sm !bg-white" placeholder="Título">
                    <input name="loja_origem" class="form-input shadow-sm !bg-white" placeholder="Loja de origem">
                    <input name="potencia" class="form-input shadow-sm !bg-white" placeholder="Potência / jurisdição">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><input name="grau" class="form-input shadow-sm !bg-white" placeholder="Grau"><input type="datetime-local" name="data_hora" class="form-input shadow-sm !bg-white"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><input name="cidade" class="form-input shadow-sm !bg-white" placeholder="Cidade"><input name="local" class="form-input shadow-sm !bg-white" placeholder="Local"></div>
                    <input type="datetime-local" name="prazo_confirmacao" class="form-input shadow-sm !bg-white">
                    <input name="contatos" class="form-input shadow-sm !bg-white" placeholder="Contatos / RSVP">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><input name="valor" class="form-input shadow-sm !bg-white" placeholder="Valor"><input name="traje" class="form-input shadow-sm !bg-white" placeholder="Traje"></div>
                    <textarea name="descricao" rows="3" class="form-textarea shadow-sm !bg-white" placeholder="Descrição"></textarea>
                    <textarea name="texto_original" rows="3" class="form-textarea shadow-sm !bg-white font-mono text-xs" placeholder="Texto recebido pelo WhatsApp"></textarea>
                    <input type="file" name="anexo" accept="image/*,.pdf" class="form-input shadow-sm !bg-white">
                    <div class="flex gap-4"><select name="status" class="form-select shadow-sm !bg-white"><option value="rascunho">Rascunho</option><option value="publicado">Publicado</option><option value="cancelado">Cancelado</option><option value="encerrado">Encerrado</option></select><label class="flex items-center gap-2 text-xs font-black text-erp-navy uppercase tracking-widest"><input type="checkbox" name="fixado" value="1">Fixado</label></div>
                    <button type="submit" class="btn btn-primary w-full py-4">Salvar Convite</button>
                </form>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-8">
        <div class="flex flex-wrap gap-2">
            <?php foreach (['todos' => 'Todos', 'sessao_magna' => 'Sessões Magnas', 'palestra' => 'Palestras', 'evento' => 'Eventos', 'outro' => 'Outros'] as $valor => $label): ?>
                <a href="/secretaria/convites-externos?tipo=<?= urlencode($valor) ?>" class="btn <?= $tipoFiltro === $valor ? 'btn-primary' : 'btn-secondary' ?> !py-2 !px-4 text-[10px] font-black uppercase tracking-widest"><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </div>
        <?php if ($fixados !== []): ?>
            <section class="space-y-4"><h2 class="text-lg font-black text-erp-navy tracking-tight">Convites em destaque</h2><?php foreach ($fixados as $convite): $renderConvite($convite, $confirmadosPorConvite[(int) ($convite['id'] ?? 0)] ?? []); endforeach; ?></section>
        <?php endif; ?>
        <section class="space-y-4">
            <h2 class="text-lg font-black text-erp-navy tracking-tight">Todos os convites</h2>
            <?php foreach ($demais as $convite): $renderConvite($convite, $confirmadosPorConvite[(int) ($convite['id'] ?? 0)] ?? []); endforeach; ?>
            <?php if ($fixados === [] && $demais === []): ?><div class="card depth-1"><div class="card-body p-8 text-center text-sm text-erp-muted font-medium">Nenhum convite externo cadastrado para este filtro.</div></div><?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
