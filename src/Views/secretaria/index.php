<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessaoEmFormulario = is_array($sessaoRascunho ?? null) ? $sessaoRascunho : (is_array($sessaoEdicao ?? null) ? $sessaoEdicao : []);
$modoEdicaoSessao = !is_array($sessaoRascunho ?? null) && is_array($sessaoEdicao ?? null);

$formatDate = static fn(?string $val): string => !empty(trim((string) $val)) ? (new DateTimeImmutable(trim((string) $val)))->format('d/m/Y') : '-';
$formatDateTime = static fn(?string $val): string => !empty(trim((string) $val)) ? (new DateTimeImmutable(trim((string) $val)))->format('d/m/Y \à\s H:i') : 'Data a definir';
$formatInputDateTime = static fn(?string $val): string => !empty(trim((string) $val)) ? (new DateTimeImmutable(trim((string) $val)))->format('Y-m-d\TH:i') : '';

$badgeStatusSessao = static function (?string $status): string {
    return match (strtolower(trim((string) $status))) {
        'publicada', 'confirmada', 'ativa', 'alterada' => 'badge-success',
        'planejada' => 'badge-warning',
        'cancelada' => 'badge-danger',
        default => 'badge-secondary',
    };
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Painel da Secretaria';
$appShellDescription = 'Gestão de obreiros, sessões, balaústres, acessos e convites da Loja.';
$appShellActiveHref = '/secretaria';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Obreiros ativos</p><p class="card-metric-value"><?= (int) ($resumo['obreiros_ativos'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Sessões futuras</p><p class="card-metric-value"><?= (int) ($resumo['sessoes_futuras'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Trabalhos pendentes</p><p class="card-metric-value"><?= (int) ($resumo['trabalhos_pendentes'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Rascunhos</p><p class="card-metric-value"><?= (int) ($resumo['publicacoes_rascunho'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Balaústres aptos</p><p class="card-metric-value"><?= (int) ($resumo['balaustres_aptos'] ?? 0) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-8">
        <!-- Saúde Cadastral -->
        <div class="card">
            <div class="card-header">
                <div><h2 class="card-title">Saúde Cadastral da Secretaria</h2><p class="card-description">Resumo para saneamento do quadro e preparo de relatórios.</p></div>
                <a href="/obreiros" class="btn btn-secondary">Central de Obreiros</a>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="card-metric-simple"><p class="card-metric-label">Total</p><p class="card-metric-value text-xl"><?= (int) ($resumoRegistros['total'] ?? 0) ?></p></div>
                    <div class="card-metric-simple"><p class="card-metric-label">No Quadro</p><p class="card-metric-value text-xl"><?= (int) ($resumoRegistros['ativos'] ?? 0) ?></p></div>
                    <div class="card-metric-simple alert-warning"><p class="card-metric-label">Com Alerta</p><p class="card-metric-value text-xl"><?= (int) ($resumoRegistros['com_alerta'] ?? 0) ?></p></div>
                    <div class="card-metric-simple"><p class="card-metric-label">Com Bot</p><p class="card-metric-value text-xl"><?= (int) ($resumoRegistros['com_telegram'] ?? 0) ?></p></div>
                </div>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="/obreiros?alerta=cadastro" class="btn btn-warning">Ver Alertas Cadastrais</a>
                    <a href="/obreiros/novo" class="btn btn-primary">Novo Obreiro</a>
                </div>
            </div>
        </div>

        <!-- Identidade e História da Loja -->
        <div class="card">
            <div class="card-header">
                <div><h2 class="card-title"><?= htmlspecialchars(trim((string) (($configuracaoLoja['nome_loja'] ?? '') . ' nº ' . ($configuracaoLoja['numero_loja'] ?? '')), " nº")) ?></h2><p class="card-description">Base institucional para relatórios e história da oficina.</p></div>
                <?php if (!empty($configuracaoLoja['potencia_sigla']) || !empty($configuracaoLoja['potencia_nome'])): ?>
                    <span class="badge badge-primary"><?= htmlspecialchars((string) (($configuracaoLoja['potencia_sigla'] ?? '') !== '' ? $configuracaoLoja['potencia_sigla'] : ($configuracaoLoja['potencia_nome'] ?? ''))) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <div class="list-item-param"><span>Oriente</span><strong><?= htmlspecialchars((string) ($configuracaoLoja['oriente'] ?? '-')) ?></strong></div>
                    <div class="list-item-param"><span>Rito</span><strong><?= htmlspecialchars((string) ($configuracaoLoja['rito'] ?? '-')) ?></strong></div>
                    <div class="list-item-param"><span>Fundação</span><strong><?= $formatDate($configuracaoLoja['data_fundacao'] ?? null) ?></strong></div>
                    <div class="list-item-param"><span>Instalação</span><strong><?= $formatDate($configuracaoLoja['data_instalacao'] ?? null) ?></strong></div>
                    <div class="list-item-param"><span>Templo</span><strong><?= htmlspecialchars((string) ($configuracaoLoja['nome_templo'] ?? '-')) ?></strong></div>
                    <div class="list-item-param"><span>Reuniões</span><strong><?= htmlspecialchars(trim((string) (($configuracaoLoja['dia_semana_reuniao'] ?? '') . ' • ' . ($configuracaoLoja['horario_reuniao'] ?? '') . ' • ' . ($configuracaoLoja['periodicidade_reuniao'] ?? '')), ' •')) ?></strong></div>
                </div>
                <div class="prose dark:prose-invert max-w-none">
                    <h3 class="font-semibold">História da Loja</h3>
                    <p class="text-sm whitespace-pre-line"><?= htmlspecialchars(mb_strimwidth(trim((string) ($configuracaoLoja['historia_loja'] ?? '')), 0, 2200, '...')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral -->
    <div class="lg:col-start-3 lg:row-start-1 lg:row-span-2 space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Sessão em Foco</h2><p class="card-description">Resumo operacional da sessão selecionada.</p></div>
            <div class="card-body">
                <form method="GET" action="/secretaria">
                    <label for="sessao_resumo" class="form-label">Sessão para acompanhamento</label>
                    <div class="flex gap-2">
                        <select name="sessao_resumo" id="sessao_resumo" class="form-select flex-grow">
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= (int) ($sessaoResumo['id'] ?? 0) === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Ver</button>
                    </div>
                </form>

                <?php if (!empty($sessaoResumo)): ?>
                    <div class="list-item-report mt-6">
                        <p class="font-semibold"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?: (($sessaoResumo['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoResumo['grau_sessao'] ?? '')))) ?></p>
                        <p class="text-sm text-gray-500"><?= $formatDateTime((string) ($sessaoResumo['data_hora_inicio'] ?? '')) ?> &bull; Status: <span class="font-medium"><?= htmlspecialchars((string) ($sessaoResumo['status'] ?? '')) ?></span></p>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <div class="card-metric-simple"><p class="card-metric-label">Confirmados</p><p class="card-metric-value text-lg"><?= (int) ($sessaoResumo['total_confirmados'] ?? 0) ?></p></div>
                        <div class="card-metric-simple"><p class="card-metric-label">Ausentes</p><p class="card-metric-value text-lg"><?= (int) ($sessaoResumo['total_ausentes'] ?? 0) ?></p></div>
                        <div class="card-metric-simple"><p class="card-metric-label">Ágape</p><p class="card-metric-value text-lg"><?= (int) ($sessaoResumo['total_agape'] ?? 0) ?></p></div>
                    </div>
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold mb-3">Confirmados na Sessão</h3>
                            <ul class="space-y-2 max-h-60 overflow-y-auto">
                                <?php foreach ($confirmadosSessaoResumo as $confirmado): ?>
                                    <li class="list-item-detail text-sm"><span><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Irmão')) ?></span><span class="font-mono text-xs"><?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></span></li>
                                <?php endforeach; ?>
                                <?php if (empty($confirmadosSessaoResumo)): ?><li class="text-center text-sm text-gray-500 py-4">Sem confirmações.</li><?php endif; ?>
                            </ul>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-3">Participantes do Ágape</h3>
                            <ul class="space-y-2 max-h-60 overflow-y-auto">
                                <?php foreach ($participantesAgapeResumo as $participanteAgape): ?>
                                    <li class="list-item-detail text-sm"><span><?= htmlspecialchars((string) ($participanteAgape['nome'] ?? 'Irmão')) ?></span><span class="font-mono text-xs"><?= htmlspecialchars((string) ($participanteAgape['cim'] ?? '-')) ?></span></li>
                                <?php endforeach; ?>
                                <?php if (empty($participantesAgapeResumo)): ?><li class="text-center text-sm text-gray-500 py-4">Sem participantes no ágape.</li><?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-6 text-center py-8 text-gray-500"><p>Nenhuma sessão disponível para resumo.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gerenciamento de Sessões -->
    <div class="card mt-8">
        <div class="card-header"><h2 class="card-title">Gerenciamento de Sessões</h2><p class="card-description">Crie, edite e publique a agenda oficial da Loja.</p></div>
        <div class="card-body">
            <?php if ($resumoRascunhoSessao): ?>
                <div class="alert alert-warning mb-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div><h3 class="font-bold">Revisão Final de Rascunho</h3><p class="text-sm">Resumo pronto para publicação.</p></div>
                        <?php if ($sessaoDuplicada): ?><span class="badge badge-danger">Sessão semelhante encontrada no mesmo dia/horário</span><?php endif; ?>
                    </div>
                    <pre class="mt-4 whitespace-pre-wrap rounded-md bg-white dark:bg-gray-800 p-4 text-sm font-mono"><?= htmlspecialchars($resumoRascunhoSessao) ?></pre>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <?php foreach ($acoesConfirmacaoRascunho as $acaoRascunho): ?>
                            <span class="badge badge-secondary"><?= htmlspecialchars((string) ($acaoRascunho['label'] ?? '')) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <form method="POST" action="/secretaria/sessoes/publicar-rascunho"><button type="submit" class="btn btn-primary">Confirmar Publicação</button></form>
                        <form method="POST" action="/secretaria/sessoes/cancelar-rascunho"><button type="submit" class="btn btn-secondary">Cancelar Rascunho</button></form>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/secretaria/sessoes/salvar" class="space-y-6">
                <?php if ($modoEdicaoSessao): ?><input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFormulario['id'] ?? 0) ?>"><?php endif; ?>
                <div class="list-item-report flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg"><?= $modoEdicaoSessao ? 'Editar sessão existente' : 'Nova sessão' ?></h3>
                        <p class="text-sm text-gray-600"><?= $modoEdicaoSessao ? 'Revise os dados da sessão e confirme a atualização.' : 'Preencha os dados da nova sessão para revisão.' ?></p>
                    </div>
                    <?php if ($modoEdicaoSessao): ?><a href="/secretaria" class="btn btn-secondary">Cancelar Edição</a><?php endif; ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2"><label for="titulo" class="form-label">Título da sessão</label><input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars((string) ($sessaoEmFormulario['titulo'] ?? '')) ?>" class="form-input"></div>
                    <div><label for="data_hora_inicio" class="form-label">Data e Hora de Início</label><input type="datetime-local" id="data_hora_inicio" name="data_hora_inicio" required value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_inicio'] ?? null) ?>" class="form-input"></div>
                    <div><label for="data_hora_fim" class="form-label">Encerramento Previsto</label><input type="datetime-local" id="data_hora_fim" name="data_hora_fim" value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_fim'] ?? null) ?>" class="form-input"></div>
                    <div><label for="grau_sessao" class="form-label">Grau da Sessão</label><select id="grau_sessao" name="grau_sessao" class="form-select"><?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Outro'] as $g): ?><option value="<?= $g ?>" <?= (($sessaoEmFormulario['grau_sessao'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
                    <div><label for="grau_personalizado" class="form-label">Grau (livre)</label><input type="text" id="grau_personalizado" name="grau_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['grau_personalizado'] ?? '')) ?>" placeholder="Se grau for 'Outro'" class="form-input"></div>
                    <div><label for="tipo_sessao_principal" class="form-label">Tipo Principal</label><select id="tipo_sessao_principal" name="tipo_sessao_principal" class="form-select"><option value="economica" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? 'economica') === 'economica') ? 'selected' : '' ?>>Econômica</option><option value="magna" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'magna') ? 'selected' : '' ?>>Magna</option><option value="outra" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'outra') ? 'selected' : '' ?>>Outra</option></select></div>
                    <div><label for="tipo_sessao_subtipo" class="form-label">Subtipo</label><select id="tipo_sessao_subtipo" name="tipo_sessao_subtipo" class="form-select"><?php foreach (['economica_1' => 'Econômica de 1º Grau', 'economica_2' => 'Econômica de 2º Grau', 'economica_3' => 'Econômica de 3º Grau', 'magna_iniciacao' => 'Magna de Iniciação', 'magna_elevacao' => 'Magna de Elevação', 'magna_exaltacao' => 'Magna de Exaltação', 'magna_instalacao' => 'Magna de Instalação', 'outra' => 'Outra'] as $v => $l): ?><option value="<?= $v ?>" <?= (($sessaoEmFormulario['tipo_sessao_subtipo'] ?? '') === $v) ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                    <div class="md:col-span-2"><label for="tipo_sessao_personalizado" class="form-label">Tipo (livre)</label><input type="text" id="tipo_sessao_personalizado" name="tipo_sessao_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['tipo_sessao_personalizado'] ?? '')) ?>" placeholder="Se tipo ou subtipo não estiver na lista" class="form-input"></div>
                    <div><label for="traje_tipo" class="form-label">Traje</label><select id="traje_tipo" name="traje_tipo" class="form-select"><option value="maconico" <?= (($sessaoEmFormulario['traje_tipo'] ?? 'maconico') === 'maconico') ? 'selected' : '' ?>>Maçônico</option><option value="livre" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'livre') ? 'selected' : '' ?>>Livre</option><option value="outro" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'outro') ? 'selected' : '' ?>>Outro</option></select></div>
                    <div><label for="traje_personalizado" class="form-label">Traje (livre)</label><input type="text" id="traje_personalizado" name="traje_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['traje_personalizado'] ?? '')) ?>" placeholder="Se traje for 'Outro'" class="form-input"></div>
                    <div><label for="agape_modalidade" class="form-label">Ágape</label><select id="agape_modalidade" name="agape_modalidade" class="form-select"><option value="nao_havera" <?= (($sessaoEmFormulario['agape_modalidade'] ?? 'nao_havera') === 'nao_havera') ? 'selected' : '' ?>>Não haverá</option><option value="gratuito" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'gratuito') ? 'selected' : '' ?>>Sim (gratuito)</option><option value="pago" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'pago') ? 'selected' : '' ?>>Sim (pago)</option></select></div>
                    <div><label for="agape_modelo_financeiro" class="form-label">Modelo Financeiro do Ágape</label><select id="agape_modelo_financeiro" name="agape_modelo_financeiro" class="form-select"><option value="oficial_loja" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? 'oficial_loja') === 'oficial_loja') ? 'selected' : '' ?>>Oficial da Loja</option><option value="particular" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? '') === 'particular') ? 'selected' : '' ?>>Particular</option><option value="misto" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? '') === 'misto') ? 'selected' : '' ?>>Misto (Loja + particular)</option></select></div>
                    <div><label for="agape_valor" class="form-label">Valor do Ágape (opcional)</label><input type="text" id="agape_valor" name="agape_valor" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['agape_valor'] ?? '')) ?>" placeholder="Definido pelo M. de Banquetes" class="form-input"></div>
                    <div><label for="gestao_referencia" class="form-label">Gestão de Referência</label><input type="text" id="gestao_referencia" name="gestao_referencia" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['gestao_referencia'] ?? '')) ?>" placeholder="Ex: 2026/2027" class="form-input"></div>
                    <div class="md:col-span-2"><label for="ordem_dia" class="form-label">Ordem do Dia / Observações</label><textarea id="ordem_dia" name="ordem_dia" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($sessaoEmFormulario['ordem_dia'] ?? '')) ?></textarea></div>
                    <div class="md:col-span-2"><label class="form-check-label"><input type="checkbox" name="conta_relatorio_potencia" value="1" class="form-checkbox" <?= !array_key_exists('conta_relatorio_potencia', $sessaoEmFormulario) || !empty($sessaoEmFormulario['conta_relatorio_potencia']) ? 'checked' : '' ?>> Contabilizar no relatório oficial da potência</label></div>
                </div>
                <div class="pt-6 border-t border-gray-200 dark:border-gray-700"><button type="submit" class="btn btn-primary"><?= $modoEdicaoSessao ? 'Revisar atualização da sessão' : 'Continuar para revisão' ?></button></div>
            </form>
        </div>
    </div>

    <!-- Lista de Sessões -->
    <div class="mt-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($sessoes)): ?>
                <div class="md:col-span-2 lg:col-span-3 text-center py-12 text-gray-500"><p>Nenhuma sessão futura cadastrada. Use o formulário acima para publicar a agenda oficial da Loja.</p></div>
            <?php else: ?>
                <?php foreach ($sessoes as $sessao): ?>
                <div class="card flex flex-col">
                    <div class="card-body flex-grow">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-bold"><?= htmlspecialchars((string) ($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessao['grau_sessao'] ?? '')))) ?></h3>
                            <span class="badge <?= $badgeStatusSessao($sessao['status'] ?? null) ?> text-xs"><?= htmlspecialchars((string) ($sessao['status'] ?: 'planejada')) ?></span>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div class="list-item-param !p-3"><span>Data</span><strong><?= $formatDateTime((string) ($sessao['data_hora_inicio'] ?? '')) ?></strong></div>
                            <div class="list-item-param !p-3"><span>Confirmados</span><strong><?= (int) ($sessao['total_confirmados'] ?? 0) ?><?php if ((int) ($sessao['total_agape'] ?? 0) > 0): ?><span class="text-sm font-normal text-gray-500"> / <?= (int) ($sessao['total_agape'] ?? 0) ?> ágape</span><?php endif; ?></strong></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="flex flex-wrap gap-2">
                            <a href="/secretaria?editar_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm btn-secondary">Editar</a>
                            <?php if (in_array($sessao['status'] ?? '', ['planejada', 'alterada'], true)): ?>
                                <form method="POST" action="/secretaria/sessoes/publicar" onsubmit="return confirm('Deseja publicar esta sessão agora?');"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button type="submit" class="btn btn-sm btn-warning">Publicar</button></form>
                            <?php endif; ?>
                            <?php if (($sessao['status'] ?? '') !== 'cancelada'): ?>
                                <form method="POST" action="/secretaria/sessoes/cancelar" onsubmit="return confirm('Deseja cancelar esta sessão?');"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button type="submit" class="btn btn-sm btn-danger">Cancelar</button></form>
                            <?php else: ?>
                                <form method="POST" action="/secretaria/sessoes/reabrir" onsubmit="return confirm('Deseja reabrir esta sessão?');"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button type="submit" class="btn btn-sm btn-success">Reabrir</button></form>
                            <?php endif; ?>
                            <a href="/secretaria?historico_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm btn-outline">Histórico</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($sessaoHistorico)): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-data="{ open: true }" x-show="open">
            <div class="card max-w-2xl w-full m-4" @click.away="open = false">
                <div class="card-header">
                    <div><h3 class="card-title">Histórico Operacional</h3><p class="card-description"><?= htmlspecialchars((string) ($sessaoHistorico['titulo'] ?: (($sessaoHistorico['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoHistorico['grau_sessao'] ?? '')))) ?></p></div>
                    <a href="/secretaria" class="text-gray-400 hover:text-gray-600">&times;</a>
                </div>
                <div class="card-body max-h-96 overflow-y-auto">
                    <div class="space-y-4">
                        <?php if (empty($historicoSessao)): ?>
                            <p class="text-center text-gray-500 py-8">Ainda não há histórico registrado para esta sessão.</p>
                        <?php else: ?>
                            <?php foreach ($historicoSessao as $itemHistorico): ?>
                                <div class="list-item-report">
                                    <div class="flex flex-wrap items-center justify-between text-sm">
                                        <p class="font-semibold"><?= htmlspecialchars((string) ($itemHistorico['acao'] ?? 'Ação')) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars((string) ($itemHistorico['autor_nome'] ?? 'Sistema')) ?> &bull; <?= htmlspecialchars((string) ($itemHistorico['created_at'] ?? '')) ?></p>
                                    </div>
                                    <?php if (!empty($itemHistorico['observacao'])): ?><p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars((string) $itemHistorico['observacao']) ?></p><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer text-right"><a href="/secretaria" class="btn btn-secondary">Fechar</a></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Placeholders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <div class="card"><div class="card-header"><h2 class="card-title">Trabalhos e Peças de Arquitetura</h2><p class="card-description">Controle de trabalhos para a Ordem do Dia.</p></div><div class="card-body text-center text-gray-500"><p>Em breve: formulário e lista de trabalhos refatorados.</p></div></div>
        <div class="card"><div class="card-header"><h2 class="card-title">Balaústre e Votação</h2><p class="card-description">Preparação do balaústre para votação.</p></div><div class="card-body text-center text-gray-500"><p>Em breve: formulário de balaústre refatorado.</p></div></div>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4; }
    .card-footer { @apply p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-sm text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-5; }

    .card-metric { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md p-5; }
    .card-metric-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400; }
    .card-metric-value { @apply mt-1 text-3xl font-bold; }
    
    .card-metric-simple { @apply bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4; }
    .card-metric-simple .card-metric-label { @apply text-xs uppercase tracking-wider; }
    .card-metric-simple .card-metric-value { @apply text-xl; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-input, .form-select, .form-textarea { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }
    .form-checkbox { @apply h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500; }
    .form-check-label { @apply flex items-center text-sm text-gray-700 dark:text-gray-300; }
    .form-check-label .form-checkbox { @apply mr-2; }

    .list-item-param { @apply flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md text-sm; }
    .list-item-detail { @apply flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700/50 rounded-md; }
    .list-item-report { @apply p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg; }

    .alert { @apply px-4 py-3 rounded-lg; }
    .alert-success { @apply bg-green-100 dark:bg-green-900/20 border border-green-400 text-green-700 dark:text-green-300; }
    .alert-danger { @apply bg-red-100 dark:bg-red-900/20 border border-red-400 text-red-700 dark:text-red-300; }
    .alert-warning { @apply bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-400 text-yellow-700 dark:text-yellow-300; }

    .badge { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold; }
    .badge-success { @apply bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-200; }
    .badge-danger { @apply bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-200; }
    .badge-warning { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-200; }
    .badge-primary { @apply bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-200; }
    .badge-secondary { @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200; }
</style>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

