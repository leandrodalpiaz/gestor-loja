<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Minha Loja';
$appShellDescription = 'Painel pessoal e privativo do Obreiro. Gerencie seus dados, finanças, trabalhos e RSVPs.';
$appShellActiveHref = '/minha-loja';
$appShellActions = [['label' => 'Painel Principal', 'href' => '/dashboard']];

// Safety checks and variable initializations
$obreiro = $obreiro ?? null;
$resumoObreiro = is_array($resumoObreiro ?? null) ? $resumoObreiro : [];
$obrigacoesObreiro = is_array($obrigacoesObreiro ?? null) ? $obrigacoesObreiro : [];
$familiares = is_array($familiares ?? null) ? $familiares : [];
$solicitacoes = is_array($solicitacoes ?? null) ? $solicitacoes : [];
$sessoes = is_array($sessoes ?? null) ? $sessoes : [];
$submissoes = is_array($submissoes ?? null) ? $submissoes : [];
$trabalhosPublicados = is_array($trabalhosPublicados ?? null) ? $trabalhosPublicados : [];
$sessoesFuturas = is_array($sessoesFuturas ?? null) ? $sessoesFuturas : [];
$comunicados = is_array($comunicados ?? null) ? $comunicados : [];
$recados = is_array($recados ?? null) ? $recados : [];
$alertas = is_array($alertas ?? null) ? $alertas : [];
$dados_cadastro = is_array($dados_cadastro ?? null) ? $dados_cadastro : [];

$abaAtiva = $aba_ativa ?? 'dashboard';
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$formatCurrency = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static fn (?string $date): string => $date ? (new DateTimeImmutable($date))->format('d/m/Y') : '-';

// Visual helper for degree progress
$grausOrdem = ['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'];
$grauObreiro = (string) ($obreiro['grau'] ?? 'Aprendiz');
$indexGrau = array_search($grauObreiro, $grausOrdem);
if ($indexGrau === false) {
    $indexGrau = 0;
}

$statusRegularidade = $resumoObreiro['parcelas_atrasadas'] > 0 ? 'irregular' : 'regular';

