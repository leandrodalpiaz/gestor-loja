<?php
declare(strict_types=1);

// #############################################################################
// SEGURANÃ‡A E PREPARAÃ‡ÃƒO
// #############################################################################

if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E HELPERS
// #############################################################################

$obreiro = $obreiro ?? [];
$acessosStatus = ['pendente' => 'Em anÃ¡lise', 'ativo' => 'Regular', 'inativo' => 'Afastado'];
$estadosCivis = ['solteiro' => 'Solteiro', 'casado' => 'Casado', 'divorciado' => 'Divorciado', 'separado' => 'Separado', 'viuvo' => 'ViÃºvo', 'uniao_estavel' => 'UniÃ£o estÃ¡vel', 'nao_informado' => 'NÃ£o informado'];
$escolaridades = ['fundamental_incompleto' => 'Fundamental incompleto', 'fundamental_completo' => 'Fundamental completo', 'medio_incompleto' => 'MÃ©dio incompleto', 'medio_completo' => 'MÃ©dio completo', 'tecnico' => 'TÃ©cnico', 'superior_incompleto' => 'Superior incompleto', 'superior_completo' => 'Superior completo', 'pos_graduacao' => 'PÃ³s-graduaÃ§Ã£o', 'mestrado' => 'Mestrado', 'doutorado' => 'Doutorado', 'nao_informado' => 'NÃ£o informado'];
$faixasRenda = ['ate_1_sm' => 'AtÃ© 1 salÃ¡rio mÃ­nimo', 'de_1_a_3_sm' => 'De 1 a 3 salÃ¡rios mÃ­nimos', 'de_3_a_5_sm' => 'De 3 a 5 salÃ¡rios mÃ­nimos', 'de_5_a_10_sm' => 'De 5 a 10 salÃ¡rios mÃ­nimos', 'acima_10_sm' => 'Acima de 10 salÃ¡rios mÃ­nimos', 'nao_informado' => 'NÃ£o informado'];
$situacoesQuadro = ['ativo' => 'Regular', 'licenciado' => 'Licenciado', 'suspenso' => 'Suspenso', 'desligado' => 'Desligado', 'falecido' => 'Falecido', 'oriente_eterno' => 'Oriente Eterno', 'inativo' => 'Afastado'];

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Editar Obreiro';
$appShellDescription = 'ManutenÃ§Ã£o do cadastro de ' . htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Obreiro');
$appShellActiveHref = '/obreiros';
$appShellActions = [['label' => 'Voltar para a Lista', 'href' => '/obreiros']];

require __DIR__ . '/partials/erp_shell_open.php';
?>

<?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success mb-6">Ficha do Obreiro atualizada com sucesso.</div>
<?php endif; ?>
<?php if (isset($_GET['erro'])): ?>
    <div class="alert alert-danger mb-6">NÃ£o foi possÃ­vel salvar. Verifique se o CIM informado jÃ¡ existe para outro obreiro.</div>
<?php endif; ?>

