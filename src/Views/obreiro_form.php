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

$estadosCivis = ['solteiro' => 'Solteiro', 'casado' => 'Casado', 'divorciado' => 'Divorciado', 'separado' => 'Separado', 'viuvo' => 'Viúvo', 'uniao_estavel' => 'União estável', 'nao_informado' => 'Não informado'];
$escolaridades = ['fundamental_incompleto' => 'Fundamental incompleto', 'fundamental_completo' => 'Fundamental completo', 'medio_incompleto' => 'Médio incompleto', 'medio_completo' => 'Médio completo', 'tecnico' => 'Técnico', 'superior_incompleto' => 'Superior incompleto', 'superior_completo' => 'Superior completo', 'pos_graduacao' => 'Pós-graduação', 'mestrado' => 'Mestrado', 'doutorado' => 'Doutorado', 'nao_informado' => 'Não informado'];
$faixasRenda = ['ate_1_sm' => 'Até 1 salário mínimo', 'de_1_a_3_sm' => 'De 1 a 3 salários mínimos', 'de_3_a_5_sm' => 'De 3 a 5 salários mínimos', 'de_5_a_10_sm' => 'De 5 a 10 salários mínimos', 'acima_10_sm' => 'Acima de 10 salários mínimos', 'nao_informado' => 'Não informado'];
$situacoesQuadro = ['ativo' => 'Regular', 'licenciado' => 'Licenciado', 'suspenso' => 'Suspenso', 'desligado' => 'Desligado', 'falecido' => 'Falecido', 'oriente_eterno' => 'Oriente Eterno', 'inativo' => 'Afastado'];

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
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
            <div class="md:col-span-2"><label for="nome_historico" class="form-label">Nome Histórico</label><input id="nome_historico" type="text" name="nome_historico" class="form-input"></div>
            <div><label for="cpf" class="form-label">CPF</label><input id="cpf" type="text" name="cpf" class="form-input"></div>
            <div><label for="data_nascimento_civil" class="form-label">Data de Nascimento</label><input id="data_nascimento_civil" type="date" name="data_nascimento_civil" class="form-input"></div>
            <div><label for="estado_civil" class="form-label">Estado Civil</label><select id="estado_civil" name="estado_civil" class="form-select"><?php foreach ($estadosCivis as $v => $r) echo "<option value=\"$v\">$r</option>"; ?></select></div>
            <div><label for="telefone" class="form-label">Telefone</label><input id="telefone" type="text" name="telefone" class="form-input"></div>
            <div class="md:col-span-2"><label for="email" class="form-label">E-mail</label><input id="email" type="email" name="email" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Dados Maçônicos -->
    <div class="card" id="dados-maconicos">
        <div class="card-header"><h2 class="card-title">Dados Maçônicos</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="cim" class="form-label">CIM</label><input id="cim" type="number" name="cim" required class="form-input"></div>
            <div><label for="grau" class="form-label">Grau</label><select id="grau" name="grau" required class="form-select"><?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $g) echo "<option value=\"$g\">$g</option>"; ?></select></div>
            <div><label for="cargo" class="form-label">Cargo (Legado)</label><select id="cargo" name="cargo" class="form-select"><option value="">Sem cargo</option><option value="Veneravel">Venerável Mestre</option><option value="1 Vigilante">1 Vigilante</option><option value="2 Vigilante">2 Vigilante</option><option value="Secretario">Secretário</option><option value="Tesoureiro">Tesoureiro</option><option value="Chanceler">Chanceler</option><option value="Orador">Orador</option></select></div>
            <div><label for="loja_origem" class="form-label">Loja de Origem</label><input id="loja_origem" type="text" name="loja_origem" class="form-input"></div>
            <div><label for="data_iniciacao" class="form-label">Data de Iniciação</label><input id="data_iniciacao" type="date" name="data_iniciacao" class="form-input"></div>
            <div><label for="data_elevacao" class="form-label">Data de Elevação</label><input id="data_elevacao" type="date" name="data_elevacao" class="form-input"></div>
            <div><label for="data_exaltacao" class="form-label">Data de Exaltação</label><input id="data_exaltacao" type="date" name="data_exaltacao" class="form-input"></div>
            <div><label for="data_filiacao" class="form-label">Data de Filiação</label><input id="data_filiacao" type="date" name="data_filiacao" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Perfil Estatístico -->
    <div class="card" id="perfil-estatistico">
        <div class="card-header"><h2 class="card-title">Perfil Estatístico</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="profissao" class="form-label">Profissão</label><input id="profissao" type="text" name="profissao" class="form-input"></div>
            <div><label for="escolaridade" class="form-label">Escolaridade</label><select id="escolaridade" name="escolaridade" class="form-select"><?php foreach ($escolaridades as $v => $r) echo "<option value=\"$v\">$r</option>"; ?></select></div>
            <div><label for="faixa_renda" class="form-label">Faixa de Renda</label><select id="faixa_renda" name="faixa_renda" class="form-select"><?php foreach ($faixasRenda as $v => $r) echo "<option value=\"$v\">$r</option>"; ?></select></div>
        </div>
    </div>

    <!-- Card: Situação no Quadro -->
    <div class="card" id="situacao-quadro">
        <div class="card-header"><h2 class="card-title">Situação no Quadro</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-3 gap-6">
            <div><label for="situacao_quadro" class="form-label">Situação</label><select id="situacao_quadro" name="situacao_quadro" class="form-select"><?php foreach ($situacoesQuadro as $v => $r) echo "<option value=\"$v\" " . ($v === 'ativo' ? 'selected' : '') . ">$r</option>"; ?></select></div>
            <div><label for="data_regularizacao" class="form-label">Data de Regularização</label><input id="data_regularizacao" type="date" name="data_regularizacao" class="form-input"></div>
            <div><label for="data_reintegracao" class="form-label">Data de Reintegração</label><input id="data_reintegracao" type="date" name="data_reintegracao" class="form-input"></div>
            <div><label for="data_quite_placet" class="form-label">Data de Quite-Placet</label><input id="data_quite_placet" type="date" name="data_quite_placet" class="form-input"></div>
            <div><label for="data_suspensao" class="form-label">Data de Suspensão</label><input id="data_suspensao" type="date" name="data_suspensao" class="form-input"></div>
            <div><label for="data_desligamento" class="form-label">Data de Desligamento</label><input id="data_desligamento" type="date" name="data_desligamento" class="form-input"></div>
            <div><label for="data_oriente_eterno" class="form-label">Data de Oriente Eterno</label><input id="data_oriente_eterno" type="date" name="data_oriente_eterno" class="form-input"></div>
        </div>
    </div>

    <!-- Card: Vínculos e Gestão -->
    <div class="card" id="vinculos-gestao">
        <div class="card-header"><h2 class="card-title">Vínculos e Gestão</h2></div>
        <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label for="potencia_nome" class="form-label">Potência</label><input id="potencia_nome" type="text" name="potencia_nome" class="form-input"></div>
            <div><label for="potencia_sigla" class="form-label">Sigla da Potência</label><input id="potencia_sigla" type="text" name="potencia_sigla" class="form-input"></div>
            <div><label for="numero_quadro" class="form-label">Número no Quadro</label><input id="numero_quadro" type="text" name="numero_quadro" class="form-input"></div>
            <div><label for="telegram_id" class="form-label">Telegram ID</label><input id="telegram_id" type="number" name="telegram_id" class="form-input"></div>
            <div><label for="potencia_login" class="form-label">Login na Potência</label><input id="potencia_login" type="text" name="potencia_login" class="form-input"></div>
            <div class="flex items-center"><label class="form-checkbox-label"><input type="checkbox" name="acesso_potencia_liberado" value="1" class="form-checkbox"> Acesso na plataforma da Potência liberado</label></div>
            <div class="md:col-span-2"><label for="observacao_secretaria" class="form-label">Observação da Secretaria</label><textarea id="observacao_secretaria" name="observacao_secretaria" rows="3" class="form-textarea"></textarea></div>
        </div>
    </div>

    <!-- Ações -->
    <div class="card">
        <div class="card-body flex justify-end gap-3">
            <a href="/obreiros" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Gravar Obreiro</button>
        </div>
    </div>
</form>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>