// Load config for PIX key
$configuracaoFinanceira = (new \App\Models\ConfiguracaoLoja())->obter();
$pixTipo = (string) ($configuracaoFinanceira['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoFinanceira['pix_chave_valor'] ?? '');
$pixBeneficiario = (string) ($configuracaoFinanceira['pix_beneficiario'] ?? '');

require __DIR__ . '/partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6 p-4 rounded-xl border border-success/20 bg-success/5 text-success flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <div><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    </div>
<?php endif; ?>

<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6 p-4 rounded-xl border border-danger/20 bg-danger/5 text-danger flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <div><?= htmlspecialchars((string) $mensagemErro) ?></div>
    </div>
<?php endif; ?>

<!-- Tabs Navigation -->
<div class="card mb-6 border border-white/5 bg-[#162a42]/30">
    <div class="card-body p-2">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2">
            <a href="/minha-loja?aba=dashboard" class="px-4 py-2.5 text-xs font-bold text-center rounded-xl border transition-all <?= $abaAtiva === 'dashboard' ? 'bg-[#C9A227] text-erp-navy-deep border-[#C9A227]' : 'bg-white/5 text-slate-300 border-white/5 hover:bg-white/10' ?>">
                Resumo (Início)
            </a>
            <a href="/minha-loja?aba=cadastro" class="px-4 py-2.5 text-xs font-bold text-center rounded-xl border transition-all <?= $abaAtiva === 'cadastro' ? 'bg-[#C9A227] text-erp-navy-deep border-[#C9A227]' : 'bg-white/5 text-slate-300 border-white/5 hover:bg-white/10' ?>">
                Cadastro & Família
            </a>
            <a href="/minha-loja?aba=financeiro" class="px-4 py-2.5 text-xs font-bold text-center rounded-xl border transition-all <?= $abaAtiva === 'financeiro' ? 'bg-[#C9A227] text-erp-navy-deep border-[#C9A227]' : 'bg-white/5 text-slate-300 border-white/5 hover:bg-white/10' ?>">
                Financeiro
            </a>
            <a href="/minha-loja?aba=trabalhos" class="px-4 py-2.5 text-xs font-bold text-center rounded-xl border transition-all <?= $abaAtiva === 'trabalhos' ? 'bg-[#C9A227] text-erp-navy-deep border-[#C9A227]' : 'bg-white/5 text-slate-300 border-white/5 hover:bg-white/10' ?>">
                Trabalhos & Docência
            </a>
            <a href="/minha-loja?aba=compromissos" class="px-4 py-2.5 text-xs font-bold text-center rounded-xl border transition-all <?= $abaAtiva === 'compromissos' ? 'bg-[#C9A227] text-erp-navy-deep border-[#C9A227]' : 'bg-white/5 text-slate-300 border-white/5 hover:bg-white/10' ?>">
                Agenda & RSVPs
            </a>
            <a href="/minha-loja?aba=mural" class="px-4 py-2.5 text-xs font-bold text-center rounded-xl border transition-all <?= $abaAtiva === 'mural' ? 'bg-[#C9A227] text-erp-navy-deep border-[#C9A227]' : 'bg-white/5 text-slate-300 border-white/5 hover:bg-white/10' ?>">
                Mural & Avisos
            </a>
        </div>
    </div>
</div>

<!-- Tab Content: Dashboard -->
<?php if ($abaAtiva === 'dashboard'): ?>
    <div class="space-y-6">
        <!-- Banner & Degree Progress -->
        <div class="glass-surface p-6 rounded-2xl border border-white/5 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4 text-center md:text-left">
                <div class="w-16 h-16 rounded-full bg-erp-gold/10 text-erp-gold flex items-center justify-center font-bold text-2xl shadow-inner shrink-0">
                    <?= htmlspecialchars(substr($obreiro['nome'] ?? 'I', 0, 1)) ?>
                </div>
                <div>
                    <h2 class="font-cinzel text-xl font-bold text-white"><?= htmlspecialchars((string) ($obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Irmão')) ?></h2>
                    <p class="text-xs text-slate-400 mt-1">CIM: <?= htmlspecialchars((string) ($obreiro['cim'] ?? 'Não informado')) ?> · Grau: <?= htmlspecialchars($grauObreiro) ?></p>
                </div>
            </div>
            
            <!-- Visual Degree Progress Track -->
            <div class="w-full md:w-auto md:max-w-md flex-1">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block mb-3 text-center md:text-right">Jornada Simbólica</span>
                <div class="flex items-center justify-between relative mt-2 px-2">
                    <div class="absolute left-0 right-0 top-1/2 h-1 bg-white/10 -translate-y-1/2 z-0"></div>
                    <div class="absolute left-0 top-1/2 h-1 bg-[#C9A227] -translate-y-1/2 z-0 transition-all duration-700" style="width: <?= $indexGrau * 33.33 ?>%;"></div>
                    <?php foreach ($grausOrdem as $idx => $g): ?>
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold border transition-colors <?= $idx <= $indexGrau ? 'bg-[#C9A227] border-[#C9A227] text-erp-navy-deep' : 'bg-[#0A1628] border-white/15 text-slate-400' ?>" title="<?= htmlspecialchars($g) ?>">
                                <?= $idx + 1 ?>
                            </div>
                            <span class="text-[9px] text-slate-400 font-semibold mt-1 hidden sm:block"><?= htmlspecialchars($g) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Metrics Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="glass-surface p-5 rounded-2xl border border-white/5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-erp-gold shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Situação Cadastral</span>
                    <div class="mt-1 font-bold text-sm <?= $statusRegularidade === 'regular' ? 'text-emerald-400' : 'text-danger' ?>">
                        <?= $statusRegularidade === 'regular' ? 'Regular' : 'Com Pendências' ?>
                    </div>
                </div>
            </div>

            <div class="glass-surface p-5 rounded-2xl border border-white/5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-erp-gold shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Saldo em aberto</span>
                    <div class="mt-1 font-bold text-sm text-white"><?= $formatCurrency($resumoObreiro['saldo_em_aberto'] ?? 0) ?></div>
                </div>
            </div>

            <div class="glass-surface p-5 rounded-2xl border border-white/5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-erp-gold shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15M9 11l3 3L22 4" /></svg>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Trabalhos Entregues</span>
                    <div class="mt-1 font-bold text-sm text-white"><?= count($submissoes) + count($trabalhosPublicados) ?> peças</div>
                </div>
            </div>

            <div class="glass-surface p-5 rounded-2xl border border-white/5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-erp-gold shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Próximo RSVP</span>
                    <div class="mt-1 font-bold text-sm text-white">
                        <?php 
                        $proximaSessao = !empty($sessoesFuturas) ? $sessoesFuturas[0] : null; 
                        echo $proximaSessao ? $formatDate($proximaSessao['data_hora_inicio']) : 'Sem sessões';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Alerts & Notifications -->
            <div class="lg:col-span-2 space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Minha Atenção / Alertas</h2></div>
                    <div class="card-body space-y-3">
                        <?php if ($alertas === []): ?>
                            <div class="p-4 rounded-xl border border-white/5 bg-white/[0.01] text-xs text-slate-400 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Nenhuma pendência financeira ou cadastral crítica detectada.
                            </div>
                        <?php else: ?>
                            <?php foreach ($alertas as $alerta): ?>
                                <div class="alert alert-warning p-4 rounded-xl border border-warning/10 bg-warning/5 text-warning flex items-center gap-3 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    <div><?= htmlspecialchars((string) $alerta) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent announcements / mural -->
                <div class="card">
                    <div class="card-header flex justify-between items-center">
                        <h2 class="card-title">Avisos Recentes</h2>
                        <a href="/minha-loja?aba=mural" class="text-xs text-erp-gold hover:text-white">Ver mural &rarr;</a>
                    </div>
                    <div class="card-body space-y-4">
                        <?php if (empty($comunicados) && empty($recados)): ?>
                            <div class="text-xs text-slate-400">Nenhum comunicado publicado recentemente.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($comunicados, 0, 2) as $com): ?>
                                <div class="p-4 rounded-xl border border-white/5 bg-white/[0.01]">
                                    <div class="flex items-center justify-between gap-3">
                                        <h4 class="font-bold text-white text-xs uppercase tracking-wider"><?= htmlspecialchars((string) ($com['titulo'] ?? 'Comunicado')) ?></h4>
                                        <span class="text-[9px] text-slate-400"><?= $formatDate($com['publicado_em']) ?></span>
                                    </div>
                                    <p class="text-xs text-slate-300 mt-2 line-clamp-3">Acesse a aba <strong>Mural & Avisos</strong> para ler o conteúdo integral.</p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Quick actions menu -->
            <div class="space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title font-cinzel">Ações Rápidas</h2></div>
                    <div class="card-body p-0 divide-y divide-white/5 text-sm">
                        <a href="/minha-loja?aba=financeiro" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-white/5 transition-all">
                            <span class="text-erp-gold">💰</span> Contribuição Financeira (PIX)
                        </a>
                        <a href="/minha-loja?aba=trabalhos" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-white/5 transition-all">
                            <span class="text-erp-gold">✍️</span> Enviar Peça de Arquitetura
                        </a>
                        <a href="/minha-loja?aba=cadastro" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-white/5 transition-all">
                            <span class="text-erp-gold">👤</span> Atualizar Dependentes / Perfil
                        </a>
                        <a href="/minha-loja/irmaos" class="flex items-center gap-3 px-4 py-3 text-slate-300 hover:bg-white/5 transition-all">
                            <span class="text-erp-gold">🤝</span> Quadro de Obreiros da Loja
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Tab Content: Cadastro & Família -->
<?php elseif ($abaAtiva === 'cadastro'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Dados Cadastrais -->
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h2 class="card-title">Dados Cadastrais</h2>
                    <button type="button" class="btn btn-secondary text-xs" data-self-edit-btn="start">Editar Cadastro</button>
                    <button type="button" class="btn btn-secondary text-xs hidden" data-self-edit-btn="cancel">Cancelar</button>
                </div>
                <div class="card-body">
                    <form action="/meu-cadastro/atualizar" method="POST" class="space-y-6" data-self-form="1">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($obreiro['id'] ?? '')) ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="md:col-span-2">
                                <label class="form-label font-bold text-slate-400">Nome Completo</label>
                                <input type="text" name="nome" required value="<?= htmlspecialchars((string) ($dados_cadastro['nome'] ?? '')) ?>" class="form-input" data-self-editable="1" disabled>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400">CIM</label>
                                <input type="text" name="cim" value="<?= htmlspecialchars((string) ($dados_cadastro['cim'] ?? '')) ?>" class="form-input" data-self-editable="1" disabled>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400">E-mail</label>
                                <input type="email" name="email" value="<?= htmlspecialchars((string) ($dados_cadastro['email'] ?? '')) ?>" class="form-input" data-self-editable="1" disabled>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400">Telefone</label>
                                <input type="text" name="telefone" value="<?= htmlspecialchars((string) ($dados_cadastro['telefone'] ?? '')) ?>" class="form-input" data-self-editable="1" disabled>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400">Nascimento</label>
                                <input type="date" name="data_nascimento_civil" value="<?= htmlspecialchars((string) ($dados_cadastro['data_nascimento_civil'] ?? '')) ?>" class="form-input" data-self-editable="1" disabled>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400">Estado Civil</label>
                                <select name="estado_civil" class="form-select" data-self-editable="1" disabled>
                                    <?php 
                                    $estCivil = $dados_cadastro['estado_civil'] ?? '';
                                    foreach (['solteiro' => 'Solteiro', 'casado' => 'Casado', 'viuvo' => 'Viúvo', 'divorciado' => 'Divorciado', 'uniao_estavel' => 'União Estável'] as $key => $lbl) {
                                        $selected = $estCivil === $key ? 'selected' : '';
                                        echo "<option value=\"$key\" $selected>$lbl</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400">Profissão</label>
                                <input type="text" name="profissao" value="<?= htmlspecialchars((string) ($dados_cadastro['profissao'] ?? '')) ?>" class="form-input" data-self-editable="1" disabled>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 hidden" id="self-edit-actions">
                            <button type="submit" class="btn btn-primary px-6">Confirmar Salvar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Familiares / Dependentes -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Dependentes & Familiares</h2></div>
                <div class="card-body space-y-6">
                    <!-- Familiar List -->
                    <?php if ($familiares === []): ?>
                        <div class="alert alert-info text-xs">Nenhum dependente cadastrado no seu cadastro de obreiro.</div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach ($familiares as $f): ?>
                                <div class="glass-surface p-4 rounded-xl border border-white/5 text-sm space-y-1">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-white"><?= htmlspecialchars((string) ($f['nome_completo'] ?? '')) ?></span>
                                        <span class="badge px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider <?= ($f['status_revisao'] ?? 'pendente') === 'revisado' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-warning/10 text-warning' ?>">
                                            <?= htmlspecialchars((string) ($f['status_revisao'] ?? 'pendente')) ?>
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-400">Parentesco: <span class="capitalize text-slate-200"><?= htmlspecialchars((string) ($f['parentesco'] ?? '')) ?></span></div>
                                    <?php if (!empty($f['data_nascimento'])): ?>
                                        <div class="text-xs text-slate-400">Nascimento: <span class="text-slate-200"><?= $formatDate($f['data_nascimento']) ?></span></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form to add familiar -->
                    <div class="border-t border-white/5 pt-4">
                        <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">Adicionar Dependente</h4>
                        <form method="POST" action="/minha-loja/familiares/salvar" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label font-bold text-slate-400 text-xs">Nome Completo</label>
                                <input class="form-input" name="nome_completo" required>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400 text-xs">Parentesco</label>
                                <select class="form-select" name="parentesco" required>
                                    <option value="esposa">Esposa</option>
                                    <option value="esposo">Esposo</option>
                                    <option value="filho">Filho</option>
                                    <option value="filha">Filha</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label font-bold text-slate-400 text-xs">Data de Nascimento</label>
                                <input class="form-input" type="date" name="data_nascimento">
                            </div>
                            <div class="sm:col-span-2 text-right">
                                <button class="btn btn-secondary px-6 text-xs" type="submit">Cadastrar Familiar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests side panel -->
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Alterações de Dados Controlados</h2></div>
                <div class="card-body space-y-6 text-xs">
                    <p class="text-slate-400">Campos controlados (como grau ritualístico, rito, oriente e potência) necessitam de revisão da secretaria para modificação.</p>
                    
                    <form method="POST" action="/minha-loja/solicitacoes/salvar" class="space-y-4 border-t border-white/5 pt-4">
                        <div>
                            <label class="form-label font-bold text-slate-400">Tipo de Correção</label>
                            <select class="form-select text-xs" name="tipo_solicitacao" required>
                                <option value="corrigir_grau">Corrigir Grau</option>
                                <option value="corrigir_loja">Corrigir Loja</option>
                                <option value="corrigir_numero_loja">Corrigir Número da Loja</option>
                                <option value="corrigir_oriente">Corrigir Oriente</option>
                                <option value="corrigir_potencia">Corrigir Potência</option>
                                <option value="corrigir_rito">Corrigir Rito</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-bold text-slate-400">Valor Solicitado / Novo Dado</label>
                            <input class="form-input text-xs" name="valor_solicitado" placeholder="Ex: Mestre Instalado, Rito de York, etc." required>
                        </div>
                        <div>
                            <label class="form-label font-bold text-slate-400">Justificativa</label>
                            <textarea class="form-textarea text-xs" name="justificativa" rows="3" placeholder="Insira o motivo ou observação para a Secretaria..." required></textarea>
                        </div>
                        <button class="btn btn-primary w-full text-xs font-bold" type="submit">Enviar Chamado</button>
                    </form>

                    <!-- History of requests -->
                    <?php if ($solicitacoes !== []): ?>
                        <div class="border-t border-white/5 pt-4">
                            <h4 class="font-bold text-white text-[10px] uppercase tracking-wider mb-3">Histórico de Solicitações</h4>
                            <div class="space-y-3 max-h-[200px] overflow-y-auto pr-1">
                                <?php foreach ($solicitacoes as $s): ?>
                                    <div class="p-3 bg-white/[0.01] rounded-xl border border-white/5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-white capitalize text-[10px]"><?= str_replace('_', ' ', (string) $s['tipo_solicitacao']) ?></span>
                                            <span class="badge px-1.5 py-0.5 rounded text-[8px] font-bold uppercase <?= ($s['status'] ?? 'pendente') === 'aprovada' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-warning/10 text-warning' ?>">
                                                <?= htmlspecialchars((string) ($s['status'] ?? 'pendente')) ?>
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-300 mt-1"><?= htmlspecialchars((string) ($s['valor_solicitado'] ?? '')) ?></div>
                                        <?php if (!empty($s['resposta_secretaria'])): ?>
                                            <div class="text-[9px] text-slate-400 mt-1 italic">Obs: <?= htmlspecialchars((string) $s['resposta_secretaria']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JS for Start Editing Profile -->
    <script>
    (() => {
        const startBtn = document.querySelector('[data-self-edit-btn="start"]');
        const cancelBtn = document.querySelector('[data-self-edit-btn="cancel"]');
        const submitDiv = document.getElementById('self-edit-actions');
        const form = document.querySelector('[data-self-form="1"]');
        if (!form) return;
        const editables = form.querySelectorAll('[data-self-editable="1"]');

        startBtn.addEventListener('click', () => {
            editables.forEach(input => input.removeAttribute('disabled'));
            startBtn.classList.add('hidden');
            cancelBtn.classList.remove('hidden');
            submitDiv.classList.remove('hidden');
        });

        cancelBtn.addEventListener('click', () => {
            editables.forEach(input => input.setAttribute('disabled', '1'));
            startBtn.classList.remove('hidden');
            cancelBtn.classList.add('hidden');
            submitDiv.classList.add('hidden');
        });
    })();
    </script>

<!-- Tab Content: Financeiro -->
<?php elseif ($abaAtiva === 'financeiro'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Balance Info -->
        <div class="lg:col-span-3 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="metric-card p-5 bg-white/5 rounded-2xl border border-white/5 flex flex-col justify-between">
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Saldo em aberto</div>
                    <div class="text-2xl font-bold text-white mt-2"><?= $formatCurrency($resumoObreiro['saldo_em_aberto'] ?? 0) ?></div>
                </div>
                <div class="metric-card p-5 bg-white/5 rounded-2xl border border-white/5 flex flex-col justify-between">
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Saldo em atraso</div>
                    <div class="text-2xl font-bold text-danger mt-2"><?= $formatCurrency($resumoObreiro['saldo_em_atraso'] ?? 0) ?></div>
                </div>
                <div class="metric-card p-5 bg-white/5 rounded-2xl border border-white/5 flex flex-col justify-between">
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Mensalidades Atrasadas</div>
                    <div class="text-2xl font-bold text-danger mt-2"><?= (int) ($resumoObreiro['parcelas_atrasadas'] ?? 0) ?></div>
                </div>
            </div>

            <!-- Obligations & Installments list -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Minhas Obrigações Financeiras</h2></div>
                <div class="card-body space-y-6">
                    <?php if ($obrigacoesObreiro === []): ?>
                        <div class="alert alert-info text-xs">Você não possui obrigações financeiras cadastradas.</div>
                    <?php else: ?>
                        <?php foreach ($obrigacoesObreiro as $ob): ?>
                            <div class="p-4 rounded-xl border border-white/5 bg-white/[0.01] space-y-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-bold text-white text-sm"><?= htmlspecialchars((string) ($ob['titulo'] ?? 'Obrigação')) ?></h4>
                                        <p class="text-xs text-slate-400 mt-0.5">Tipo: <span class="capitalize text-slate-200"><?= htmlspecialchars((string) ($ob['tipo_obrigacao'] ?? 'Outra')) ?></span></p>
                                    </div>
                                    <span class="badge px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider <?= ($ob['status'] ?? 'ativa') === 'ativa' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-500/10 text-slate-400 border border-slate-500/20' ?>">
                                        <?= htmlspecialchars((string) ($ob['status'] ?? 'ativa')) ?>
                                    </span>
                                </div>

                                <!-- Installments list -->
                                <?php if (!empty($ob['parcelas'])): ?>
                                    <div class="border-t border-white/5 pt-3 space-y-2">
                                        <h5 class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Histórico de Parcelas</h5>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                            <?php foreach ($ob['parcelas'] as $parc): ?>
                                                <?php 
                                                $statusParc = strtolower(trim((string) ($parc['status'] ?? 'pendente')));
                                                $isPago = $statusParc === 'pago' || !empty($parc['quitado_na_exibicao']);
                                                $isAtrasado = !$isPago && !empty($parc['em_atraso']);
                                                ?>
                                                <div class="p-3 bg-white/[0.02] border border-white/5 rounded-xl flex items-center justify-between text-xs">
                                                    <div>
                                                        <div class="font-semibold text-white">Parcela <?= (int) ($parc['numero_parcela'] ?? 1) ?></div>
                                                        <div class="text-[10px] text-slate-400">Vencimento: <?= $formatDate($parc['vencimento']) ?></div>
                                                        <div class="font-bold mt-1 text-white"><?= $formatCurrency($parc['valor_previsto']) ?></div>
                                                    </div>
                                                    <span class="badge px-1.5 py-0.5 rounded text-[8px] font-bold uppercase <?= $isPago ? 'bg-emerald-500/10 text-emerald-400' : ($isAtrasado ? 'bg-danger/10 text-danger border border-danger/20' : 'bg-slate-500/10 text-slate-400') ?>">
                                                        <?= $isPago ? 'pago' : ($isAtrasado ? 'atrasado' : 'pendente') ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PIX Box -->
        <div class="space-y-6">
            <div class="card border border-erp-gold/30">
                <div class="card-header"><h2 class="card-title">Contribuição via PIX</h2></div>
                <div class="card-body text-xs space-y-4">
                    <p class="text-slate-400">Utilize a chave PIX abaixo para enviar suas contribuições, joias, mensalidades ou biblioteca.</p>
                    
                    <div class="p-3 bg-white/5 rounded-xl border border-white/5 space-y-1">
                        <div class="text-slate-400 font-bold uppercase text-[9px]">Chave <?= htmlspecialchars($pixTipo) ?></div>
                        <div class="font-mono font-bold text-white select-all text-xs break-all"><?= htmlspecialchars($pixValor !== '' ? $pixValor : 'Não cadastrada') ?></div>
                    </div>
                    
                    <div class="space-y-1">
                        <div class="text-slate-400 font-bold uppercase text-[9px]">Favorecido / Beneficiário</div>
                        <div class="font-bold text-white text-xs"><?= htmlspecialchars($pixBeneficiario !== '' ? $pixBeneficiario : 'Não informado') ?></div>
                    </div>

                    <?php if ($pixValor !== ''): ?>
                        <button type="button" class="btn btn-secondary w-full py-2.5 font-bold text-xs" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($pixValor)) ?>'); alert('Chave PIX copiada!')">
                            Copiar Chave PIX
                        </button>
                    <?php endif; ?>
                    
                    <div class="p-3 rounded-xl bg-[#C9A227]/5 border border-[#C9A227]/10 text-[10px] text-slate-400 leading-relaxed">
                        💡 <strong>Lembrete:</strong> Envie o comprovante de pagamento à Tesouraria ou tire um print para validação rápida no mural da secretaria.
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Tab Content: Trabalhos & Docência -->
<?php elseif ($abaAtiva === 'trabalhos'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Validação / Submissões -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Acompanhamento de Peças Submetidas</h2></div>
                <div class="card-body space-y-3">
                    <?php if ($submissoes === []): ?>
                        <div class="alert alert-info text-xs">Nenhum trabalho submetido para validação dos Vigilantes.</div>
                    <?php else: ?>
                        <?php foreach ($submissoes as $sub): ?>
                            <?php 
                            $statusSub = (string) ($sub['status'] ?? 'rascunho');
                            $labelStatus = match ($statusSub) {
                                'pendente_mentor' => 'aguardando mentor',
                                'pendente_secretaria' => 'aguardando secretaria',
                                'rejeitado' => 'rejeitado',
                                'arquivado' => 'arquivado e publicado',
                                default => $statusSub,
                            };
                            ?>
                            <div class="p-4 rounded-xl border border-white/5 bg-white/[0.01] flex flex-col justify-between gap-3 text-xs">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-bold text-white text-sm"><?= htmlspecialchars((string) ($sub['titulo'] ?? 'Sem título')) ?></h4>
                                    <span class="badge px-2 py-0.5 rounded font-bold uppercase tracking-wider text-[9px] <?= $statusSub === 'arquivado' ? 'bg-emerald-500/10 text-emerald-400' : ($statusSub === 'rejeitado' ? 'bg-danger/10 text-danger border border-danger/20' : 'bg-warning/10 text-warning') ?>">
                                        <?= htmlspecialchars($labelStatus) ?>
                                    </span>
                                </div>
                                <div class="text-slate-400">
                                    Tipo: <span class="capitalize text-slate-200"><?= str_replace('_', ' ', (string) ($sub['tipo_trabalho'] ?? 'peça')) ?></span>
                                    <?php if (!empty($sub['grau_obreiro'])): ?> · Grau: <span class="text-slate-200"><?= htmlspecialchars($sub['grau_obreiro']) ?></span><?php endif; ?>
                                </div>
                                <?php if (!empty($sub['mentor_observacao'])): ?>
                                    <div class="p-2.5 rounded-lg bg-white/5 border border-white/5 text-[10px] text-slate-300">
                                        <strong>Observação do Mentor:</strong> <?= htmlspecialchars($sub['mentor_observacao']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($sub['arquivo_pdf_path'])): ?>
                                    <div class="pt-2">
                                        <a href="<?= htmlspecialchars((string) $sub['arquivo_pdf_path']) ?>" target="_blank" rel="noopener" class="btn btn-secondary text-[10px] !py-1 !px-3 font-semibold">
                                            Visualizar Anexo PDF
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Trabalhos publicados no acervo -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Minhas Peças de Arquitetura Publicadas</h2></div>
                <div class="card-body space-y-3">
                    <?php if ($trabalhosPublicados === []): ?>
                        <div class="text-xs text-slate-400 p-2">Você não possui peças arquivadas no acervo oficial.</div>
                    <?php else: ?>
                        <?php foreach ($trabalhosPublicados as $pub): ?>
                            <div class="p-4 rounded-xl border border-white/5 bg-white/[0.01] flex items-center justify-between text-xs">
                                <div>
                                    <h4 class="font-bold text-white"><?= htmlspecialchars((string) ($pub['titulo'] ?? 'Trabalho')) ?></h4>
                                    <div class="text-[10px] text-slate-400 mt-1">Sessão: <?= htmlspecialchars((string) ($pub['sessao_titulo'] ?? 'Sessão')) ?> · Arquivado</div>
                                </div>
                                <?php if (!empty($pub['arquivo_pdf_path'])): ?>
                                    <a href="<?= htmlspecialchars((string) $pub['arquivo_pdf_path']) ?>" target="_blank" rel="noopener" class="btn btn-secondary text-[10px]">
                                        Baixar PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Submit new work -->
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Enviar Peça de Arquitetura</h2></div>
                <div class="card-body">
                    <form method="POST" action="/minha-loja/trabalhos/enviar" class="space-y-4 text-xs">
                        <div>
                            <label class="form-label font-bold text-slate-400">Título do Trabalho</label>
                            <input class="form-input text-xs" name="titulo" placeholder="Ex: O Aprendiz e suas ferramentas..." required>
                        </div>
                        <div>
                            <label class="form-label font-bold text-slate-400">Tipo de Trabalho</label>
                            <select class="form-select text-xs" name="tipo_trabalho" required>
                                <option value="peca_arquitetura">Peça de Arquitetura (Masonic Board)</option>
                                <option value="trabalho_apresentado">Trabalho apresentado (Instrução)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-bold text-slate-400">Sessão Vinculada (opcional)</label>
                            <select class="form-select text-xs" name="sessao_id">
                                <option value="">Nenhuma / Outra...</option>
                                <?php foreach ($sessoes as $s): ?>
                                    <option value="<?= htmlspecialchars((string) ($s['id'] ?? '')) ?>">
                                        <?= htmlspecialchars((string) ($s['titulo'] ?? 'Sessão')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label font-bold text-slate-400">URL ou Path do PDF (opcional)</label>
                            <input class="form-input text-xs" name="arquivo_pdf_path" placeholder="Ex: https://drive.google.com/... ou /uploads/...">
                        </div>
                        
                        <button class="btn btn-primary w-full text-xs font-bold" type="submit">Enviar Trabalho</button>
                    </form>
                    
                    <div class="p-3 bg-white/5 rounded-xl border border-white/5 mt-4 text-[10px] text-slate-400 leading-relaxed">
                        ℹ️ <strong>Fluxo de Aprovação:</strong> Aprendizes submetem ao 1º Vigilante, Companheiros ao 2º Vigilante e Mestres enviam diretamente à Secretaria para arquivamento no acervo da biblioteca digital.
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Tab Content: Agenda & Confirmações -->
<?php elseif ($abaAtiva === 'compromissos'): ?>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Calendário & Confirmações de Sessões (RSVP)</h2></div>
        <div class="card-body">
            <?php if ($sessoesFuturas === []): ?>
                <div class="alert alert-info text-xs">Nenhum compromisso ou sessão futura agendada pela Secretaria.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($sessoesFuturas as $sf): ?>
                        <?php 
                        $sessaoId = (int) ($sf['id'] ?? 0);
                        $isConfirmado = !empty($sf['confirmado']);
                        $resposta = (string) ($sf['resposta_usuario'] ?? '');
                        ?>
                        <div class="p-5 rounded-2xl border transition-all relative flex flex-col justify-between gap-4 <?= $isConfirmado ? 'border-emerald-500/20 bg-emerald-500/[0.01]' : 'border-white/5 bg-white/[0.01]' ?>">
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-[#C9A227] font-bold font-cinzel text-xs tracking-wider line-clamp-1">
                                        <?= htmlspecialchars((string) ($sf['titulo'] ?? 'Sessão')) ?>
                                    </span>
                                    <span class="badge px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider shrink-0 <?= $isConfirmado ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-400' ?>">
                                        <?= $isConfirmado ? 'presença confirmada' : 'não confirmada' ?>
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-400">Tipo: <span class="text-slate-200 capitalize"><?= htmlspecialchars((string) ($sf['tipo_sessao'] ?? '')) ?></span> · Grau: <span class="text-slate-200"><?= htmlspecialchars((string) ($sf['grau_sessao'] ?? '')) ?></span></div>
                                <div class="text-[10px] text-slate-400">Início: <span class="text-slate-200 font-bold"><?= $formatDate($sf['data_hora_inicio']) ?> às <?= $sf['data_hora_inicio'] ? (new DateTimeImmutable($sf['data_hora_inicio']))->format('H:i') : '' ?></span></div>
                                <?php if (!empty($sf['descricao_agape'])): ?>
                                    <div class="text-[9px] text-slate-400 italic">Ágape: <?= htmlspecialchars((string) $sf['descricao_agape']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- RSVP form action -->
                            <form method="POST" action="/minha-loja" class="flex gap-2">
                                <input type="hidden" name="action" value="sessao_confirmacao">
                                <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                                <?php if ($isConfirmado): ?>
                                    <button type="submit" name="acao" value="cancelar" class="btn btn-secondary w-full text-[10px] !py-1.5 font-bold border-danger/30 text-danger hover:bg-danger/10">
                                        Cancelar RSVP
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="acao" value="confirmar" class="btn btn-primary w-full text-[10px] !py-1.5 font-bold">
                                        Confirmar Presença
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<!-- Tab Content: Mural & Avisos -->
<?php elseif ($abaAtiva === 'mural'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Announcements (Comunicados) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title font-cinzel tracking-wider">Comunicados Oficiais</h2></div>
                <div class="card-body space-y-6">
                    <?php if ($comunicados === []): ?>
                        <div class="alert alert-info text-xs">Nenhum comunicado oficial publicado no mural da Loja.</div>
                    <?php else: ?>
                        <?php foreach ($comunicados as $com): ?>
                            <div class="p-5 rounded-2xl border border-white/5 bg-white/[0.01] space-y-3 text-xs">
                                <div class="flex justify-between items-start gap-2 border-b border-white/5 pb-2">
                                    <div>
                                        <h4 class="font-bold text-white text-sm"><?= htmlspecialchars((string) ($com['titulo'] ?? 'Comunicado')) ?></h4>
                                        <span class="text-[10px] text-slate-400 capitalize">Categoria: <?= htmlspecialchars((string) ($com['categoria'] ?? 'geral')) ?></span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 shrink-0 font-bold"><?= $formatDate($com['publicado_em']) ?></span>
                                </div>
                                <p class="text-slate-300 text-xs leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars((string) ($com['conteudo'] ?? '')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notices (Recados) -->
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Recados da Secretaria</h2></div>
                <div class="card-body space-y-4">
                    <?php if ($recados === []): ?>
                        <div class="text-xs text-slate-400 p-2">Nenhum recado ou aviso administrativo registrado.</div>
                    <?php else: ?>
                        <?php foreach ($recados as $rec): ?>
                            <div class="p-4 rounded-xl border border-white/5 bg-white/[0.02] text-xs space-y-2">
                                <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold border-b border-white/5 pb-1">
                                    <span>Administração</span>
                                    <span><?= $formatDate($rec['created_at']) ?></span>
                                </div>
                                <p class="text-slate-300 font-medium"><?= htmlspecialchars((string) ($rec['conteudo'] ?? '')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>
