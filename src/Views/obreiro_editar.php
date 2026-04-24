<?php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}

$appTitle = "Editar Obreiro - Secretaria";
$acessosStatus = [
    'pendente' => 'Pendente',
    'ativo' => 'Ativo',
    'inativo' => 'Inativo',
];
$estadosCivis = [
    'solteiro' => 'Solteiro',
    'casado' => 'Casado',
    'divorciado' => 'Divorciado',
    'separado' => 'Separado',
    'viuvo' => 'Viuvo',
    'uniao_estavel' => 'Uniao estavel',
    'nao_informado' => 'Não informado',
];
$escolaridades = [
    'fundamental_incompleto' => 'Fundamental incompleto',
    'fundamental_completo' => 'Fundamental completo',
    'medio_incompleto' => 'Medio incompleto',
    'medio_completo' => 'Medio completo',
    'tecnico' => 'Tecnico',
    'superior_incompleto' => 'Superior incompleto',
    'superior_completo' => 'Superior completo',
    'pos_graduacao' => 'Pos-graduacao',
    'mestrado' => 'Mestrado',
    'doutorado' => 'Doutorado',
    'nao_informado' => 'Não informado',
];
$faixasRenda = [
    'ate_1_sm' => 'Ate 1 salario minimo',
    'de_1_a_3_sm' => 'De 1 a 3 salarios minimos',
    'de_3_a_5_sm' => 'De 3 a 5 salarios minimos',
    'de_5_a_10_sm' => 'De 5 a 10 salarios minimos',
    'acima_10_sm' => 'Acima de 10 salarios minimos',
    'nao_informado' => 'Não informado',
];
$situacoesQuadro = [
    'ativo' => 'Ativo',
    'licenciado' => 'Licenciado',
    'suspenso' => 'Suspenso',
    'desligado' => 'Desligado',
    'falecido' => 'Falecido',
    'oriente_eterno' => 'Oriente Eterno',
    'inativo' => 'Inativo',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalto: '#0a192f',
                        ouro: '#cfa935',
                        pedra: '#f3f4f6'
                    },
                    fontFamily: {
                        serif: ['Merriweather', 'serif'],
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[11px\] {
                font-size: 0.92rem !important;
                line-height: 1.4rem !important;
            }
            .erp-readable .text-sm {
                font-size: 1.03rem !important;
                line-height: 1.58rem !important;
            }
        }
    </style>
