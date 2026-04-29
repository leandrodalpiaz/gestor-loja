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

$estadosCivis = ['solteiro' => 'Solteiro', 'casado' => 'Casado', 'divorciado' => 'Divorciado', 'separado' => 'Separado', 'viuvo' => 'ViÃºvo', 'uniao_estavel' => 'UniÃ£o estÃ¡vel', 'nao_informado' => 'NÃ£o informado'];
$escolaridades = ['fundamental_incompleto' => 'Fundamental incompleto', 'fundamental_completo' => 'Fundamental completo', 'medio_incompleto' => 'MÃ©dio incompleto', 'medio_completo' => 'MÃ©dio completo', 'tecnico' => 'TÃ©cnico', 'superior_incompleto' => 'Superior incompleto', 'superior_completo' => 'Superior completo', 'pos_graduacao' => 'PÃ³s-graduaÃ§Ã£o', 'mestrado' => 'Mestrado', 'doutorado' => 'Doutorado', 'nao_informado' => 'NÃ£o informado'];
$faixasRenda = ['ate_1_sm' => 'AtÃ© 1 salÃ¡rio mÃ­nimo', 'de_1_a_3_sm' => 'De 1 a 3 salÃ¡rios mÃ­nimos', 'de_3_a_5_sm' => 'De 3 a 5 salÃ¡rios mÃ­nimos', 'de_5_a_10_sm' => 'De 5 a 10 salÃ¡rios mÃ­nimos', 'acima_10_sm' => 'Acima de 10 salÃ¡rios mÃ­nimos', 'nao_informado' => 'NÃ£o informado'];
$situacoesQuadro = ['ativo' => 'Regular', 'licenciado' => 'Licenciado', 'suspenso' => 'Suspenso', 'desligado' => 'Desligado', 'falecido' => 'Falecido', 'oriente_eterno' => 'Oriente Eterno', 'inativo' => 'Afastado'];

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Adicionar Obreiro';
$appShellDescription = 'Ficha de cadastro para um novo membro da loja.';
$appShellActiveHref = '/obreiros';
$appShellActions = [['label' => 'Voltar para a Lista', 'href' => '/obreiros']];

require __DIR__ . '/partials/erp_shell_open.php';
?>

