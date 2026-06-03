<?php
declare(strict_types=1);

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

// Variáveis injetadas pelo controller
$sinais = isset($sinais) && is_array($sinais) ? $sinais : [];
$acompanhamentos = isset($acompanhamentos) && is_array($acompanhamentos) ? $acompanhamentos : [];
$obreiros = isset($obreiros) && is_array($obreiros) ? $obreiros : [];
$podeVerSigilosos = (bool) ($podeVerSigilosos ?? false);
$usuarioUuid = $usuarioUuid ?? null;

$authorizer = new \App\Core\Authorization\Authorizer(new \App\Core\Auth\CurrentUser($_SESSION), new \App\Core\Authorization\PermissionMap());
$podeGerenciar = $authorizer->hasPermission('vida_loja.manage');
$podeRegistrar = $authorizer->hasPermission('vida_loja.acompanhamento.create');

// Helpers de badge de nível sem o termo de cores nos rótulos
$obterBadgeNivel = static function(string $nivel): string {
    return match ($nivel) {
        'alto' => 'bg-red-500/10 text-red-400 border border-red-500/20',
        'medio' => 'bg-orange-500/10 text-orange-400 border border-orange-500/20',
        default => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
    };
};

$obterLabelNivel = static function(string $nivel): string {
    return match ($nivel) {
        'alto' => 'Cuidado Prioritário',
        'medio' => 'Atenção Sugerida',
        default => 'Acompanhamento Preventivo',
    };
};

$obterBadgeStatus = static function(string $status): string {
    return match ($status) {
        'aberto' => 'bg-red-500/15 text-red-300 border border-red-500/30',
        'em_observacao' => 'bg-yellow-500/15 text-yellow-300 border border-yellow-500/30',
        'resolvido' => 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30',
        'arquivado' => 'bg-slate-500/15 text-slate-300 border border-white/10',
        default => 'bg-slate-500/10 text-slate-400',
    };
};

$obterLabelStatus = static function(string $status): string {
    return match ($status) {
        'aberto' => 'Aberto',
        'em_observacao' => 'Em Observação',
        'resolvido' => 'Resolvido',
        'arquivado' => 'Arquivado',
        default => $status,
    };
};

$obterBadgeSigilo = static function(string $sigilo): string {
    return match ($sigilo) {
        'sigiloso' => 'bg-red-950 text-red-400 border border-red-900',
        'reservado' => 'bg-orange-950 text-orange-400 border border-orange-900',
        default => 'bg-slate-800 text-slate-300 border border-white/5',
    };
};

