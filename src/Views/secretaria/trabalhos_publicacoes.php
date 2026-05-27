<?php
declare(strict_types=1);

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Trabalhos & Peças';
$appShellDescription = 'Arquive e publique na Biblioteca após a apresentação em Loja.';
$appShellActiveHref = '/secretaria/trabalhos-publicacoes';
$appShellActions = [['label' => 'Voltar', 'href' => '/secretaria']];

$pendentes = is_array($pendentes ?? null) ? $pendentes : [];
$trabalhosRecentes = is_array($trabalhosRecentes ?? null) ? $trabalhosRecentes : [];
$sessoes = is_array($sessoes ?? null) ? $sessoes : [];

require __DIR__ . '/_sidebar.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_sucesso']) ?></div>
    <?php unset($_SESSION['mensagem_sucesso']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['mensagem_erro'])): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_erro']) ?></div>
    <?php unset($_SESSION['mensagem_erro']); ?>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coluna de Pendências -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h2 class="text-xl font-black text-white tracking-tight">Pendentes para arquivamento</h2>
                <p class="text-sm text-erp-muted mt-1 font-medium font-sans">Revise e publique na Biblioteca para acesso dos irmãos.</p>
            </div>
            <div class="card-body p-6 space-y-6">
                <?php if ($pendentes === []): ?>
                    <div class="text-center py-10 bg-white/[0.01] border border-white/5 rounded-2xl">
                        <span class="text-3xl block mb-2 opacity-30">✨</span>
                        <p class="text-sm text-slate-400 font-medium">Nenhuma submissão aguardando arquivamento.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($pendentes as $p): ?>
                            <div class="p-6 bg-white/[0.02] border border-white/5 rounded-2xl relative shadow-md">
                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/5 pb-4 mb-4">
                                    <div>
                                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Submissão Enviada</p>
                                        <h3 class="text-base font-bold text-white mt-1"><?= htmlspecialchars((string) ($p['titulo'] ?? '')) ?></h3>
                                    </div>
                                    <span class="badge bg-white/5 text-slate-300 border border-white/10 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                                        <?= htmlspecialchars((string) ($p['obreiro_nome'] ?? 'Irmão')) ?>
                                    </span>
                                </div>
                                
                                <form method="POST" action="/secretaria/trabalhos-publicacoes/arquivar" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($p['id'] ?? '')) ?>">
                                    <input type="hidden" name="autor_id" value="<?= htmlspecialchars((string) ($p['obreiro_id'] ?? '')) ?>">

                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Título do Trabalho</label>
                                        <input class="form-input shadow-sm w-full" name="titulo" value="<?= htmlspecialchars((string) ($p['titulo'] ?? '')) ?>" required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Tipo de Trabalho</label>
                                        <?php $tipo = (string) ($p['tipo_trabalho'] ?? 'peca_arquitetura'); ?>
                                        <select class="form-select shadow-sm w-full" name="tipo_trabalho" required>
                                            <option value="peca_arquitetura" <?= $tipo === 'peca_arquitetura' ? 'selected' : '' ?>>Peça de Arquitetura</option>
                                            <option value="trabalho_apresentado" <?= $tipo === 'trabalho_apresentado' ? 'selected' : '' ?>>Trabalho apresentado</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Apresentado na Sessão</label>
                                        <select class="form-select shadow-sm w-full" name="sessao_id" required>
                                            <option value="">Selecionar sessão...</option>
                                            <?php foreach ($sessoes as $s): ?>
                                                <?php $sid = (string) ($s['id'] ?? ''); ?>
                                                <option value="<?= htmlspecialchars($sid) ?>" <?= (string) ($p['sessao_id'] ?? '') === $sid ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) ($s['titulo'] ?? 'Sessão')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">PDF (Caminho ou URL do Arquivo)</label>
                                        <input class="form-input shadow-sm w-full" name="arquivo_pdf_path" value="<?= htmlspecialchars((string) ($p['arquivo_pdf_path'] ?? '')) ?>" required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Status na Potência</label>
                                        <select class="form-select shadow-sm w-full" name="status_envio_potencia">
                                            <option value="pendente">Pendente</option>
                                            <option value="enviado">Enviado</option>
                                            <option value="dispensado">Dispensado</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Observações da Secretaria</label>
                                        <input class="form-input shadow-sm w-full" name="observacao" placeholder="Ex: Aprovado sem ressalvas.">
                                    </div>
                                    <div class="md:col-span-2 text-right border-t border-white/5 pt-4 mt-2">
                                        <button class="btn btn-primary w-full md:w-auto text-xs font-black uppercase tracking-widest" type="submit">
                                            Arquivar e tornar público
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral -->
    <div class="space-y-6">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h2 class="text-xl font-black text-white tracking-tight">Arquivos Recentes</h2>
                <p class="text-xs text-erp-muted mt-1 font-medium font-sans">Os últimos 10 trabalhos integrados na Biblioteca.</p>
            </div>
            <div class="card-body p-6 space-y-4">
                <?php foreach (array_slice($trabalhosRecentes, 0, 10) as $t): ?>
                    <div class="p-3 bg-white/[0.01] border border-white/5 rounded-xl text-xs space-y-2">
                        <div class="flex justify-between items-start gap-2">
                            <span class="font-bold text-white truncate max-w-[150px]"><?= htmlspecialchars((string) ($t['titulo'] ?? '')) ?></span>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 shrink-0 bg-white/5 px-1.5 py-0.5 rounded">
                                <?= htmlspecialchars(str_replace('_', ' ', (string) ($t['tipo_trabalho'] ?? ''))) ?>
                            </span>
                        </div>
                        <div class="text-slate-400 space-y-0.5">
                            <p><span class="text-slate-600">Sessão:</span> <?= htmlspecialchars((string) ($t['sessao_titulo'] ?? '')) ?></p>
                            <?php if (!empty($t['autor_nome'])): ?>
                                <p><span class="text-slate-600">Autor:</span> <?= htmlspecialchars((string) $t['autor_nome']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($t['arquivo_pdf_path'])): ?>
                            <div class="text-right mt-1 pt-1.5 border-t border-white/5">
                                <a class="text-erp-gold hover:underline text-[10px] font-bold" href="<?= htmlspecialchars((string) $t['arquivo_pdf_path']) ?>" target="_blank" rel="noopener">Visualizar PDF &rarr;</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($trabalhosRecentes === []): ?>
                    <div class="text-center py-6 text-xs text-slate-400 bg-white/[0.01] border border-white/5 rounded-xl">
                        Nenhum arquivo cadastrado ainda.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
