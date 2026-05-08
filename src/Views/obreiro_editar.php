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
$isSelf = (bool) ($isSelf ?? false);
$formAction = $isSelf ? '/meu-cadastro/atualizar' : '/obreiros/atualizar';
$showAdminOnlyCards = !$isSelf;

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = $isSelf ? 'Área do Irmão' : 'Secretaria';
$appShellTitle = $isSelf ? 'Meu Cadastro' : 'Editar Obreiro';
$appShellDescription = 'Manutenção do registro de ' . htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Obreiro');
$appShellActiveHref = $isSelf ? '/meu-cadastro' : '/obreiros';
$appShellActions = $isSelf ? [['label' => 'Voltar', 'href' => '/minha-loja']] : [['label' => 'Voltar para a Lista', 'href' => '/obreiros']];

require __DIR__ . '/partials/erp_shell_open.php';

?>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success mb-6">Ficha do Obreiro atualizada com sucesso.</div>
<?php endif; ?>
<?php if (isset($_GET['erro'])): ?>
    <div class="alert alert-danger mb-6">Não foi possível salvar. Verifique se o CIM informado já existe para outro obreiro.</div>
<?php endif; ?>

<?php if ($isSelf): ?>
    <div class="card mb-6">
        <div class="card-body flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="text-sm text-gray-600 dark:text-gray-300">Edite apenas o necessário. Correções passam a valer para suas efemérides.</div>
            <div class="flex gap-2">
                <button type="button" class="btn btn-secondary" data-self-edit-btn="start">Editar</button>
                <button type="button" class="btn btn-secondary hidden" data-self-edit-btn="cancel">Cancelar</button>
                <button type="button" class="btn btn-primary hidden" data-self-edit-btn="confirm">Confirmar</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<form action="<?= htmlspecialchars($formAction) ?>" method="POST" class="space-y-8" <?= $isSelf ? 'data-self-form="1"' : '' ?>>
    <input type="hidden" name="id" value="<?= htmlspecialchars($obreiro['id'] ?? '') ?>">

    <!-- Card: Dados Civis -->
    <div class="card" id="dados-civis">
        <div class="card-header"><h2 class="card-title">Dados Civis</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2"><label for="nome_completo" class="form-label">Nome Completo Civil</label><input id="nome_completo" type="text" name="nome_completo" required value="<?= htmlspecialchars($obreiro['nome'] ?? '') ?>" class="form-input" <?= $isSelf ? 'data-self-editable="1" disabled' : '' ?>></div>
            <div class="md:col-span-2"><label for="nome_historico" class="form-label">Nome Histórico</label><input id="nome_historico" type="text" name="nome_historico" value="<?= htmlspecialchars($obreiro['nome_historico'] ?? '') ?>" class="form-input" <?= $isSelf ? 'data-self-editable="1" disabled' : '' ?>></div>
            <div><label for="cpf" class="form-label">CPF</label><input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($obreiro['cpf'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_nascimento_civil" class="form-label">Data de Nascimento</label><input id="data_nascimento_civil" type="date" name="data_nascimento_civil" value="<?= htmlspecialchars($obreiro['data_nascimento_civil'] ?? '') ?>" class="form-input" <?= $isSelf ? 'data-self-editable="1" disabled' : '' ?>></div>
            <div><label for="estado_civil" class="form-label">Estado Civil</label><select id="estado_civil" name="estado_civil" class="form-select" <?= $isSelf ? 'data-self-editable="1" disabled' : '' ?>><?php foreach ($estadosCivis as $v => $r) echo "<option value=\"$v\" " . (($obreiro['estado_civil'] ?? '') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="telefone" class="form-label">Telefone</label><input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($obreiro['telefone'] ?? '') ?>" class="form-input" <?= $isSelf ? 'data-self-editable="1" disabled' : '' ?>></div>
            <div class="md:col-span-2"><label for="email" class="form-label">E-mail</label><input id="email" type="email" name="email" value="<?= htmlspecialchars($obreiro['email'] ?? '') ?>" class="form-input" <?= $isSelf ? 'data-self-editable="1" disabled' : '' ?>></div>
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

    <?php if ($showAdminOnlyCards): ?>
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

    <?php endif; ?>

    <!-- Ações -->
    <div class="card">
        <div class="card-body flex flex-col sm:flex-row justify-between items-center gap-4">
            <label class="form-checkbox-label"><input type="hidden" name="ativo" value="0"><input type="checkbox" id="ativo" name="ativo" value="1" <?= ($obreiro['ativo'] ?? true) ? 'checked' : '' ?> class="form-checkbox"> Registro habilitado no sistema</label>
            <div class="flex gap-3">
                <a href="<?= $isSelf ? '/minha-loja' : '/obreiros' ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><?= $isSelf ? 'Confirmar' : 'Salvar Alterações' ?></button>
            </div>
        </div>
    </div>
</form>

<?php if ($isSelf): ?>
<script>
(() => {
  const form = document.querySelector('form[data-self-form="1"]');
  if (!form) return;

  const btnStart = document.querySelector('[data-self-edit-btn="start"]');
  const btnCancel = document.querySelector('[data-self-edit-btn="cancel"]');
  const btnConfirm = document.querySelector('[data-self-edit-btn="confirm"]');

  const editable = Array.from(form.querySelectorAll('[data-self-editable="1"]'));
  const setEditing = (on) => {
    editable.forEach(el => { el.disabled = !on; });
    if (btnStart) btnStart.classList.toggle('hidden', on);
    if (btnCancel) btnCancel.classList.toggle('hidden', !on);
    if (btnConfirm) btnConfirm.classList.toggle('hidden', !on);
  };

  setEditing(false);
  btnStart && btnStart.addEventListener('click', () => setEditing(true));
  btnCancel && btnCancel.addEventListener('click', () => window.location.reload());
  btnConfirm && btnConfirm.addEventListener('click', () => form.submit());
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>



