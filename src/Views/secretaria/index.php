<?php
declare(strict_types=1);

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessaoEmFormulario = is_array($sessaoRascunho ?? null) ? $sessaoRascunho : (is_array($sessaoEdicao ?? null) ? $sessaoEdicao : []);
$modoEdicaoSessao = !is_array($sessaoRascunho ?? null) && is_array($sessaoEdicao ?? null);

$parseDateTime = static function (?string $val): ?DateTimeImmutable {
    $val = trim((string) $val);
    if ($val === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($val);
    } catch (Throwable) {
        return null;
    }
};
$formatDate = static function (?string $val) use ($parseDateTime): string {
    $dt = $parseDateTime($val);
    return $dt ? $dt->format('d/m/Y') : '-';
};
$formatDateTime = static function (?string $val) use ($parseDateTime): string {
    $dt = $parseDateTime($val);
    return $dt ? $dt->format('d/m/Y \a\s H:i') : 'Data a definir';
};
$formatInputDateTime = static function (?string $val) use ($parseDateTime): string {
    $dt = $parseDateTime($val);
    return $dt ? $dt->format('Y-m-d\TH:i') : '';
};

$badgeStatusSessao = static function (?string $status): string {
    return match (strtolower(trim((string) $status))) {
        'publicada', 'confirmada', 'ativa', 'alterada' => 'badge-success',
        'planejada' => 'badge-warning',
        'cancelada' => 'badge-danger',
        default => 'badge-secondary',
    };
};

$rascunhoEditaSessaoExistente = is_array($sessaoRascunho ?? null) && !empty($sessaoRascunho['id']);
$etapasSessao = [
    ['numero' => '1', 'titulo' => 'Preencher', 'texto' => 'Dados oficiais da agenda', 'ativo' => !$resumoRascunhoSessao],
    ['numero' => '2', 'titulo' => 'Revisar', 'texto' => 'Resumo final antes de publicar', 'ativo' => (bool) $resumoRascunhoSessao],
    ['numero' => '3', 'titulo' => 'Gerenciar', 'texto' => 'Editar, publicar, cancelar e acompanhar', 'ativo' => false],
];
$linhasVisitantesBalaustre = array_pad($visitantesBalaustre ?? [], max(3, count($visitantesBalaustre ?? []) + 1), []);
$linhasVisitasExternasBalaustre = array_pad($visitasExternasBalaustre ?? [], max(2, count($visitasExternasBalaustre ?? []) + 1), []);
$linhasObreirosPalavraBalaustre = array_pad($palavrasObreirosBalaustre ?? [], max(3, count($palavrasObreirosBalaustre ?? []) + 1), []);
$blocosBalaustre = is_array($blocosBalaustre ?? null) ? $blocosBalaustre : [];

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Painel da Secretaria';
$appShellDescription = 'GestÃ£o de obreiros, sessÃµes, balaÃºstres, acessos e convites da Loja.';
$appShellActiveHref = '/secretaria';
$appShellActions = [
    ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
    ['label' => 'Novo Obreiro', 'href' => '/obreiros/novo'],];
