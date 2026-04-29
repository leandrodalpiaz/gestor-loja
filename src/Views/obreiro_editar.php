<?php
declare(strict_types=1);

// #############################################################################
// SEGURANÇA E PREPARAÇÃO
// #############################################################################

if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$obreiro = $obreiro ?? [];
$acessosStatus = ['pendente' => 'Em análise', 'ativo' => 'Regular', 'inativo' => 'Afastado'];
$estadosCivis = ['solteiro' => 'Solteiro', 'casado' => 'Casado', 'divorciado' => 'Divorciado', 'separado' => 'Separado', 'viuvo' => 'Viúvo', 'uniao_estavel' => 'União estável', 'nao_informado' => 'Não informado'];
$escolaridades = ['fundamental_incompleto' => 'Fundamental incompleto', 'fundamental_completo' => 'Fundamental completo', 'medio_incompleto' => 'Médio incompleto', 'medio_completo' => 'Médio completo', 'tecnico' => 'Técnico', 'superior_incompleto' => 'Superior incompleto', 'superior_completo' => 'Superior completo', 'pos_graduacao' => 'Pós-graduação', 'mestrado' => 'Mestrado', 'doutorado' => 'Doutorado', 'nao_informado' => 'Não informado'];
$faixasRenda = ['ate_1_sm' => 'Até 1 salário mínimo', 'de_1_a_3_sm' => 'De 1 a 3 salários mínimos', 'de_3_a_5_sm' => 'De 3 a 5 salários mínimos', 'de_5_a_10_sm' => 'De 5 a 10 salários mínimos', 'acima_10_sm' => 'Acima de 10 salários mínimos', 'nao_informado' => 'Não informado'];
$situacoesQuadro = ['ativo' => 'Regular', 'licenciado' => 'Licenciado', 'suspenso' => 'Suspenso', 'desligado' => 'Desligado', 'falecido' => 'Falecido', 'oriente_eterno' => 'Oriente Eterno', 'inativo' => 'Afastado'];

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Editar Obreiro';
$appShellDescription = 'Manutenção do cadastro de ' . htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Obreiro');
$appShellActiveHref = '/obreiros';
$appShellActions = [['label' => 'Voltar para a Lista', 'href' => '/obreiros']];

require __DIR__ . '/partials/erp_shell_open.php';
?>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success mb-6">Ficha do Obreiro atualizada com sucesso.</div>
<?php endif; ?>
<?php if (isset($_GET['erro'])): ?>
    <div class="alert alert-danger mb-6">Não foi possível salvar. Verifique se o CIM informado já existe para outro obreiro.</div>
<?php endif; ?>