$appShellEyebrow = 'Vida da Loja';
$appShellTitle = 'Sinais de Cuidado & Acompanhamentos';
$appShellDescription = 'Painel de controle para monitoramento preventivo e registro de contatos de hospitalaria.';
$appShellActiveHref = '/vida-loja/sinais';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- SINAIS DE CUIDADO PREVENTIVOS -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="card-title text-white">Sinais de Cuidado Preventivos</h2>
                    <p class="card-subtitle mt-1">Ausências acumuladas e sinais gerados a partir do histórico de presenças nos últimos 60 dias.</p>
                </div>
                <span class="badge-status badge-status-primary text-xs uppercase">Sinais Ativos</span>
            </div>
            
            <div class="card-body p-6">
                <!-- Visualização em Tabela para Desktop (md+) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="table-base w-full">
                        <thead>
                            <tr class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-400">
                                <th class="pb-3 font-semibold">Irmão</th>
                                <th class="pb-3 font-semibold">Grau</th>
                                <th class="pb-3 font-semibold">Nível</th>
                                <th class="pb-3 font-semibold">Status</th>
                                <th class="pb-3 font-semibold">Detalhes</th>
                                <th class="pb-3 font-semibold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-sm">
                            <?php if (empty($sinais)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">Nenhum sinal de cuidado preventivo ativo na Loja.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sinais as $si): ?>
                                    <tr class="hover:bg-white/[0.01] transition">
                                        <td class="py-3 text-white font-medium">
                                            <?= htmlspecialchars((string) ($si['obreiro_nome'] ?? 'Sem obreiro')) ?>
                                            <span class="block text-[10px] text-slate-400">CIM: <?= htmlspecialchars((string) ($si['obreiro_cim'] ?? '-')) ?></span>
                                        </td>
                                        <td class="py-3 text-slate-300 text-xs">
                                            <?= htmlspecialchars((string) ($si['obreiro_grau'] ?? 'Aprendiz')) ?>
                                        </td>
                                        <td class="py-3">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold <?= $obterBadgeNivel($si['nivel']) ?>">
                                                <?= $obterLabelNivel($si['nivel']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold <?= $obterBadgeStatus($si['status']) ?>">
                                                <?= $obterLabelStatus($si['status']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-slate-300 text-xs max-w-xs truncate" title="<?= htmlspecialchars((string) ($si['detalhes'] ?? '')) ?>">
                                            <?= htmlspecialchars((string) ($si['detalhes'] ?? '')) ?>
                                        </td>
                                        <td class="py-3 text-right space-x-2 whitespace-nowrap">
                                            <?php if ($podeRegistrar): ?>
                                                <button onclick="preencherFormContato('<?= htmlspecialchars((string) $si['obreiro_id']) ?>', '<?= (int) $si['id'] ?>')" 
                                                        class="btn btn-success !py-1 !px-2.5 text-xs font-semibold" title="Registrar Contato">
                                                    Contatar
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($podeGerenciar): ?>
                                                <button onclick="abrirModalStatus(<?= (int) $si['id'] ?>, '<?= htmlspecialchars(addslashes((string)$si['obreiro_nome'])) ?>', '<?= htmlspecialchars((string)$si['status']) ?>')" 
                                                        class="btn border border-white/10 text-slate-300 hover:bg-white/5 !py-1 !px-2.5 text-xs font-semibold">
                                                    Status
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Visualização em Cards para Mobile (<md) -->
                <div class="block md:hidden space-y-4">
                    <?php if (empty($sinais)): ?>
                        <p class="text-center text-slate-400 py-6 text-sm">Nenhum sinal de cuidado preventivo ativo.</p>
                    <?php else: ?>
                        <?php foreach ($sinais as $si): ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-white"><?= htmlspecialchars((string) ($si['obreiro_nome'] ?? 'Sem obreiro')) ?></p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">CIM: <?= htmlspecialchars((string) ($si['obreiro_cim'] ?? '-')) ?> &middot; Grau: <?= htmlspecialchars((string) ($si['obreiro_grau'] ?? 'Aprendiz')) ?></p>
                                    </div>
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold <?= $obterBadgeStatus($si['status']) ?>">
                                        <?= $obterLabelStatus($si['status']) ?>
                                    </span>
                                </div>
                                
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold <?= $obterBadgeNivel($si['nivel']) ?>">
                                        <?= $obterLabelNivel($si['nivel']) ?>
                                    </span>
                                </div>

                                <p class="text-xs text-slate-300 leading-relaxed bg-white/[0.01] border border-white/5 rounded-lg p-2.5">
                                    <?= htmlspecialchars((string) ($si['detalhes'] ?? '')) ?>
                                </p>

                                <div class="flex justify-end gap-2 pt-2 border-t border-white/5">
                                    <?php if ($podeRegistrar): ?>
                                        <button onclick="preencherFormContato('<?= htmlspecialchars((string) $si['obreiro_id']) ?>', '<?= (int) $si['id'] ?>')" 
                                                class="btn btn-success !py-1.5 !px-3 text-xs font-bold flex-grow sm:flex-grow-0 text-center">
                                            Contatar
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($podeGerenciar): ?>
                                        <button onclick="abrirModalStatus(<?= (int) $si['id'] ?>, '<?= htmlspecialchars(addslashes((string)$si['obreiro_nome'])) ?>', '<?= htmlspecialchars((string)$si['status']) ?>')" 
                                                class="btn border border-white/10 text-slate-300 hover:bg-white/5 !py-1.5 !px-3 text-xs font-bold flex-grow sm:flex-grow-0 text-center">
                                            Alterar Status
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- HISTÓRICO DE ACOMPANHAMENTOS FRATERNOS -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="card-title text-white">Histórico de Acompanhamentos Fraternos</h2>
                    <p class="card-subtitle mt-1">Últimos contatos preventivos efetuados e visitas realizadas pela Hospitalaria.</p>
                </div>
                <span class="badge-status badge-status-secondary text-xs uppercase">Registros de Cuidado</span>
            </div>

            <div class="card-body p-6">
                <!-- Visualização em Tabela para Desktop (md+) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="table-base w-full">
                        <thead>
                            <tr class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-400">
                                <th class="pb-3 font-semibold">Data</th>
                                <th class="pb-3 font-semibold">Irmão</th>
                                <th class="pb-3 font-semibold">Meio</th>
                                <th class="pb-3 font-semibold">Sigilo</th>
                                <th class="pb-3 font-semibold">Sumário</th>
                                <th class="pb-3 font-semibold">Notas Sigilosas</th>
                                <th class="pb-3 font-semibold">Efetuado Por</th>
                                <?php if ($podeGerenciar): ?>
                                    <th class="pb-3 font-semibold text-right">Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-sm">
                            <?php if (empty($acompanhamentos)): ?>
                                <tr>
                                    <td colspan="<?= $podeGerenciar ? 8 : 7 ?>" class="py-8 text-center text-slate-400">Nenhum acompanhamento fraterno registrado nesta Oficina.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($acompanhamentos as $ac): ?>
                                    <tr class="hover:bg-white/[0.01] transition">
                                        <td class="py-3 text-slate-300 whitespace-nowrap">
                                            <?= date('d/m/Y', strtotime((string)$ac['data_contato'])) ?>
                                        </td>
                                        <td class="py-3 text-white font-medium">
                                            <?= htmlspecialchars((string) ($ac['obreiro_nome'] ?? 'Sem obreiro')) ?>
                                        </td>
                                        <td class="py-3 text-slate-300 capitalize text-xs">
                                            <?= htmlspecialchars((string) ($ac['meio_contato'] ?? 'whatsapp')) ?>
                                        </td>
                                        <td class="py-3">
                                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium <?= $obterBadgeSigilo($ac['nivel_sigilo']) ?> capitalize">
                                                <?= htmlspecialchars((string) ($ac['nivel_sigilo'] ?? 'reservado')) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-slate-300 text-xs max-w-xs truncate" title="<?= htmlspecialchars((string) ($ac['resultado'] ?? '')) ?>">
                                            <?= htmlspecialchars((string) ($ac['resultado'] ?? '')) ?>
                                        </td>
                                        <td class="py-3 text-slate-400 text-xs italic max-w-xs truncate" title="<?= htmlspecialchars((string) ($ac['observacoes_sigilosas'] ?? '')) ?>">
                                            <?= htmlspecialchars((string) ($ac['observacoes_sigilosas'] ?? '')) ?>
                                        </td>
                                        <td class="py-3 text-slate-400 text-xs">
                                            <?= htmlspecialchars((string) ($ac['responsavel_nome'] ?? '-')) ?>
                                        </td>
                                        <?php if ($podeGerenciar): ?>
                                            <td class="py-3 text-right">
                                                <button onclick="abrirModalExcluir(<?= (int) $ac['id'] ?>, '<?= htmlspecialchars(addslashes((string)$ac['obreiro_nome'])) ?>')"
                                                        class="btn border border-red-500/30 text-red-400 hover:bg-red-500/5 !py-0.5 !px-2 text-[10px] font-bold">
                                                    Excluir
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Visualização em Cards para Mobile (<md) -->
                <div class="block md:hidden space-y-4">
                    <?php if (empty($acompanhamentos)): ?>
                        <p class="text-center text-slate-400 py-6 text-sm">Nenhum acompanhamento registrado.</p>
                    <?php else: ?>
                        <?php foreach ($acompanhamentos as $ac): ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 space-y-3">
                                <div class="flex items-start justify-between gap-2 border-b border-white/5 pb-2">
                                    <div>
                                        <p class="font-bold text-white"><?= htmlspecialchars((string) ($ac['obreiro_nome'] ?? 'Sem obreiro')) ?></p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Data: <?= date('d/m/Y', strtotime((string)$ac['data_contato'])) ?> &middot; Meio: <span class="capitalize text-slate-300"><?= htmlspecialchars((string) ($ac['meio_contato'] ?? '')) ?></span></p>
                                    </div>
                                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-medium <?= $obterBadgeSigilo($ac['nivel_sigilo']) ?> capitalize">
                                        <?= htmlspecialchars((string) ($ac['nivel_sigilo'] ?? 'reservado')) ?>
                                    </span>
                                </div>

                                <div class="space-y-1.5 text-xs">
                                    <p class="text-slate-300"><span class="text-slate-400 font-medium">Resultado:</span> <?= htmlspecialchars((string) ($ac['resultado'] ?? '')) ?></p>
                                    <?php if (!empty($ac['observacoes_sigilosas'])): ?>
                                        <p class="text-slate-400 bg-white/[0.01] border border-white/5 rounded p-2 italic mt-1.5">
                                            <span class="text-[10px] font-semibold uppercase tracking-wider block text-slate-500 mb-0.5">Notas de Sigilo</span>
                                            <?= htmlspecialchars((string) ($ac['observacoes_sigilosas'] ?? '')) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-white/5 text-[10px] text-slate-400">
                                    <span>Efetuado por: <?= htmlspecialchars((string) ($ac['responsavel_nome'] ?? '')) ?></span>
                                    <?php if ($podeGerenciar): ?>
                                        <button onclick="abrirModalExcluir(<?= (int) $ac['id'] ?>, '<?= htmlspecialchars(addslashes((string)$ac['obreiro_nome'])) ?>')"
                                                class="btn border border-red-500/30 text-red-400 hover:bg-red-500/5 !py-1 !px-2.5 text-[10px] font-bold">
                                            Excluir
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        
        <?php if ($podeRegistrar): ?>
            <!-- FORMULÁRIO REGISTRAR CONTATO -->
            <div id="secao-registro-contato" class="card depth-1 p-6">
                <div class="card-header border-b border-white/5 pb-3 mb-4">
                    <h2 class="card-title text-white">Registrar Acompanhamento</h2>
                    <p class="card-subtitle mt-1">Registrar contato preventivo ou visita assistencial.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="/vida-loja/contato/salvar" class="space-y-4">
                        <input type="hidden" name="sinal_id" id="form-contato-sinal-id" value="">
                        
                        <div>
                            <label for="form-contato-obreiro-id" class="form-label">Irmão Contatado</label>
                            <select name="obreiro_id" id="form-contato-obreiro-id" required class="form-select w-full">
                                <option value="">Selecione o Obreiro...</option>
                                <?php foreach ($obreiros as $ob): ?>
                                    <option value="<?= htmlspecialchars((string) ($ob['id'] ?? '')) ?>">
                                        <?= htmlspecialchars((string) ($ob['nome_historico'] ?? $ob['nome'] ?? '')) ?> - CIM <?= htmlspecialchars((string) ($ob['cim'] ?? '-')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="data_contato" class="form-label">Data do Contato</label>
                                <input type="date" name="data_contato" id="data_contato" value="<?= date('Y-m-d') ?>" class="form-input w-full">
                            </div>
                            <div>
                                <label for="meio_contato" class="form-label">Meio de Contato</label>
                                <select name="meio_contato" id="meio_contato" class="form-select w-full">
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="telefone">Telefone</option>
                                    <option value="presencial">Presencial (Templo)</option>
                                    <option value="visita">Visita Domiciliar</option>
                                    <option value="outro">Outro meio</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="nivel_sigilo" class="form-label">Nível de Sigilo</label>
                            <select name="nivel_sigilo" id="nivel_sigilo" class="form-select w-full" onchange="document.getElementById('sigiloso_form_block').style.display = (this.value !== 'administrativo') ? 'block' : 'none';">
                                <option value="reservado" selected>Reservado (Visível à Hospitalaria e Venerável)</option>
                                <option value="sigiloso">Sigiloso (Apenas ao autor do contato e Venerável)</option>
                                <option value="administrativo">Administrativo (Livre a todos com acesso à Vida da Loja)</option>
                            </select>
                        </div>

                        <div>
                            <label for="resultado" class="form-label">Sumário do Contato (Visível Administrativamente)</label>
                            <textarea name="resultado" id="resultado" rows="2" required class="form-textarea w-full" 
                                      placeholder="Sumário livre de sigilo pessoal. Ex: Irmão contatado e informou que retornará às atividades na próxima quinzena."></textarea>
                        </div>

                        <!-- Detalhes Sigilosos (visíveis conforme permissão) -->
                        <div id="sigiloso_form_block" class="bg-white/[0.02] border border-white/5 rounded-xl p-4 space-y-2">
                            <label for="observacoes_sigilosas" class="form-label text-erp-gold font-bold text-[10px] uppercase tracking-wider flex justify-between items-center">
                                Detalhes e Notas Sigilosas (Confidencial)
                                <?php if (!$podeVerSigilosos): ?>
                                    <span class="text-[9px] text-red-400 font-normal uppercase tracking-normal">Sem permissão de leitura</span>
                                <?php endif; ?>
                            </label>
                            <textarea name="observacoes_sigilosas" id="observacoes_sigilosas" rows="3" class="form-textarea w-full text-xs" 
                                      placeholder="Descreva detalhes sigilosos. Ex: Afastado por tratamento médico na família / problemas financeiros temporários."></textarea>
                            <p class="text-[9px] text-slate-500">Estas notas serão mascaradas para usuários sem a permissão `vida_loja.sigilo.view`.</p>
                        </div>

                        <div>
                            <label for="proximo_acompanhamento" class="form-label">Agendar Próximo Contato (Opcional)</label>
                            <input type="date" name="proximo_acompanhamento" id="proximo_acompanhamento" class="form-input w-full">
                            <p class="text-[9px] text-slate-400 mt-1">Gera um aviso de planejamento no dashboard da Vida da Loja.</p>
                        </div>

                        <button type="submit" class="btn btn-primary w-full font-bold">Salvar Acompanhamento</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- INFORMAÇÕES DE INTEGRIDADE -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-2 mb-4">
                <h2 class="card-title text-white">Níveis de Cuidado</h2>
            </div>
            <div class="card-body text-xs text-slate-400 space-y-3 leading-relaxed">
                <div class="flex gap-2">
                    <span class="inline-flex rounded-full w-2 h-2 mt-1.5 shrink-0 bg-red-500"></span>
                    <p><strong class="text-white">Cuidado Prioritário:</strong> Gerado para frequência individual igual ou inferior a 50% nas últimas sessões realizadas.</p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex rounded-full w-2 h-2 mt-1.5 shrink-0 bg-orange-500"></span>
                    <p><strong class="text-white">Atenção Sugerida:</strong> Gerado para frequência individual acumulada entre 51% e 69%.</p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex rounded-full w-2 h-2 mt-1.5 shrink-0 bg-blue-500"></span>
                    <p><strong class="text-white">Em Observação:</strong> Sinal que aguarda validação de retorno estável do irmão. O comparecimento posterior a uma nova sessão move o sinal para este status de forma automática.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ############################################################################# -->
<!-- MODAL DE ATUALIZAÇÃO DE STATUS (SINAIS) -->
<!-- ############################################################################# -->
<div id="modal-status-container" class="fixed inset-0 bg-black/75 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-slate-900 border border-white/10 rounded-2xl max-w-md w-full p-6 space-y-4">
        <div class="border-b border-white/5 pb-3">
            <h3 class="text-white text-lg font-bold">Alterar Status do Sinal</h3>
            <p class="text-xs text-slate-400 mt-1">Irmão: <span id="modal-sinal-obreiro" class="text-slate-200 font-semibold"></span></p>
        </div>
        
        <form method="POST" action="/vida-loja/sinais/acao" class="space-y-4">
            <input type="hidden" name="sinal_id" id="modal-sinal-id" value="">
            
            <div>
                <label for="modal-sinal-status" class="form-label text-xs">Novo Status</label>
                <select name="status" id="modal-sinal-status" required class="form-select w-full">
                    <option value="aberto">Aberto</option>
                    <option value="em_observacao">Em Observação</option>
                    <option value="resolvido">Resolvido (Frequência regularizada/Contato efetuado)</option>
                    <option value="arquivado">Arquivado</option>
                </select>
            </div>

            <div>
                <label for="modal-sinal-motivo" class="form-label text-xs">Justificativa / Motivo do Fechamento</label>
                <textarea name="motivo_resolucao" id="modal-sinal-motivo" rows="3" class="form-textarea w-full text-xs" 
                          placeholder="Informe detalhes da resolução. Ex: Irmão justificado por motivos profissionais/doença e retorno acordado."></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                <button type="button" onclick="fecharModalStatus()" 
                        class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1.5 px-4 text-xs font-semibold rounded-xl">
                    Cancelar
                </button>
                <button type="submit" 
                        class="btn btn-primary py-1.5 px-4 text-xs font-semibold rounded-xl">
                    Confirmar Alteração
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ############################################################################# -->
<!-- MODAL DE EXCLUSÃO DE CONTATO (SOFT DELETE) -->
<!-- ############################################################################# -->
<div id="modal-excluir-container" class="fixed inset-0 bg-black/75 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-slate-900 border border-white/10 rounded-2xl max-w-md w-full p-6 space-y-4">
        <div class="border-b border-white/5 pb-3">
            <h3 class="text-white text-lg font-bold text-red-400">Excluir Contato Fraterno</h3>
            <p class="text-xs text-slate-400 mt-1">Esta ação realiza uma exclusão lógica do registro associado ao irmão: <span id="modal-excluir-obreiro" class="text-slate-200 font-semibold"></span></p>
        </div>
        
        <form method="POST" action="/vida-loja/contato/excluir" class="space-y-4">
            <input type="hidden" name="acompanhamento_id" id="modal-excluir-id" value="">
            
            <div>
                <label for="modal-excluir-motivo" class="form-label text-xs">Motivo da Exclusão (Obrigatório)</label>
                <textarea name="motivo_exclusao" id="modal-excluir-motivo" rows="3" required class="form-textarea w-full text-xs" 
                          placeholder="Descreva o motivo pelo qual este contato está sendo removido da Hospitalaria..."></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-white/5">
                <button type="button" onclick="fecharModalExcluir()" 
                        class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1.5 px-4 text-xs font-semibold rounded-xl">
                    Cancelar
                </button>
                <button type="submit" 
                        class="btn border border-red-500/20 text-red-400 hover:bg-red-500/10 py-1.5 px-4 text-xs font-semibold rounded-xl">
                    Confirmar Exclusão
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalStatus(sinalId, obreiroNome, statusAtual) {
    document.getElementById('modal-sinal-id').value = sinalId;
    document.getElementById('modal-sinal-obreiro').innerText = obreiroNome;
    document.getElementById('modal-sinal-status').value = statusAtual;
    document.getElementById('modal-sinal-motivo').value = '';
    
    const container = document.getElementById('modal-status-container');
    container.classList.remove('hidden');
    container.classList.add('flex');
}

function fecharModalStatus() {
    const container = document.getElementById('modal-status-container');
    container.classList.remove('flex');
    container.classList.add('hidden');
}

function abrirModalExcluir(contatoId, obreiroNome) {
    document.getElementById('modal-excluir-id').value = contatoId;
    document.getElementById('modal-excluir-obreiro').innerText = obreiroNome;
    document.getElementById('modal-excluir-motivo').value = '';
    
    const container = document.getElementById('modal-excluir-container');
    container.classList.remove('hidden');
    container.classList.add('flex');
}

function fecharModalExcluir() {
    const container = document.getElementById('modal-excluir-container');
    container.classList.remove('flex');
    container.classList.add('hidden');
}

function preencherFormContato(obreiroId, sinalId) {
    const obreiroSelect = document.getElementById('form-contato-obreiro-id');
    const sinalInput = document.getElementById('form-contato-sinal-id');
    
    if (obreiroSelect) {
        obreiroSelect.value = obreiroId;
    }
    if (sinalInput) {
        sinalInput.value = sinalId || '';
    }
    
    const targetSection = document.getElementById('secao-registro-contato');
    if (targetSection) {
        targetSection.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