<form action="/obreiros/salvar" method="POST" class="space-y-8">

    <!-- Card: Dados Civis -->
    <div class="card" id="dados-civis">
        <div class="card-header"><h2 class="card-title">Dados Civis</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2"><label for="nome_completo" class="form-label">Nome Completo Civil</label><input id="nome_completo" type="text" name="nome_completo" required class="form-input"></div>
            <div class="md:col-span-2"><label for="nome_historico" class="form-label">Nome HistÃ³rico</label><input id="nome_historico" type="text" name="nome_historico" class="form-input"></div>
            <div><label for="cpf" class="form-label">CPF</label><input id="cpf" type="text" name="cpf" class="form-input"></div>
            <div><label for="data_nascimento_civil" class="form-label">Data de Nascimento</label><input id="data_nascimento_civil" type="date" name="data_nascimento_civil" class="form-input"></div>
            <div><label for="estado_civil" class="form-label">Estado Civil</label><select id="estado_civil" name="estado_civil" class="form-select"><?php foreach ($estadosCivis as $v => $r) echo "<option value=\"$v\">$r</option>"; ?></select></div>
            <div><label for="telefone" class="form-label">Telefone</label><input id="telefone" type="text" name="telefone" class="form-input"></div>
            <div class="md:col-span-2"><label for="email" class="form-label">E-mail</label><input id="email" type="email" name="email" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Dados MaÃ§Ã´nicos -->
    <div class="card" id="dados-maconicos">
        <div class="card-header"><h2 class="card-title">Dados MaÃ§Ã´nicos</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="cim" class="form-label">CIM</label><input id="cim" type="number" name="cim" required class="form-input"></div>
            <div><label for="grau" class="form-label">Grau</label><select id="grau" name="grau" required class="form-select"><?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $g) echo "<option value=\"$g\">$g</option>"; ?></select></div>
            <div><label for="cargo" class="form-label">Cargo (Legado)</label><select id="cargo" name="cargo" class="form-select"><option value="">Sem cargo</option><option value="Veneravel">Veneravel Mestre</option><option value="1 Vigilante">1 Vigilante</option><option value="2 Vigilante">2 Vigilante</option><option value="Secretario">Secretario</option><option value="Tesoureiro">Tesoureiro</option><option value="Chanceler">Chanceler</option><option value="Orador">Orador</option></select></div>
            <div><label for="loja_origem" class="form-label">Loja de Origem</label><input id="loja_origem" type="text" name="loja_origem" class="form-input"></div>
            <div><label for="data_iniciacao" class="form-label">Data de IniciaÃ§Ã£o</label><input id="data_iniciacao" type="date" name="data_iniciacao" class="form-input"></div>
            <div><label for="data_elevacao" class="form-label">Data de ElevaÃ§Ã£o</label><input id="data_elevacao" type="date" name="data_elevacao" class="form-input"></div>
            <div><label for="data_exaltacao" class="form-label">Data de ExaltaÃ§Ã£o</label><input id="data_exaltacao" type="date" name="data_exaltacao" class="form-input"></div>
            <div><label for="data_filiacao" class="form-label">Data de FiliaÃ§Ã£o</label><input id="data_filiacao" type="date" name="data_filiacao" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Perfil EstatÃ­stico -->
    <div class="card" id="perfil-estatistico">
        <div class="card-header"><h2 class="card-title">Perfil EstatÃ­stico</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="profissao" class="form-label">ProfissÃ£o</label><input id="profissao" type="text" name="profissao" class="form-input"></div>
            <div><label for="escolaridade" class="form-label">Escolaridade</label><select id="escolaridade" name="escolaridade" class="form-select"><?php foreach ($escolaridades as $v => $r) echo "<option value=\"$v\">$r</option>"; ?></select></div>
            <div><label for="faixa_renda" class="form-label">Faixa de Renda</label><select id="faixa_renda" name="faixa_renda" class="form-select"><?php foreach ($faixasRenda as $v => $r) echo "<option value=\"$v\">$r</option>"; ?></select></div>
        </div>
    </div>

    <!-- Card: SituaÃ§Ã£o no Quadro -->
    <div class="card" id="situacao-quadro">
        <div class="card-header"><h2 class="card-title">SituaÃ§Ã£o no Quadro</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="situacao_quadro" class="form-label">SituaÃ§Ã£o</label><select id="situacao_quadro" name="situacao_quadro" class="form-select"><?php foreach ($situacoesQuadro as $v => $r) echo "<option value=\"$v\" " . ($v === 'ativo' ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="data_regularizacao" class="form-label">Data de RegularizaÃ§Ã£o</label><input id="data_regularizacao" type="date" name="data_regularizacao" class="form-input"></div>
            <div><label for="data_reintegracao" class="form-label">Data de ReintegraÃ§Ã£o</label><input id="data_reintegracao" type="date" name="data_reintegracao" class="form-input"></div>
            <div><label for="data_quite_placet" class="form-label">Data de Quite-Placet</label><input id="data_quite_placet" type="date" name="data_quite_placet" class="form-input"></div>
            <div><label for="data_suspensao" class="form-label">Data de SuspensÃ£o</label><input id="data_suspensao" type="date" name="data_suspensao" class="form-input"></div>
            <div><label for="data_desligamento" class="form-label">Data de Desligamento</label><input id="data_desligamento" type="date" name="data_desligamento" class="form-input"></div>
            <div><label for="data_oriente_eterno" class="form-label">Data de Oriente Eterno</label><input id="data_oriente_eterno" type="date" name="data_oriente_eterno" class="form-input"></div>
        </div>
    </div>

    <!-- Card: VÃ­nculos e GestÃ£o -->
    <div class="card" id="vinculos-gestao">
        <div class="card-header"><h2 class="card-title">VÃ­nculos e GestÃ£o</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="potencia_nome" class="form-label">PotÃªncia</label><input id="potencia_nome" type="text" name="potencia_nome" class="form-input"></div>
            <div><label for="potencia_sigla" class="form-label">Sigla da PotÃªncia</label><input id="potencia_sigla" type="text" name="potencia_sigla" class="form-input"></div>
            <div><label for="numero_quadro" class="form-label">NÃºmero no Quadro</label><input id="numero_quadro" type="text" name="numero_quadro" class="form-input"></div>
            <div><label for="telegram_id" class="form-label">Telegram ID</label><input id="telegram_id" type="number" name="telegram_id" class="form-input"></div>
            <div><label for="potencia_login" class="form-label">Login na PotÃªncia</label><input id="potencia_login" type="text" name="potencia_login" class="form-input"></div>
            <div class="flex items-center"><label class="form-checkbox-label"><input type="checkbox" name="acesso_potencia_liberado" value="1" class="form-checkbox"> Acesso na plataforma da PotÃªncia liberado</label></div>
            <div class="md:col-span-2"><label for="observacao_secretaria" class="form-label">ObservaÃ§Ã£o da Secretaria</label><textarea id="observacao_secretaria" name="observacao_secretaria" rows="3" class="form-textarea"></textarea></div>
        </div>
    </div>

    <!-- AÃ§Ãµes -->
    <div class="card">
        <div class="card-body flex justify-end gap-3">
            <a href="/obreiros" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Gravar Obreiro</button>
        </div>
    </div>
</form>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>