<form action="/obreiros/atualizar" method="POST" class="space-y-8">
    <input type="hidden" name="id" value="<?= htmlspecialchars($obreiro['id'] ?? '') ?>">

    <!-- Card: Dados Civis -->
    <div class="card" id="dados-civis">
        <div class="card-header"><h2 class="card-title">Dados Civis</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2"><label for="nome_completo" class="form-label">Nome Completo Civil</label><input id="nome_completo" type="text" name="nome_completo" required value="<?= htmlspecialchars($obreiro['nome'] ?? '') ?>" class="form-input"></div>
            <div class="md:col-span-2"><label for="nome_historico" class="form-label">Nome HistÃ³rico</label><input id="nome_historico" type="text" name="nome_historico" value="<?= htmlspecialchars($obreiro['nome_historico'] ?? '') ?>" class="form-input"></div>
            <div><label for="cpf" class="form-label">CPF</label><input id="cpf" type="text" name="cpf" value="<?= htmlspecialchars($obreiro['cpf'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_nascimento_civil" class="form-label">Data de Nascimento</label><input id="data_nascimento_civil" type="date" name="data_nascimento_civil" value="<?= htmlspecialchars($obreiro['data_nascimento_civil'] ?? '') ?>" class="form-input"></div>
            <div><label for="estado_civil" class="form-label">Estado Civil</label><select id="estado_civil" name="estado_civil" class="form-select"><?php foreach ($estadosCivis as $v => $r) echo "<option value=\"$v\" " . (($obreiro['estado_civil'] ?? '') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="telefone" class="form-label">Telefone</label><input id="telefone" type="text" name="telefone" value="<?= htmlspecialchars($obreiro['telefone'] ?? '') ?>" class="form-input"></div>
            <div class="md:col-span-2"><label for="email" class="form-label">E-mail</label><input id="email" type="email" name="email" value="<?= htmlspecialchars($obreiro['email'] ?? '') ?>" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Dados MaÃ§Ã´nicos -->
    <div class="card" id="dados-maconicos">
        <div class="card-header"><h2 class="card-title">Dados MaÃ§Ã´nicos</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="cim" class="form-label">CIM</label><input id="cim" type="text" name="cim" value="<?= htmlspecialchars($obreiro['cim'] ?? '') ?>" class="form-input"></div>
            <div><label for="grau" class="form-label">Grau</label><select id="grau" name="grau" required class="form-select"><?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $g) echo "<option value=\"$g\" " . (($obreiro['grau'] ?? '') === $g ? 'selected' : '') . ">$g</option>"; ?></select></div>
            <div><label for="acesso_status" class="form-label">Status de Acesso</label><select id="acesso_status" name="acesso_status" class="form-select"><?php $acessoAtual = strtolower(trim($obreiro['acesso_status'] ?? '')); if (!in_array($acessoAtual, ['pendente', 'ativo', 'inativo'], true)) $acessoAtual = !empty($obreiro['ativo']) ? 'ativo' : 'inativo'; foreach ($acessosStatus as $v => $r) echo "<option value=\"$v\" " . ($acessoAtual === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="cargo" class="form-label">Cargo (Legado)</label><input id="cargo" type="text" name="cargo" value="<?= htmlspecialchars($obreiro['cargo'] ?? '') ?>" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label">Cargos Oficiais (Nominata)</label><div class="form-static-field"><?= !empty($obreiro['cargos_codigos']) ? htmlspecialchars(implode(', ', (array) $obreiro['cargos_codigos'])) : 'Sem cargo oficial ativo' ?> <a href="/admin/cargos" class="link ml-2">Abrir Nominata</a></div></div>
            <div><label for="loja_origem" class="form-label">Loja de Origem</label><input id="loja_origem" type="text" name="loja_origem" value="<?= htmlspecialchars($obreiro['loja_origem'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_iniciacao" class="form-label">Data de IniciaÃ§Ã£o</label><input id="data_iniciacao" type="date" name="data_iniciacao" value="<?= htmlspecialchars($obreiro['data_iniciacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_elevacao" class="form-label">Data de ElevaÃ§Ã£o</label><input id="data_elevacao" type="date" name="data_elevacao" value="<?= htmlspecialchars($obreiro['data_elevacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_exaltacao" class="form-label">Data de ExaltaÃ§Ã£o</label><input id="data_exaltacao" type="date" name="data_exaltacao" value="<?= htmlspecialchars($obreiro['data_exaltacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_filiacao" class="form-label">Data de FiliaÃ§Ã£o</label><input id="data_filiacao" type="date" name="data_filiacao" value="<?= htmlspecialchars($obreiro['data_filiacao'] ?? '') ?>" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Perfil EstatÃ­stico -->
    <div class="card" id="perfil-estatistico">
        <div class="card-header"><h2 class="card-title">Perfil EstatÃ­stico</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="profissao" class="form-label">ProfissÃ£o</label><input id="profissao" type="text" name="profissao" value="<?= htmlspecialchars($obreiro['profissao'] ?? '') ?>" class="form-input"></div>
            <div><label for="escolaridade" class="form-label">Escolaridade</label><select id="escolaridade" name="escolaridade" class="form-select"><?php foreach ($escolaridades as $v => $r) echo "<option value=\"$v\" " . (($obreiro['escolaridade'] ?? '') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="faixa_renda" class="form-label">Faixa de Renda</label><select id="faixa_renda" name="faixa_renda" class="form-select"><?php foreach ($faixasRenda as $v => $r) echo "<option value=\"$v\" " . (($obreiro['faixa_renda'] ?? '') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
        </div>
    </div>

    <!-- Card: SituaÃ§Ã£o no Quadro -->
    <div class="card" id="situacao-quadro">
        <div class="card-header"><h2 class="card-title">SituaÃ§Ã£o no Quadro</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="situacao_quadro" class="form-label">SituaÃ§Ã£o</label><select id="situacao_quadro" name="situacao_quadro" class="form-select"><?php foreach ($situacoesQuadro as $v => $r) echo "<option value=\"$v\" " . (($obreiro['situacao_quadro'] ?? 'ativo') === $v ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="data_regularizacao" class="form-label">Data de RegularizaÃ§Ã£o</label><input id="data_regularizacao" type="date" name="data_regularizacao" value="<?= htmlspecialchars($obreiro['data_regularizacao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_reintegracao" class="form-label">Data de ReintegraÃ§Ã£o</label><input id="data_reintegracao" type="date" name="data_reintegracao" value="<?= htmlspecialchars($obreiro['data_reintegracao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_quite_placet" class="form-label">Data de Quite-Placet</label><input id="data_quite_placet" type="date" name="data_quite_placet" value="<?= htmlspecialchars($obreiro['data_quite_placet'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_suspensao" class="form-label">Data de SuspensÃ£o</label><input id="data_suspensao" type="date" name="data_suspensao" value="<?= htmlspecialchars($obreiro['data_suspensao'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_desligamento" class="form-label">Data de Desligamento</label><input id="data_desligamento" type="date" name="data_desligamento" value="<?= htmlspecialchars($obreiro['data_desligamento'] ?? '') ?>" class="form-input"></div>
            <div><label for="data_oriente_eterno" class="form-label">Data de Oriente Eterno</label><input id="data_oriente_eterno" type="date" name="data_oriente_eterno" value="<?= htmlspecialchars($obreiro['data_oriente_eterno'] ?? '') ?>" class="form-input"></div>
        </div>
    </div>

    <!-- Card: VÃ­nculos e GestÃ£o -->
    <div class="card" id="vinculos-gestao">
        <div class="card-header"><h2 class="card-title">VÃ­nculos e GestÃ£o</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="potencia_nome" class="form-label">PotÃªncia</label><input id="potencia_nome" type="text" name="potencia_nome" value="<?= htmlspecialchars($obreiro['potencia_nome'] ?? '') ?>" class="form-input"></div>
            <div><label for="potencia_sigla" class="form-label">Sigla da PotÃªncia</label><input id="potencia_sigla" type="text" name="potencia_sigla" value="<?= htmlspecialchars($obreiro['potencia_sigla'] ?? '') ?>" class="form-input"></div>
            <div><label for="numero_quadro" class="form-label">NÃºmero no Quadro</label><input id="numero_quadro" type="text" name="numero_quadro" value="<?= htmlspecialchars($obreiro['numero_quadro'] ?? '') ?>" class="form-input"></div>
            <div><label class="form-label">Telegram ID</label><div class="form-static-field"><?= htmlspecialchars($obreiro['telegram_id'] ?? 'NÃ£o vinculado') ?></div><p class="form-hint">VÃ­nculo controlado apenas pelo bot (nÃ£o editÃ¡vel aqui).</p></div>
            <div><label for="potencia_login" class="form-label">Login na PotÃªncia</label><input id="potencia_login" type="text" name="potencia_login" value="<?= htmlspecialchars($obreiro['potencia_login'] ?? '') ?>" class="form-input"></div>
            <div class="flex items-center"><label class="form-checkbox-label"><input type="checkbox" name="acesso_potencia_liberado" value="1" <?= !empty($obreiro['acesso_potencia_liberado']) ? 'checked' : '' ?> class="form-checkbox"> Acesso na plataforma da PotÃªncia liberado</label></div>
            <div class="md:col-span-2"><label for="observacao_secretaria" class="form-label">ObservaÃ§Ã£o da Secretaria</label><textarea id="observacao_secretaria" name="observacao_secretaria" rows="3" class="form-textarea"><?= htmlspecialchars($obreiro['observacao_secretaria'] ?? '') ?></textarea></div>
        </div>
    </div>

    <!-- AÃ§Ãµes -->
    <div class="card">
        <div class="card-body flex flex-col sm:flex-row justify-between items-center gap-4">
            <label class="form-checkbox-label"><input type="hidden" name="ativo" value="0"><input type="checkbox" id="ativo" name="ativo" value="1" <?= ($obreiro['ativo'] ?? true) ? 'checked' : '' ?> class="form-checkbox"> Registro habilitado no sistema</label>
            <div class="flex gap-3">
                <a href="/obreiros" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar AlteraÃ§Ãµes</button>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>