</head>
<body class="erp-readable bg-pedra font-sans text-gray-800 antialiased">
    <header class="bg-cobalto text-white shadow-md sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-3">
            <a href="/obreiros" class="text-gray-300 hover:text-white" title="Voltar">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <h1 class="font-serif text-lg font-bold tracking-wider">
                <i class="fas fa-user-check text-ouro mr-2"></i>Completar cadastro
            </h1>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8 mt-4 mb-20">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <h2 class="text-xl font-bold text-cobalto"><?= htmlspecialchars($obreiro['nome_historico'] ?? $obreiro['nome']) ?></h2>
                <p class="text-sm text-gray-500 mt-1">Completar e manter o cadastro do obreiro existente (sem criar novo registro).</p>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="mx-6 mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Ficha atualizada com sucesso.</div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="mx-6 mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Não foi possível salvar. Verifique se o CIM informado já existe para outro obreiro.</div>
            <?php endif; ?>

            <form action="/obreiros/atualizar" method="POST" class="p-6 space-y-8">
                <input type="hidden" name="id" value="<?= htmlspecialchars($obreiro['id']) ?>">

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Dados civis</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2" id="cargos-oficiais">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo civil</label>
                            <input type="text" name="nome_completo" required value="<?= htmlspecialchars($obreiro['nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome historico</label>
                            <input type="text" name="nome_historico" value="<?= htmlspecialchars($obreiro['nome_historico'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                            <input type="text" name="cpf" value="<?= htmlspecialchars($obreiro['cpf'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de nascimento civil</label>
                            <input type="date" name="data_nascimento_civil" value="<?= htmlspecialchars($obreiro['data_nascimento_civil'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado civil</label>
                            <select name="estado_civil" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Selecione</option>
                                <?php foreach ($estadosCivis as $valor => $rotulo): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= ($obreiro['estado_civil'] ?? '') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input type="text" name="telefone" value="<?= htmlspecialchars($obreiro['telefone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($obreiro['email'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Dados maconicos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CIM</label>
                            <input type="text" name="cim" value="<?= htmlspecialchars($obreiro['cim'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" inputmode="numeric" autocomplete="off">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grau</label>
                            <select name="grau" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $grau): ?>
                                    <option value="<?= htmlspecialchars($grau) ?>" <?= ($obreiro['grau'] ?? '') === $grau ? 'selected' : '' ?>><?= htmlspecialchars($grau) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status de acesso</label>
                            <select name="acesso_status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <?php
                                $acessoAtual = strtolower(trim((string) ($obreiro['acesso_status'] ?? '')));
                                if (!in_array($acessoAtual, ['pendente', 'ativo', 'inativo'], true)) {
                                    $acessoAtual = !empty($obreiro['ativo']) ? 'ativo' : 'inativo';
                                }
                                ?>
                                <?php foreach ($acessosStatus as $valor => $rotulo): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= $acessoAtual === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo legado</label>
                            <input type="text" name="cargo" value="<?= htmlspecialchars($obreiro['cargo'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Cargos oficiais (nominata)</div>
                            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                <?= !empty($obreiro['cargos_codigos']) ? htmlspecialchars(implode(', ', (array) $obreiro['cargos_codigos'])) : 'Sem cargo oficial ativo' ?>
                                <span class="text-gray-400">·</span>
                                <a href="/admin/cargos" class="text-cobalto underline">Abrir nominata</a>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loja de origem</label>
                            <input type="text" name="loja_origem" value="<?= htmlspecialchars($obreiro['loja_origem'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de iniciacao</label>
                            <input type="date" name="data_iniciacao" value="<?= htmlspecialchars($obreiro['data_iniciacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de elevacao</label>
                            <input type="date" name="data_elevacao" value="<?= htmlspecialchars($obreiro['data_elevacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de exaltacao</label>
                            <input type="date" name="data_exaltacao" value="<?= htmlspecialchars($obreiro['data_exaltacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de filiacao</label>
                            <input type="date" name="data_filiacao" value="<?= htmlspecialchars($obreiro['data_filiacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Perfil estatistico</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profissao</label>
                            <input type="text" name="profissao" value="<?= htmlspecialchars($obreiro['profissao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Escolaridade</label>
                            <select name="escolaridade" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Selecione</option>
                                <?php foreach ($escolaridades as $valor => $rotulo): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= ($obreiro['escolaridade'] ?? '') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Faixa de renda</label>
                            <select name="faixa_renda" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Selecione</option>
                                <?php foreach ($faixasRenda as $valor => $rotulo): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= ($obreiro['faixa_renda'] ?? '') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Situacao no quadro</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Situacao</label>
                            <select name="situacao_quadro" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <?php foreach ($situacoesQuadro as $valor => $rotulo): ?>
                                    <option value="<?= htmlspecialchars($valor) ?>" <?= ($obreiro['situacao_quadro'] ?? 'ativo') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de regularizacao</label>
                            <input type="date" name="data_regularizacao" value="<?= htmlspecialchars($obreiro['data_regularizacao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de reintegracao</label>
                            <input type="date" name="data_reintegracao" value="<?= htmlspecialchars($obreiro['data_reintegracao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de quite-placet</label>
                            <input type="date" name="data_quite_placet" value="<?= htmlspecialchars($obreiro['data_quite_placet'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de suspensao</label>
                            <input type="date" name="data_suspensao" value="<?= htmlspecialchars($obreiro['data_suspensao'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de desligamento</label>
                            <input type="date" name="data_desligamento" value="<?= htmlspecialchars($obreiro['data_desligamento'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Oriente Eterno</label>
                            <input type="date" name="data_oriente_eterno" value="<?= htmlspecialchars($obreiro['data_oriente_eterno'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-ouro uppercase tracking-wider border-b border-gray-200 pb-2">Vinculos com Potencia e gestao</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Potencia</label>
                            <input type="text" name="potencia_nome" value="<?= htmlspecialchars($obreiro['potencia_nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sigla da Potencia</label>
                            <input type="text" name="potencia_sigla" value="<?= htmlspecialchars($obreiro['potencia_sigla'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numero no quadro</label>
                            <input type="text" name="numero_quadro" value="<?= htmlspecialchars($obreiro['numero_quadro'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telegram ID</label>
                            <input type="text" value="<?= htmlspecialchars($obreiro['telegram_id'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                            <div class="mt-1 text-xs text-gray-500">Vínculo controlado apenas pelo bot/onboarding (não editável aqui).</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Login na Potencia</label>
                            <input type="text" name="potencia_login" value="<?= htmlspecialchars($obreiro['potencia_login'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" name="acesso_potencia_liberado" value="1" <?= !empty($obreiro['acesso_potencia_liberado']) ? 'checked' : '' ?> class="rounded border-gray-300">
                                Acesso na plataforma da Potencia liberado
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observacao da Secretaria</label>
                            <textarea name="observacao_secretaria" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($obreiro['observacao_secretaria'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="ativo" value="0">
                    <input type="checkbox" id="ativo" name="ativo" value="1" <?= ($obreiro['ativo'] ?? true) ? 'checked' : '' ?> class="h-4 w-4 text-cobalto border-gray-300 rounded">
                    <label for="ativo" class="ml-2 block text-sm text-gray-900 font-medium">Cadastro habilitado no sistema</label>
                </div>

                <div class="border-t border-gray-100 bg-gray-50 -my-6 -mx-6 mt-2 p-6 flex justify-end gap-3 flex-col sm:flex-row rounded-b-xl">
                    <a href="/obreiros" class="w-full sm:w-auto text-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Voltar
                    </a>
                    <button type="submit" class="w-full sm:w-auto flex justify-center py-2 px-6 text-sm font-medium rounded-md text-white bg-cobalto hover:bg-blue-900 gap-2 items-center">
                        <i class="fas fa-save text-ouro"></i>Atualizar dados
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

