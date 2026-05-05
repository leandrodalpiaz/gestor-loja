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

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Convites Externos';
$appShellDescription = 'Cadastro manual de convites de terceiros para mural e confirmações.';
$appShellActiveHref = '/secretaria/convites-externos';
require __DIR__ . '/_sidebar.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Novo Convite Externo</h2>
                <p class="card-description">Registro manual a partir de imagem, PDF ou texto recebido.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="/secretaria/convites-externos/salvar" enctype="multipart/form-data" class="space-y-4">
                    <div><label class="form-label">Tipo</label><select name="tipo" class="form-select"><option value="sessao_magna">Sessão Magna</option><option value="palestra">Palestra</option><option value="evento">Evento</option><option value="outro">Outro</option></select></div>
                    <div><label class="form-label">Título</label><input name="titulo" required class="form-input"></div>
                    <div><label class="form-label">Loja de origem</label><input name="loja_origem" class="form-input"></div>
                    <div><label class="form-label">Potência/Jurisdição</label><input name="potencia" class="form-input"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="form-label">Grau</label><input name="grau" class="form-input"></div>
                        <div><label class="form-label">Data/Hora</label><input type="datetime-local" name="data_hora" class="form-input"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="form-label">Cidade</label><input name="cidade" class="form-input"></div>
                        <div><label class="form-label">Local</label><input name="local" class="form-input"></div>
                    </div>
                    <div><label class="form-label">Prazo de confirmação</label><input type="datetime-local" name="prazo_confirmacao" class="form-input"></div>
                    <div><label class="form-label">Contatos</label><input name="contatos" class="form-input"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="form-label">Valor/inscrição</label><input name="valor" class="form-input"></div>
                        <div><label class="form-label">Traje/orientações</label><input name="traje" class="form-input"></div>
                    </div>
                    <div><label class="form-label">Descrição</label><textarea name="descricao" rows="3" class="form-textarea"></textarea></div>
                    <div><label class="form-label">Texto original</label><textarea name="texto_original" rows="4" class="form-textarea"></textarea></div>
                    <div><label class="form-label">Anexo original</label><input type="file" name="anexo" accept="image/*,.pdf" class="form-input"></div>
                    <div><label class="form-label">Status</label><select name="status" class="form-select"><option value="rascunho">Rascunho</option><option value="publicado">Publicado</option><option value="cancelado">Cancelado</option><option value="encerrado">Encerrado</option></select></div>
                    <label class="form-check-label"><input type="checkbox" name="fixado" value="1" class="form-checkbox"> Fixado</label>
                    <button type="submit" class="btn btn-primary">Salvar convite</button>
                </form>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Mural Externo</h2>
                <p class="card-description">Convites cadastrados pela Secretaria.</p>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    <?php if (empty($convitesExternos)): ?>
                        <p class="text-center text-sm text-gray-500 py-8">Nenhum convite externo cadastrado.</p>
                    <?php endif; ?>
                    <?php foreach ($convitesExternos as $convite): ?>
                        <?php $confirmados = $confirmadosPorConvite[(int) ($convite['id'] ?? 0)] ?? []; ?>
                        <div class="list-item-report">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars((string) ($convite['titulo'] ?? 'Convite')) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars((string) ($convite['loja_origem'] ?? '-')) ?> · <?= $formatDateTime($convite['data_hora'] ?? null) ?> · <?= htmlspecialchars((string) ($convite['cidade'] ?? '')) ?></p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="badge badge-secondary"><?= htmlspecialchars((string) ($convite['status'] ?? 'rascunho')) ?></span>
                                    <?php if (!empty($convite['fixado'])): ?><span class="badge badge-primary">Fixado</span><?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($convite['descricao'])): ?><p class="mt-3 text-sm text-gray-600"><?= nl2br(htmlspecialchars((string) $convite['descricao'])) ?></p><?php endif; ?>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                <form method="POST" action="/secretaria/convites-externos/presenca" class="flex flex-wrap gap-2">
                                    <input type="hidden" name="convite_id" value="<?= (int) ($convite['id'] ?? 0) ?>">
                                    <input type="hidden" name="status" value="confirmado">
                                    <button type="submit" class="btn btn-sm btn-primary">Confirmar</button>
                                </form>
                                <form method="POST" action="/secretaria/convites-externos/presenca" class="flex flex-wrap gap-2">
                                    <input type="hidden" name="convite_id" value="<?= (int) ($convite['id'] ?? 0) ?>">
                                    <input type="hidden" name="status" value="cancelado">
                                    <button type="submit" class="btn btn-sm btn-secondary">Cancelar</button>
                                </form>
                                <?php if (!empty($convite['anexo_nome']) && empty($convite['anexo_deleted_at'])): ?>
                                    <span>Anexo: <?= htmlspecialchars((string) $convite['anexo_nome']) ?></span>
                                    <form method="POST" action="/secretaria/convites-externos/remover-anexo">
                                        <input type="hidden" name="convite_id" value="<?= (int) ($convite['id'] ?? 0) ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary">Remover anexo</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($confirmados)): ?>
                                <?php
                                $linhasConfirmados = array_map(
                                    static fn(array $item): string => '- ' . trim((string) ($item['nome'] ?? 'Irmao')) . ((string) ($item['cim'] ?? '') !== '' ? ' (CIM: ' . (string) $item['cim'] . ')' : ''),
                                    $confirmados
                                );
                                $textoConfirmados = "Confirmados (" . count($confirmados) . "):\n" . implode("\n", $linhasConfirmados);
                                ?>
                                <div class="mt-3">
                                    <p class="text-sm font-medium">Confirmados (<?= count($confirmados) ?>)</p>
                                    <textarea class="form-textarea mt-2" rows="4" readonly><?= htmlspecialchars($textoConfirmados) ?></textarea>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