<form action="/obreiros/atualizar" method="POST" class="space-y-8">
    <input type="hidden" name="id" value="<?= htmlspecialchars($obreiro['id'] ?? '') ?>">

    <!-- Card: Dados Civis -->
    <div class="card" id="dados-civis">
        <div class="card-header"><h2 class="card-title">Dados Civis</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2"><label for="nome_completo" class="form-label">Nome Completo Civil</label><input id="nome_completo" type="text" name="nome_completo" required value="<?= htmlspecialchars($obreiro['nome'] ?? '') ?>" class="form-input"></div>
            <div class="md:col-span-2"><label for="nome_historico" class="form-label">Nome Histórico</label><input id="nome_historico" type="text" name="nome_historico" value="<?= htmlspecialchars($obreiro['nome_historico'] ?? '') ?>" class="form-input"></div>
            <div><label for="cpf" class="form-label">CPF</label><input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($obreiro['cpf'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_nascimento_civil" class="form-label">Data de Nascimento</label><input id="data_nascimento_civil" type="date" name="data_nascimento_civil" value="<?= htmlspecialchars($obreiro['data_nascimento_civil'] ?? '') ?>" class="form-input"></div>
            <div><label for="estado_civil" class="form-label">Estado Civil</label><select id="estado_civil" name="estado_civil" class="form-select"><?php foreach ($estadosCivis as $v => $r) echo "<option value=\"$v\" " . (($obreiro['estado_civil'] ?? '') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="telefone" class="form-label">Telefone</label><input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($obreiro['telefone'] ?? '') ?>" class="form-input"></div>
            <div class="md:col-span-2"><label for="email" class="form-label">E-mail</label><input id="email" type="email" name="email" value="<?= htmlspecialchars($obreiro['email'] ?? '') ?>" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Dados Maçônicos -->
    <div class="card" id="dados-maconicos">
        <div class="card-header"><h2 class="card-title">Dados Maçônicos</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="cim" class="form-label">CIM</label><input id="cim" type="text" name="cim" value="<?= htmlspecialchars($obreiro['cim'] ?? '') ?>" class="form-input"></div>
            <div><label for="grau" class="form-label">Grau</label><select id="grau" name="grau" required class="form-select"><?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $g) echo "<option value=\"$g\" " . (($obreiro['grau'] ?? '') === $g ? 'selected' : '') . ">$g</option>"; ?></select></div>
            <div><label for="acesso_status" class="form-label">Status de Acesso</label><select id="acesso_status" name="acesso_status" class="form-select"><?php $acessoAtual = strtolower(trim($obreiro['acesso_status'] ?? '')); if (!in_array($acessoAtual, ['pendente', 'ativo', 'inativo'], true)) $acessoAtual = !empty($obreiro['ativo']) ? 'ativo' : 'inativo'; foreach ($acessosStatus as $v => $r) echo "<option value=\"$v\" " . ($acessoAtual === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="cargo" class="form-label">Cargo (Legado)</label><input id="cargo" type="text" name="cargo" value="<?= htmlspecialchars($obreiro['cargo'] ?? '') ?>" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Cargos Oficiais (Nominata)</label><div class="form-static-field"><?= !empty($obreiro['cargos_codigos']) ? htmlspecialchars(implode(', ', (array) $obreiro['cargos_codigos'])) : 'Sem cargo oficial ativo' ?> <a href="/admin/cargos" class="link ml-2">Abrir Nominata</a></div></div>
            <div><label for="loja_origem" class="form-label">Loja de Origem</label><input id="loja_origem" type="text" name="loja_origem" value="<?= htmlspecialchars($obreiro['loja_origem'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_iniciacao" class="form-label">Data de Iniciação</label><input id="data_iniciacao" type="date" name="data_iniciacao" value="<?= htmlspecialchars($obreiro['data_iniciacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_elevacao" class="form-label">Data de Elevação</label><input id="data_elevacao" type="date" name="data_elevacao" value="<?= htmlspecialchars($obreiro['data_elevacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_exaltacao" class="form-label">Data de Exaltação</label><input id="data_exaltacao" type="date" name="data_exaltacao" value="<?= htmlspecialchars($obreiro['data_exaltacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_filiacao" class="form-label">Data de Filiação</label><input id="data_filiacao" type="date" name="data_filiacao" value="<?= htmlspecialchars($obreiro['data_filiacao'] ?? '') ?>" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Perfil Estatístico -->
    <div class="card" id="perfil-estatistico">
        <div class="card-header"><h2 class="card-title">Perfil Estatístico</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="profissao" class="form-label">Profissão</label><input id="profissao" type="text" name="profissao" value="<?= htmlspecialchars($obreiro['profissao'] ?? '') ?>" class="form-input"></div>
            <div><label for="escolaridade" class="form-label">Escolaridade</label><select id="escolaridade" name="escolaridade" class="form-select"><?php foreach ($escolaridades as $v => $r) echo "<option value=\"$v\" " . (($obreiro['escolaridade'] ?? '') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="faixa_renda" class="form-label">Faixa de Renda</label><select id="faixa_renda" name="faixa_renda" class="form-select"><?php foreach ($faixasRenda as $v => $r) echo "<option value=\"$v\" " . (($obreiro['faixa_renda'] ?? '') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
        </div>
    </div>

    <!-- Card: Situação no Quadro -->
    <div class="card" id="situacao-quadro">
        <div class="card-header"><h2 class="card-title">Situação no Quadro</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="situacao_quadro" class="form-label">Situação</label><select id="situacao_quadro" name="situacao_quadro" class="form-select"><?php foreach ($situacoesQuadro as $v => $r) echo "<option value=\"$v\" " . (($obreiro['situacao_quadro'] ?? 'ativo') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="data_regularizacao" class="form-label">Data de Regularização</label><input id="data_regularizacao" type="date" name="data_regularizacao" value="<?= htmlspecialchars($obreiro['data_regularizacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_reintegracao" class="form-label">Data de Reintegração</label><input id="data_reintegracao" type="date" name="data_reintegracao" value="<?= htmlspecialchars($obreiro['data_reintegracao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_quite_placet" class="form-label">Data de Quite-Placet</label><input id="data_quite_placet" type="date" name="data_quite_placet" value="<?= htmlspecialchars($obreiro['data_quite_placet'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_suspensao" class="form-label">Data de Suspensão</label><input id="data_suspensao" type="date" name="data_suspensao" value="<?= htmlspecialchars($obreiro['data_suspensao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_desligamento" class="form-label">Data de Desligamento</label><input id="data_desligamento" type="date" name="data_desligamento" value="<?= htmlspecialchars($obreiro['data_desligamento'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_oriente_eterno" class="form-label">Data de Oriente Eterno</label><input id="data_oriente_eterno" type="date" name="data_oriente_eterno" value="<?= htmlspecialchars($obreiro['data_oriente_eterno'] ?? '') ?>" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Vínculos e Gestão -->
    <div class="card" id="vinculos-gestao">
        <div class="card-header"><h2 class="card-title">Vínculos e Gestão</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="potencia_nome" class="form-label">Potência</label><input id="potencia_nome" type="text" name="potencia_nome" value="<?= htmlspecialchars($obreiro['potencia_nome'] ?? '') ?>" class="form-input"></div>
            <div><label for="potencia_sigla" class="form-label">Sigla da Potência</label><input id="potencia_sigla" type="text" name="potencia_sigla" value="<?= htmlspecialchars($obreiro['potencia_sigla'] ?? '') ?>" class="form-input"></div>
            <div><label for="numero_quadro" class="form-label">Número no Quadro</label><input id="numero_quadro" type="text" name="numero_quadro" value="<?= htmlspecialchars($obreiro['numero_quadro'] ?? '') ?>" class="form-input"></div>
            <div><label class="form-label">Telegram ID</label><div class="form-static-field"><?= htmlspecialchars($obreiro['telegram_id'] ?? 'Não vinculado') ?></div><p class="form-hint">Vínculo controlado apenas pelo bot (não editável aqui).</p></div>
            <div><label for="potencia_login" class="form-label">Login na Potência</label><input id="potencia_login" type="text" name="potencia_login" value="<?= htmlspecialchars($obreiro['potencia_login'] ?? '') ?>" class="form-input"></div>
            <div class="flex items-center"><label class="form-checkbox-label"><input type="checkbox" name="acesso_potencia_liberado" value="1" <?= !empty($obreiro['acesso_potencia_liberado']) ? 'checked' : '' ?> class="form-checkbox"> Acesso na plataforma da Potência liberado</label></div>
            <div class="md:col-span-2"><label for="observacao_secretaria" class="form-label">Observação da Secretaria</label><textarea id="observacao_secretaria" name="observacao_secretaria" rows="3" class="form-textarea"><?= htmlspecialchars($obreiro['observacao_secretaria'] ?? '') ?></textarea></div>
        </div>
    </div>

    <!-- Ações -->
    <div class="card">
        <div class="card-body flex flex-col sm:flex-row justify-between items-center gap-4">
            <label class="form-checkbox-label"><input type="hidden" name="ativo" value="0"><input type="checkbox" id="ativo" name="ativo" value="1" <?= ($obreiro['ativo'] ?? true) ? 'checked' : '' ?> class="form-checkbox"> Registro habilitado no sistema</label>
            <div class="flex gap-3">
                <a href="/obreiros" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </div>
    </div>
</form>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-body { @apply p-5; }

    .alert { @apply p-4 rounded-md text-sm; }
    .alert-success { @apply bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300; }
    .alert-danger { @apply bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-input, .form-select, .form-textarea { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }
    .form-static-field { @apply w-full px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600/50; }
    .form-hint { @apply text-xs text-gray-500 dark:text-gray-400 mt-1; }
    .form-checkbox { @apply h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500; }
    .form-checkbox-label { @apply flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300; }
    
    .link { @apply text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 underline; }

    .btn { @apply inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-900 transition-colors; }
    .btn-primary { @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500; }
    .btn-secondary { @apply bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500; }
</style>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>