require __DIR__ . '/_sidebar.php';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- MÃ©tricas RÃ¡pidas -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Obreiros ativos</p><p class="card-metric-value"><?= (int) ($resumo['obreiros_ativos'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">SessÃµes futuras</p><p class="card-metric-value"><?= (int) ($resumo['sessoes_futuras'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Trabalhos pendentes</p><p class="card-metric-value"><?= (int) ($resumo['trabalhos_pendentes'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Rascunhos</p><p class="card-metric-value"><?= (int) ($resumo['publicacoes_rascunho'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">BalaÃºstres aptos</p><p class="card-metric-value"><?= (int) ($resumo['balaustres_aptos'] ?? 0) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-8">
        <!-- SaÃºde Cadastral -->
        <div class="card">
            <div class="card-header">
                <div><h2 class="card-title">SaÃºde Cadastral da Secretaria</h2><p class="card-description">Resumo para saneamento do quadro e preparo de relatÃ³rios.</p></div>
                <a href="/obreiros" class="btn btn-secondary">Central de Obreiros</a>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="card-metric-simple"><p class="card-metric-label">Total</p><p class="card-metric-value text-xl"><?= (int) ($resumoCadastros['total'] ?? 0) ?></p></div>
                    <div class="card-metric-simple"><p class="card-metric-label">No Quadro</p><p class="card-metric-value text-xl"><?= (int) ($resumoCadastros['ativos'] ?? 0) ?></p></div>
                    <div class="card-metric-simple alert-warning"><p class="card-metric-label">Com Alerta</p><p class="card-metric-value text-xl"><?= (int) ($resumoCadastros['com_alerta'] ?? 0) ?></p></div>
                    <div class="card-metric-simple"><p class="card-metric-label">Com Bot</p><p class="card-metric-value text-xl"><?= (int) ($resumoCadastros['com_telegram'] ?? 0) ?></p></div>
                </div>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="/obreiros?alerta=cadastro" class="btn btn-warning">Ver Alertas Cadastrais</a>
                    <a href="/obreiros/novo" class="btn btn-primary">Novo Obreiro</a>
                </div>
            </div>
        </div>

        <!-- Identidade e HistÃ³ria da Loja -->
        <div class="card">
            <div class="card-header">
                <div><h2 class="card-title"><?= htmlspecialchars(trim((string) (($configuracaoLoja['nome_loja'] ?? '') . ' nÂº ' . ($configuracaoLoja['numero_loja'] ?? '')), " nÂº")) ?></h2><p class="card-description">Base institucional para relatÃ³rios e histÃ³ria da oficina.</p></div>
                <?php if (!empty($configuracaoLoja['potencia_sigla']) || !empty($configuracaoLoja['potencia_nome'])): ?>
                    <span class="badge badge-primary"><?= htmlspecialchars((string) (($configuracaoLoja['potencia_sigla'] ?? '') !== '' ? $configuracaoLoja['potencia_sigla'] : ($configuracaoLoja['potencia_nome'] ?? ''))) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <div class="list-item-param"><span>Oriente</span><strong><?= htmlspecialchars((string) ($configuracaoLoja['oriente'] ?? '-')) ?></strong></div>
                    <div class="list-item-param"><span>Rito</span><strong><?= htmlspecialchars((string) ($configuracaoLoja['rito'] ?? '-')) ?></strong></div>
                    <div class="list-item-param"><span>FundaÃ§Ã£o</span><strong><?= $formatDate($configuracaoLoja['data_fundacao'] ?? null) ?></strong></div>
                    <div class="list-item-param"><span>InstalaÃ§Ã£o</span><strong><?= $formatDate($configuracaoLoja['data_instalacao'] ?? null) ?></strong></div>
                    <div class="list-item-param"><span>Templo</span><strong><?= htmlspecialchars((string) ($configuracaoLoja['nome_templo'] ?? '-')) ?></strong></div>
                    <div class="list-item-param"><span>ReuniÃµes</span><strong><?= htmlspecialchars(trim((string) (($configuracaoLoja['dia_semana_reuniao'] ?? '') . ' â€¢ ' . ($configuracaoLoja['horario_reuniao'] ?? '') . ' â€¢ ' . ($configuracaoLoja['periodicidade_reuniao'] ?? '')), ' â€¢')) ?></strong></div>
                </div>
                <div class="prose dark:prose-invert max-w-none">
                    <h3 class="font-semibold">HistÃ³ria da Loja</h3>
                    <p class="text-sm whitespace-pre-line"><?= htmlspecialchars(mb_strimwidth(trim((string) ($configuracaoLoja['historia_loja'] ?? '')), 0, 2200, '...')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral -->
    <div class="lg:col-start-3 lg:row-start-1 lg:row-span-2 space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">SessÃ£o em Foco</h2><p class="card-description">Resumo operacional da sessÃ£o selecionada.</p></div>
            <div class="card-body">
                <form method="GET" action="/secretaria">
                    <label for="sessao_resumo" class="form-label">SessÃ£o para acompanhamento</label>
                    <div class="flex gap-2">
                        <select name="sessao_resumo" id="sessao_resumo" class="form-select flex-grow">
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= (int) ($sessaoResumo['id'] ?? 0) === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'SessÃ£o') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Ver</button>
                    </div>
                </form>

                <?php if (!empty($sessaoResumo)): ?>
                    <div class="list-item-report mt-6">
                        <p class="font-semibold"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?: (($sessaoResumo['tipo_sessao'] ?? 'SessÃ£o') . ' - ' . ($sessaoResumo['grau_sessao'] ?? '')))) ?></p>
                        <p class="text-sm text-gray-500"><?= $formatDateTime((string) ($sessaoResumo['data_hora_inicio'] ?? '')) ?> &bull; Status: <span class="font-medium"><?= htmlspecialchars((string) ($sessaoResumo['status'] ?? '')) ?></span></p>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <div class="card-metric-simple"><p class="card-metric-label">Confirmados</p><p class="card-metric-value text-lg"><?= (int) ($sessaoResumo['total_confirmados'] ?? 0) ?></p></div>
                        <div class="card-metric-simple"><p class="card-metric-label">Ausentes</p><p class="card-metric-value text-lg"><?= (int) ($sessaoResumo['total_ausentes'] ?? 0) ?></p></div>
                        <div class="card-metric-simple"><p class="card-metric-label">Ãgape</p><p class="card-metric-value text-lg"><?= (int) ($sessaoResumo['total_agape'] ?? 0) ?></p></div>
                    </div>
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold mb-3">Confirmados na SessÃ£o</h3>
                            <ul class="space-y-2 max-h-60 overflow-y-auto">
                                <?php foreach ($confirmadosSessaoResumo as $confirmado): ?>
                                    <li class="list-item-detail text-sm"><span><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'IrmÃ£o')) ?></span><span class="font-mono text-xs"><?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></span></li>
                                <?php endforeach; ?>
                                <?php if (empty($confirmadosSessaoResumo)): ?><li class="text-center text-sm text-gray-500 py-4">Sem confirmaÃ§Ãµes.</li><?php endif; ?>
                            </ul>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-3">Participantes do Ãgape</h3>
                            <ul class="space-y-2 max-h-60 overflow-y-auto">
                                <?php foreach ($participantesAgapeResumo as $participanteAgape): ?>
                                    <li class="list-item-detail text-sm"><span><?= htmlspecialchars((string) ($participanteAgape['nome'] ?? 'IrmÃ£o')) ?></span><span class="font-mono text-xs"><?= htmlspecialchars((string) ($participanteAgape['cim'] ?? '-')) ?></span></li>
                                <?php endforeach; ?>
                                <?php if (empty($participantesAgapeResumo)): ?><li class="text-center text-sm text-gray-500 py-4">Sem participantes no Ã¡gape.</li><?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-6 text-center py-8 text-gray-500"><p>Nenhuma sessÃ£o disponÃ­vel para resumo.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gerenciamento de SessÃµes -->
    <div class="card mt-8">
        <div class="card-header">
            <div>
                <h2 class="card-title">Gerenciamento de Sessï¿½es</h2>
                <p class="card-description">Fluxo operacional: preencher, revisar, publicar e acompanhar a agenda oficial.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">
                <?php foreach ($etapasSessao as $etapaSessao): ?>
                    <div class="list-item-param !items-start <?= !empty($etapaSessao['ativo']) ? 'border-l-4 border-blue-600' : '' ?>">
                        <span class="badge <?= !empty($etapaSessao['ativo']) ? 'badge-primary' : 'badge-secondary' ?>">Etapa <?= htmlspecialchars($etapaSessao['numero']) ?></span>
                        <strong class="mt-2"><?= htmlspecialchars($etapaSessao['titulo']) ?></strong>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars($etapaSessao['texto']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($resumoRascunhoSessao): ?>
                <div class="alert alert-warning mb-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div><h3 class="font-bold"><?= $rascunhoEditaSessaoExistente ? 'Revisï¿½o final da atualizaï¿½ï¿½o' : 'Revisï¿½o final da nova sessï¿½o' ?></h3><p class="text-sm"><?= $rascunhoEditaSessaoExistente ? 'Confira o resumo antes de atualizar a sessï¿½o jï¿½ existente.' : 'Confira o texto final antes de publicar a nova sessï¿½o.' ?></p></div>
                        <?php if ($sessaoDuplicada): ?><span class="badge badge-danger">SessÃ£o semelhante encontrada no mesmo dia/horÃ¡rio</span><?php endif; ?>
                    </div>
                    <pre class="mt-4 whitespace-pre-wrap rounded-md bg-white dark:bg-gray-800 p-4 text-sm font-mono"><?= htmlspecialchars($resumoRascunhoSessao) ?></pre>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <?php foreach ($acoesConfirmacaoRascunho as $acaoRascunho): ?>
                            <span class="badge badge-secondary"><?= htmlspecialchars((string) ($acaoRascunho['label'] ?? '')) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <form method="POST" action="/secretaria/sessoes/publicar-rascunho"><button type="submit" class="btn btn-primary"><?= $rascunhoEditaSessaoExistente ? 'Confirmar atualizaï¿½ï¿½o' : 'Confirmar publicaï¿½ï¿½o' ?></button></form>
                        <form method="POST" action="/secretaria/sessoes/cancelar-rascunho"><button type="submit" class="btn btn-secondary">Descartar revisï¿½o</button></form>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/secretaria/sessoes/salvar" class="space-y-6">
                <?php if ($modoEdicaoSessao): ?><input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFormulario['id'] ?? 0) ?>"><?php endif; ?>
                <div class="list-item-report flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-lg"><?= $modoEdicaoSessao ? 'Editar sessÃ£o existente' : 'Nova sessÃ£o' ?></h3>
                        <p class="text-sm text-gray-600"><?= $modoEdicaoSessao ? 'Revise os dados da sessÃ£o e confirme a atualizaÃ§Ã£o.' : 'Preencha os dados da nova sessÃ£o para revisÃ£o.' ?></p>
                    </div>
                    <?php if ($modoEdicaoSessao): ?><a href="/secretaria" class="btn btn-secondary">Cancelar EdiÃ§Ã£o</a><?php endif; ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2"><label for="titulo" class="form-label">TÃ­tulo da sessÃ£o</label><input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars((string) ($sessaoEmFormulario['titulo'] ?? '')) ?>" class="form-input"></div>
                    <div><label for="data_hora_inicio" class="form-label">Data e Hora de InÃ­cio</label><input type="datetime-local" id="data_hora_inicio" name="data_hora_inicio" required value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_inicio'] ?? null) ?>" class="form-input"></div>
                    <div><label for="data_hora_fim" class="form-label">Encerramento Previsto</label><input type="datetime-local" id="data_hora_fim" name="data_hora_fim" value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_fim'] ?? null) ?>" class="form-input"></div>
                    <div><label for="grau_sessao" class="form-label">Grau da SessÃ£o</label><select id="grau_sessao" name="grau_sessao" class="form-select"><?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Outro'] as $g): ?><option value="<?= $g ?>" <?= (($sessaoEmFormulario['grau_sessao'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
                    <div><label for="grau_personalizado" class="form-label">Grau (livre)</label><input type="text" id="grau_personalizado" name="grau_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['grau_personalizado'] ?? '')) ?>" placeholder="Se grau for 'Outro'" class="form-input"></div>
                    <div><label for="tipo_sessao_principal" class="form-label">Tipo Principal</label><select id="tipo_sessao_principal" name="tipo_sessao_principal" class="form-select"><option value="economica" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? 'economica') === 'economica') ? 'selected' : '' ?>>EconÃ´mica</option><option value="magna" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'magna') ? 'selected' : '' ?>>Magna</option><option value="outra" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'outra') ? 'selected' : '' ?>>Outra</option></select></div>
                    <div><label for="tipo_sessao_subtipo" class="form-label">Subtipo</label><select id="tipo_sessao_subtipo" name="tipo_sessao_subtipo" class="form-select"><?php foreach (['economica_1' => 'EconÃ´mica de 1Âº Grau', 'economica_2' => 'EconÃ´mica de 2Âº Grau', 'economica_3' => 'EconÃ´mica de 3Âº Grau', 'magna_iniciacao' => 'Magna de IniciaÃ§Ã£o', 'magna_elevacao' => 'Magna de ElevaÃ§Ã£o', 'magna_exaltacao' => 'Magna de ExaltaÃ§Ã£o', 'magna_instalacao' => 'Magna de InstalaÃ§Ã£o', 'outra' => 'Outra'] as $v => $l): ?><option value="<?= $v ?>" <?= (($sessaoEmFormulario['tipo_sessao_subtipo'] ?? '') === $v) ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                    <div class="md:col-span-2"><label for="tipo_sessao_personalizado" class="form-label">Tipo (livre)</label><input type="text" id="tipo_sessao_personalizado" name="tipo_sessao_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['tipo_sessao_personalizado'] ?? '')) ?>" placeholder="Se tipo ou subtipo nÃ£o estiver na lista" class="form-input"></div>
                    <div><label for="traje_tipo" class="form-label">Traje</label><select id="traje_tipo" name="traje_tipo" class="form-select"><option value="maconico" <?= (($sessaoEmFormulario['traje_tipo'] ?? 'maconico') === 'maconico') ? 'selected' : '' ?>>MaÃ§Ã´nico</option><option value="livre" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'livre') ? 'selected' : '' ?>>Livre</option><option value="outro" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'outro') ? 'selected' : '' ?>>Outro</option></select></div>
                    <div><label for="traje_personalizado" class="form-label">Traje (livre)</label><input type="text" id="traje_personalizado" name="traje_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['traje_personalizado'] ?? '')) ?>" placeholder="Se traje for 'Outro'" class="form-input"></div>
                    <div><label for="agape_modalidade" class="form-label">Ãgape</label><select id="agape_modalidade" name="agape_modalidade" class="form-select"><option value="nao_havera" <?= (($sessaoEmFormulario['agape_modalidade'] ?? 'nao_havera') === 'nao_havera') ? 'selected' : '' ?>>NÃ£o haverÃ¡</option><option value="gratuito" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'gratuito') ? 'selected' : '' ?>>Sim (gratuito)</option><option value="pago" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'pago') ? 'selected' : '' ?>>Sim (pago)</option></select></div>
                    <div><label for="agape_modelo_financeiro" class="form-label">Modelo Financeiro do Ãgape</label><select id="agape_modelo_financeiro" name="agape_modelo_financeiro" class="form-select"><option value="oficial_loja" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? 'oficial_loja') === 'oficial_loja') ? 'selected' : '' ?>>Oficial da Loja</option><option value="particular" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? '') === 'particular') ? 'selected' : '' ?>>Particular</option><option value="misto" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? '') === 'misto') ? 'selected' : '' ?>>Misto (Loja + particular)</option></select></div>
                    <div><label for="agape_valor" class="form-label">Valor do Ãgape (opcional)</label><input type="text" id="agape_valor" name="agape_valor" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['agape_valor'] ?? '')) ?>" placeholder="Definido pelo M. de Banquetes" class="form-input"></div>
                    <div><label for="gestao_referencia" class="form-label">GestÃ£o de ReferÃªncia</label><input type="text" id="gestao_referencia" name="gestao_referencia" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['gestao_referencia'] ?? '')) ?>" placeholder="Ex: 2026/2027" class="form-input"></div>
                    <div class="md:col-span-2"><label for="ordem_dia" class="form-label">Ordem do Dia / ObservaÃ§Ãµes</label><textarea id="ordem_dia" name="ordem_dia" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($sessaoEmFormulario['ordem_dia'] ?? '')) ?></textarea></div>
                    <div class="md:col-span-2"><label class="form-check-label"><input type="checkbox" name="conta_relatorio_potencia" value="1" class="form-checkbox" <?= !array_key_exists('conta_relatorio_potencia', $sessaoEmFormulario) || !empty($sessaoEmFormulario['conta_relatorio_potencia']) ? 'checked' : '' ?>> Contabilizar no relatÃ³rio oficial da potÃªncia</label></div>
                </div>
                <div class="pt-6 border-t border-gray-200 dark:border-gray-700"><button type="submit" class="btn btn-primary"><?= $modoEdicaoSessao ? 'Revisar atualizaÃ§Ã£o da sessÃ£o' : 'Continuar para revisÃ£o' ?></button></div>
            </form>
        </div>
    </div>

    <!-- Lista de SessÃµes -->
    <div class="mt-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($sessoes)): ?>
                <div class="md:col-span-2 lg:col-span-3 text-center py-12 text-gray-500"><p>Nenhuma sessÃ£o futura cadastrada. Use o formulÃ¡rio acima para publicar a agenda oficial da Loja.</p></div>
            <?php else: ?>
                <?php foreach ($sessoes as $sessao): ?>
                <div class="card flex flex-col">
                    <div class="card-body flex-grow">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-bold"><?= htmlspecialchars((string) ($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'SessÃ£o') . ' - ' . ($sessao['grau_sessao'] ?? '')))) ?></h3>
                            <span class="badge <?= $badgeStatusSessao($sessao['status'] ?? null) ?> text-xs"><?= htmlspecialchars((string) ($sessao['status'] ?: 'planejada')) ?></span>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div class="list-item-param !p-3"><span>Data</span><strong><?= $formatDateTime((string) ($sessao['data_hora_inicio'] ?? '')) ?></strong></div>
                            <div class="list-item-param !p-3"><span>Confirmados</span><strong><?= (int) ($sessao['total_confirmados'] ?? 0) ?><?php if ((int) ($sessao['total_agape'] ?? 0) > 0): ?><span class="text-sm font-normal text-gray-500"> / <?= (int) ($sessao['total_agape'] ?? 0) ?> Ã¡gape</span><?php endif; ?></strong></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="flex flex-wrap gap-2">
                            <a href="/secretaria?editar_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm btn-secondary">Editar</a>
                            <?php if (in_array($sessao['status'] ?? '', ['planejada', 'alterada'], true)): ?>
                                <form method="POST" action="/secretaria/sessoes/publicar" onsubmit="return confirm('Deseja publicar esta sessÃ£o agora?');"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button type="submit" class="btn btn-sm btn-warning">Publicar</button></form>
                            <?php endif; ?>
                            <?php if (($sessao['status'] ?? '') !== 'cancelada'): ?>
                                <form method="POST" action="/secretaria/sessoes/cancelar" onsubmit="return confirm('Deseja cancelar esta sessÃ£o?');"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button type="submit" class="btn btn-sm btn-danger">Cancelar</button></form>
                            <?php else: ?>
                                <form method="POST" action="/secretaria/sessoes/reabrir" onsubmit="return confirm('Deseja reabrir esta sessÃ£o?');"><input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>"><button type="submit" class="btn btn-sm btn-success">Reabrir</button></form>
                            <?php endif; ?>
                            <a href="/secretaria?historico_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm btn-outline">HistÃ³rico</a>
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
                    <div><h3 class="card-title">HistÃ³rico Operacional</h3><p class="card-description"><?= htmlspecialchars((string) ($sessaoHistorico['titulo'] ?: (($sessaoHistorico['tipo_sessao'] ?? 'SessÃ£o') . ' - ' . ($sessaoHistorico['grau_sessao'] ?? '')))) ?></p></div>
                    <a href="/secretaria" class="text-gray-400 hover:text-gray-600">&times;</a>
                </div>
                <div class="card-body max-h-96 overflow-y-auto">
                    <div class="space-y-4">
                        <?php if (empty($historicoSessao)): ?>
                            <p class="text-center text-gray-500 py-8">Ainda nÃ£o hÃ¡ histÃ³rico registrado para esta sessÃ£o.</p>
                        <?php else: ?>
                            <?php foreach ($historicoSessao as $itemHistorico): ?>
                                <div class="list-item-report">
                                    <div class="flex flex-wrap items-center justify-between text-sm">
                                        <p class="font-semibold"><?= htmlspecialchars((string) ($itemHistorico['acao'] ?? 'AÃ§Ã£o')) ?></p>
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

    <!-- Trabalhos e Balaustre -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <div class="card"><div class="card-header"><h2 class="card-title">Trabalhos e Peï¿½as de Arquitetura</h2><p class="card-description">Controle de trabalhos para a Ordem do Dia.</p></div><div class="card-body text-center text-gray-500"><p>Em breve: formulï¿½rio e lista de trabalhos refatorados.</p></div></div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Balaï¿½stre da Sessï¿½o em Foco</h2>
                    <p class="card-description">Rascunho prï¿½-preenchido com sessï¿½o, nominata e campos essenciais da ata.</p>
                </div>
                <?php if (!empty($balaustreSessao)): ?>
                    <span class="badge <?= $badgeStatusSessao($balaustreSessao['status'] ?? null) ?>"><?= htmlspecialchars((string) ($balaustreSessao['status'] ?? 'rascunho')) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($sessaoResumo) && empty($modoBalaustreIndependente)): ?>
                    <div class="space-y-4">
                        <div class="text-sm text-gray-600">Vocï¿½ pode preparar o balaï¿½stre manualmente em qualquer momento. Primeiro selecione a sessï¿½o que serï¿½ a base do rascunho.</div>
                        <form method="GET" action="/secretaria" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="md:col-span-2">
                                <label for="sessao_resumo_balaustre" class="form-label">Sessï¿½o para preparar o balaï¿½stre</label>
                                <select name="sessao_resumo" id="sessao_resumo_balaustre" class="form-select">
                                    <?php foreach ($sessoes as $sessaoOpcao): ?>
                                        <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessï¿½o') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button type="submit" class="btn btn-primary w-full">Carregar formulï¿½rio</button>
                            </div>
                        </form>
                        <div class="text-xs text-gray-500">Depois de carregar, revise e edite todos os campos antes de salvar. O rascunho pode ser salvo e retomado depois.</div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="/secretaria/balaustres/salvar" class="space-y-6">
                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoResumo['id'] ?? 0) ?>">
                        <input type="hidden" name="balaustre_id" value="<?= (int) ($balaustreSessao['id'] ?? 0) ?>">
                        <?php if (!empty($modoBalaustreIndependente)): ?><input type="hidden" name="balaustre_independente" value="1"><?php endif; ?>
                        <div class="list-item-report">
                            <p class="font-semibold"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?: (($sessaoResumo['tipo_sessao'] ?? 'Sessï¿½o') . ' - ' . ($sessaoResumo['grau_sessao'] ?? '')))) ?></p>
                            <p class="text-sm text-gray-500"><?= $formatDateTime((string) ($sessaoResumo['data_hora_inicio'] ?? '')) ?> ï¿½ <?= (int) ($sessaoResumo['total_presentes'] ?? 0) ?> presenï¿½a(s) registradas</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label for="numero_balaustre" class="form-label">Nï¿½mero do balaï¿½stre</label><input id="numero_balaustre" name="numero_balaustre" value="<?= htmlspecialchars((string) ($balaustreSessao['numero_balaustre'] ?? '')) ?>" class="form-input" placeholder="Ex: 012/2026"></div>
                            <div><label for="template_versao" class="form-label">Modelo</label><input id="template_versao" name="template_versao" value="<?= htmlspecialchars((string) ($balaustreSessao['template_versao'] ?? 'oficial-v1')) ?>" class="form-input"></div>
                        </div>

                        <div>
                            <h3 class="font-semibold mb-3">Redacao oficial por blocos</h3>
                            <div class="space-y-3">
                                <div><label class="form-label">Abertura</label><textarea name="bloco_abertura" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['abertura'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Balaustre</label><textarea name="bloco_balaustre" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['balaustre'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Expediente</label><textarea name="bloco_expediente" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['expediente'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Saco de Propostas e Informacoes</label><textarea name="bloco_saco_propostas" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['saco_propostas'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Ordem do Dia</label><textarea name="bloco_ordem_dia" rows="4" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['ordem_dia'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Tronco de Solidariedade</label><textarea name="bloco_tronco_solidariedade" rows="2" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['tronco_solidariedade'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Conclusoes do Orador</label><textarea name="bloco_conclusoes_orador" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['conclusoes_orador'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Encerramento</label><textarea name="bloco_encerramento" rows="3" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['encerramento'] ?? '')) ?></textarea></div>
                                <div><label class="form-label">Assinaturas</label><textarea name="bloco_assinaturas" rows="2" class="form-textarea"><?= htmlspecialchars((string) ($blocosBalaustre['assinaturas'] ?? 'Secretario              Guarda da Lei              Veneravel Mestre')) ?></textarea></div>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold mb-3">Cargos da sessï¿½o</h3>
                            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                                <?php foreach ($cargosBalaustreSessao as $cargoSessao): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 list-item-param !items-start">
                                        <input type="hidden" name="cargo_sessao_codigo[]" value="<?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?>">
                                        <input type="hidden" name="cargo_sessao_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '')) ?>">
                                        <input type="hidden" name="cargo_sessao_titular_oficial[]" value="<?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '')) ?>">
                                        <div><span>Cargo</span><strong><?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '-')) ?></strong></div>
                                        <div><span>Titular oficial</span><strong><?= htmlspecialchars((string) (($cargoSessao['titular_oficial'] ?? '') !== '' ? $cargoSessao['titular_oficial'] : '-')) ?></strong></div>
                                        <div><label class="form-label">Ocupante em Loja</label><input name="cargo_sessao_ocupante_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['ocupante_nome'] ?? $cargoSessao['titular_oficial'] ?? '')) ?>" class="form-input"></div>
                                        <div><label class="form-label">Observaï¿½ï¿½o</label><input name="cargo_sessao_observacao[]" value="<?= htmlspecialchars((string) ($cargoSessao['observacao'] ?? '')) ?>" class="form-input" placeholder="ad hoc, ausï¿½ncia..."></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold mb-3">Palavra a bem da Ordem: quadro oficial</h3>
                            <div class="space-y-3">
                                <?php foreach ($linhasObreirosPalavraBalaustre as $palavraObreiro): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 list-item-param !items-start">
                                        <select name="palavra_obreiro_id[]" class="form-select">
                                            <option value="">Selecionar obreiro</option>
                                            <?php foreach ($obreiros as $obreiroPalavraOpcao): ?>
                                                <option value="<?= htmlspecialchars((string) ($obreiroPalavraOpcao['id'] ?? '')) ?>" <?= (string) ($palavraObreiro['obreiro_id'] ?? '') === (string) ($obreiroPalavraOpcao['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars((string) ($obreiroPalavraOpcao['nome'] ?? 'Obreiro')) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input name="palavra_obreiro_nome[]" value="<?= htmlspecialchars((string) ($palavraObreiro['nome'] ?? '')) ?>" class="form-input" placeholder="Obreiro">
                                        <input name="palavra_obreiro_cargo[]" value="<?= htmlspecialchars((string) ($palavraObreiro['cargo_no_momento'] ?? '')) ?>" class="form-input" placeholder="Cargo no momento">
                                        <input name="palavra_obreiro_fala[]" value="<?= htmlspecialchars((string) ($palavraObreiro['fala_resumida'] ?? '')) ?>" class="form-input md:col-span-3" placeholder="Fala resumida">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold mb-3">Palavra a bem da Ordem: visitantes</h3>
                            <div class="space-y-3">
                                <?php foreach ($linhasVisitantesBalaustre as $visitante): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 list-item-param !items-start">
                                        <input name="palavra_visitante_nome[]" value="<?= htmlspecialchars((string) ($visitante['nome'] ?? '')) ?>" class="form-input" placeholder="Nome do visitante">
                                        <input name="palavra_visitante_loja[]" value="<?= htmlspecialchars((string) ($visitante['loja'] ?? '')) ?>" class="form-input" placeholder="Loja">
                                        <input name="palavra_visitante_oriente[]" value="<?= htmlspecialchars((string) ($visitante['oriente'] ?? '')) ?>" class="form-input" placeholder="Oriente">
                                        <input name="palavra_visitante_potencia[]" value="<?= htmlspecialchars((string) ($visitante['potencia'] ?? '')) ?>" class="form-input" placeholder="Potï¿½ncia">
                                        <input name="palavra_visitante_grau[]" value="<?= htmlspecialchars((string) ($visitante['grau'] ?? '')) ?>" class="form-input" placeholder="Grau">
                                        <input name="palavra_visitante_fala[]" value="<?= htmlspecialchars((string) ($visitante['fala_resumida'] ?? '')) ?>" class="form-input" placeholder="Fala resumida">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold mb-3">Convites e visitas de terceiros</h3>
                            <div class="space-y-3">
                                <?php foreach ($linhasVisitasExternasBalaustre as $visitaExterna): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 list-item-param !items-start">
                                        <input name="visita_externa_obreiro_nome[]" value="<?= htmlspecialchars((string) ($visitaExterna['obreiro_nome'] ?? '')) ?>" class="form-input" placeholder="Obreiro responsï¿½vel">
                                        <input name="visita_externa_loja[]" value="<?= htmlspecialchars((string) ($visitaExterna['loja'] ?? '')) ?>" class="form-input" placeholder="Loja visitada / convidada">
                                        <input name="visita_externa_oriente[]" value="<?= htmlspecialchars((string) ($visitaExterna['oriente'] ?? '')) ?>" class="form-input" placeholder="Oriente">
                                        <input name="visita_externa_potencia[]" value="<?= htmlspecialchars((string) ($visitaExterna['potencia_obediencia'] ?? '')) ?>" class="form-input" placeholder="Potï¿½ncia">
                                        <input type="date" name="visita_externa_data[]" value="<?= htmlspecialchars((string) ($visitaExterna['data_visita'] ?? '')) ?>" class="form-input">
                                        <input name="visita_externa_observacao[]" value="<?= htmlspecialchars((string) ($visitaExterna['observacao'] ?? '')) ?>" class="form-input" placeholder="Observaï¿½ï¿½o">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div><label for="texto_final" class="form-label">Texto final do balaï¿½stre (snapshot oficial)</label><textarea id="texto_final" name="texto_final" rows="5" class="form-textarea" readonly><?= htmlspecialchars((string) ($balaustreSessao['texto_final'] ?? '')) ?></textarea></div>
                        <div><label for="observacoes_secretaria" class="form-label">Observaï¿½ï¿½es da Secretaria</label><textarea id="observacoes_secretaria" name="observacoes_secretaria" rows="3" class="form-textarea"><?= htmlspecialchars($observacoesBalaustre) ?></textarea></div>
                        <div><label class="form-label">Previa oficial (modelo estrito)</label><pre class="whitespace-pre-wrap rounded-md bg-white dark:bg-gray-800 p-4 text-sm font-mono"><?= htmlspecialchars((string) ($previewTextoOficialBalaustre ?? '')) ?></pre></div>

                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="submit" class="btn btn-primary">Salvar balaï¿½stre</button>
                            <?php if (!empty($sessaoResumo['id']) && !empty($balaustreSessao['id']) && !in_array($balaustreSessao['status'] ?? '', ['apto_votacao', 'em_votacao', 'aprovado'], true)): ?>
                                <button type="submit" form="marcar-balaustre-apto" class="btn btn-warning">Marcar apto para votaï¿½ï¿½o</button>
                            <?php endif; ?>
                            <a href="/secretaria/votacao" class="btn btn-secondary">Acompanhar votaï¿½ï¿½es</a>
                        </div>
                    </form>
                    <?php if (!empty($sessaoResumo['id']) && !empty($balaustreSessao['id']) && !in_array($balaustreSessao['status'] ?? '', ['apto_votacao', 'em_votacao', 'aprovado'], true)): ?>
                        <form id="marcar-balaustre-apto" method="POST" action="/secretaria/balaustres/apto" onsubmit="return confirm('Marcar este balaï¿½stre como apto para votaï¿½ï¿½o?');">
                            <input type="hidden" name="balaustre_id" value="<?= (int) ($balaustreSessao['id'] ?? 0) ?>">
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


