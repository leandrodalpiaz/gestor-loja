<?php
use App\Models\Cargo;

if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}

$appTitle = "Secretaria - Central de Obreiros";
$filtrosObreiros = $filtrosObreiros ?? [
    'busca' => '',
    'situacao' => '',
    'grau' => '',
    'alerta' => '',
    'cargo_codigo' => '',
    'ordenacao' => 'nome',
];
$resumoObreiros = $resumoObreiros ?? ['total' => 0, 'ativos' => 0, 'com_alerta' => 0, 'com_telegram' => 0, 'mestres' => 0];
$podeGerenciarObreiros = (bool) ($podeGerenciarObreiros ?? false);
$podeGerarConvitesAcesso = (bool) ($podeGerarConvitesAcesso ?? false);
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
$conviteGeradoLink = $_SESSION['convite_gerado_link'] ?? null;
$conviteGeradoExpiraEm = $_SESSION['convite_gerado_expira_em'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro'], $_SESSION['convite_gerado_link'], $_SESSION['convite_gerado_expira_em']);
if (!$mensagemSucesso && isset($_GET['sucesso'])) {
    $mensagemSucesso = 'Registro atualizado com sucesso.';
}
if (!$mensagemErro && isset($_GET['erro'])) {
    $mensagemErro = 'Não foi possível concluir a atualização deste registro.';
}
$returnToAtual = (string) ($_SERVER['REQUEST_URI'] ?? '/obreiros');
$rotulosAlerta = [
    'sem_nascimento' => 'Nascimento ausente',
    'sem_escolaridade' => 'Escolaridade ausente',
    'sem_profissao' => 'Profissão ausente',
    'sem_situacao' => 'Situação do quadro ausente',
    'sem_data_ingresso' => 'Data de ingresso ausente',
    'sem_potencia' => 'Potência ausente',
];
 
$erpPageTitle = $appTitle;
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Central de Obreiros';
$appShellDescription = 'Registro administrativo, filtros operacionais e organização do quadro da Loja.';
$appShellActiveHref = '/obreiros';
$appShellActions = [
    ['label' => 'Somente alertas', 'href' => '/obreiros?alerta=cadastro'],
];
if ($podeGerenciarObreiros) {
    $appShellActions[] = ['label' => 'Registrar obreiro', 'href' => '/obreiros/novo', 'primary' => false];
}
$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaústres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos', 'href' => '/admin/acessos'],
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];
require __DIR__ . '/partials/erp_head.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php require __DIR__ . '/partials/erp_shell_open.php'; ?>
<?php /* TODO(layout): consolidar classes locais legadas em tokens do shell ERP, sem alterar o controller. */ ?>
<div class="space-y-7">
        <?php if ($mensagemSucesso): ?>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><?= htmlspecialchars((string) $mensagemErro) ?></div>
        <?php endif; ?>
        <?php if (is_string($conviteGeradoLink) && $conviteGeradoLink !== ''): ?>
            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
                <div class="text-sm font-semibold text-sky-900">Convite de acesso gerado com sucesso</div>
                <div class="mt-2 flex flex-col gap-2 md:flex-row md:items-center">
                    <input id="convite-link-obreiros" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800" readonly value="<?= htmlspecialchars((string) $conviteGeradoLink) ?>">
                    <button type="button" class="copy-btn rounded-md bg-slate-900 px-4 py-2 text-sm text-white" data-copy="<?= htmlspecialchars((string) $conviteGeradoLink) ?>">Copiar link</button>
                    <button type="button" class="select-btn rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800" data-target="convite-link-obreiros">Selecionar link</button>
                    <a href="/admin/convites" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800">Abrir convites</a>
                </div>
                <?php if (is_string($conviteGeradoExpiraEm) && $conviteGeradoExpiraEm !== ''): ?>
                    <div class="mt-1 text-xs text-slate-600">Expira em: <?= htmlspecialchars((string) $conviteGeradoExpiraEm) ?></div>
                <?php endif; ?>
                <div class="mt-1 text-xs text-slate-600">Pode encaminhar por Telegram, WhatsApp ou e-mail. Telefone no registro não é obrigatório para gerar convite.</div>
            </div>
        <?php endif; ?>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap gap-2">
                <a href="/admin/cargos" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Nominata oficial</a>
                <?php if ($podeGerarConvitesAcesso): ?>
                    <a href="/admin/convites" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Convites de acesso</a>
                <?php endif; ?>
                <?php if ($podeGerenciarObreiros): ?>
                    <a href="/obreiros/novo" class="rounded-lg border border-slate-900 bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Registrar obreiro</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-5 xl:gap-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                <div class="text-xs text-gray-500 md:text-sm">Total filtrado</div>
                <div class="mt-1 text-2xl font-semibold text-cobalto md:mt-2 md:text-3xl"><?= (int) $resumoObreiros['total'] ?></div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                <div class="text-xs text-gray-500 md:text-sm">Obreiros regulares</div>
                <div class="mt-1 text-2xl font-semibold text-cobalto md:mt-2 md:text-3xl"><?= (int) $resumoObreiros['ativos'] ?></div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                <div class="text-xs text-gray-500 md:text-sm">Com alerta</div>
                <div class="mt-1 text-2xl font-semibold text-amber-700 md:mt-2 md:text-3xl"><?= (int) $resumoObreiros['com_alerta'] ?></div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                <div class="text-xs text-gray-500 md:text-sm">Bot vinculado</div>
                <div class="mt-1 text-2xl font-semibold text-cobalto md:mt-2 md:text-3xl"><?= (int) $resumoObreiros['com_telegram'] ?></div>
            </article>
            <article class="col-span-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:col-span-1 md:p-5">
                <div class="text-xs text-gray-500 md:text-sm">Mestres</div>
                <div class="mt-1 text-2xl font-semibold text-cobalto md:mt-2 md:text-3xl"><?= (int) $resumoObreiros['mestres'] ?></div>
            </article>
        </section>

        <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            Alertas de registro servem como lembrete interno para a Secretaria tratar o tema reservadamente com o obreiro, sem expor o motivo como bloqueio operacional.
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 mb-4">
                <h2 class="text-xl font-semibold text-cobalto">Filtros administrativos</h2>
                <p class="text-sm text-gray-500">Use os filtros para organizar registros, conferir a nominata e preparar relatórios da Secretaria.</p>
            </div>

            <form method="GET" action="/obreiros" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-3 2xl:grid-cols-6">
                    <div class="md:col-span-2 xl:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                    <input
                        type="text"
                        name="busca"
                        value="<?= htmlspecialchars((string) ($filtrosObreiros['busca'] ?? '')) ?>"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2"
                        placeholder="Nome, nome historico, cargo ou CIM"
                    >
                </div>
                    <div class="grid grid-cols-2 gap-2 md:col-span-1 xl:col-span-1">
                        <button type="submit" class="rounded-lg border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Pesquisar</button>
                        <a href="/obreiros" class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm text-gray-700 bg-white hover:bg-gray-50">Limpar</a>
                    </div>
                </div>

                <details id="obreiros-filtros-avancados" class="group rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 md:border-0 md:bg-transparent md:p-0">
                    <summary class="cursor-pointer list-none text-sm font-medium text-cobalto md:hidden">
                        Mais filtros
                    </summary>
                    <div class="mt-3 grid gap-4 md:mt-0 md:grid-cols-3 2xl:grid-cols-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Situacao</label>
                            <select name="situacao" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                <option value="">Todas</option>
                                <?php foreach (\App\Models\Obreiro::SITUACOES_QUADRO as $situacao): ?>
                                    <option value="<?= htmlspecialchars($situacao) ?>" <?= ($filtrosObreiros['situacao'] ?? '') === $situacao ? 'selected' : '' ?>><?= htmlspecialchars($situacao) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grau</label>
                            <select name="grau" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                <option value="">Todos</option>
                                <?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Mestre Instalado'] as $grau): ?>
                                    <option value="<?= htmlspecialchars($grau) ?>" <?= ($filtrosObreiros['grau'] ?? '') === $grau ? 'selected' : '' ?>><?= htmlspecialchars($grau) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo oficial</label>
                            <select name="cargo_codigo" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                <option value="">Todos</option>
                                <?php foreach ($cargosFiltros as $cargo): ?>
                                    <option value="<?= htmlspecialchars((string) ($cargo['codigo'] ?? '')) ?>" <?= ($filtrosObreiros['cargo_codigo'] ?? '') === (string) ($cargo['codigo'] ?? '') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(Cargo::rotuloOficial((string) ($cargo['codigo'] ?? ''), (string) ($cargo['nome_exibicao'] ?? $cargo['codigo'] ?? ''))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alerta</label>
                            <select name="alerta" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                <option value="">Todos</option>
                                <option value="cadastro" <?= ($filtrosObreiros['alerta'] ?? '') === 'cadastro' ? 'selected' : '' ?>>Com alerta de registro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ordenar por</label>
                            <select name="ordenacao" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                <option value="nome" <?= ($filtrosObreiros['ordenacao'] ?? '') === 'nome' ? 'selected' : '' ?>>Nome</option>
                                <option value="grau" <?= ($filtrosObreiros['ordenacao'] ?? '') === 'grau' ? 'selected' : '' ?>>Grau</option>
                                <option value="situacao" <?= ($filtrosObreiros['ordenacao'] ?? '') === 'situacao' ? 'selected' : '' ?>>Situacao</option>
                                <option value="alerta" <?= ($filtrosObreiros['ordenacao'] ?? '') === 'alerta' ? 'selected' : '' ?>>Quantidade de alerta</option>
                            </select>
                        </div>
                    </div>
                </details>

                <div class="flex flex-wrap gap-2 border-t border-gray-200 pt-3">
                    <button type="submit" class="rounded-lg border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        Pesquisar obreiros
                    </button>
                    <a href="/obreiros" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 bg-white hover:bg-gray-50">
                        Limpar filtros
                    </a>
                </div>
            </form>
        </section>

        <section class="space-y-4">
            <?php if (empty($obreiros)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                    <p>Nenhum obreiro foi localizado com os filtros atuais.</p>
                </div>
            <?php else: ?>
                <div class="hidden overflow-hidden rounded-2xl border border-erp-border bg-white shadow-sm lg:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-erp-border text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">
                                <tr>
                                    <th class="px-4 py-3">Obreiro</th>
                                    <th class="px-4 py-3">Situacao</th>
                                    <th class="px-4 py-3">Tesouraria</th>
                                    <th class="px-4 py-3">Cargos</th>
                                    <th class="px-4 py-3 text-right">Acoes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-erp-border">
                                <?php foreach ($obreiros as $obreiro): ?>
                                    <?php
                                    $nomeExibicao = (string) ($obreiro['nome_historico'] ?: $obreiro['nome']);
                                    $situacao = (string) ($obreiro['situacao_quadro'] ?? 'Regular');
                                    $situacaoVisual = match (mb_strtolower(trim($situacao), 'UTF-8')) {
                                        'ativo' => 'Regular',
                                        'inativo' => 'Afastado',
                                        'bloqueado' => 'Irregular',
                                        'pendente' => 'Em analise',
                                        default => $situacao,
                                    };
                                    $cargosAtuais = $obreiro['cargos_codigos'] ?? [];
                                    ?>
                                    <tr class="align-top hover:bg-slate-50/70">
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-erp-text"><?= htmlspecialchars($nomeExibicao) ?></div>
                                            <div class="mt-1 text-xs text-erp-muted">CIM <?= htmlspecialchars((string) ($obreiro['cim'] ?? '-')) ?> Â· <?= htmlspecialchars((string) ($obreiro['grau'] ?? 'NÃ£o informado')) ?></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                <?= htmlspecialchars($situacaoVisual) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php /* TODO(dados): aplicar StatusBadge de regularidade quando o backend expor esse indicador na listagem. */ ?>
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                Em analise
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($cargosAtuais !== []): ?>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php foreach ($cargosAtuais as $codigo): ?>
                                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                                            <?= htmlspecialchars(Cargo::rotuloOficial((string) $codigo)) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-xs text-erp-muted">Sem cargo oficial em exercicio</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <?php if ($podeGerenciarObreiros): ?>
                                                    <a href="/obreiros/editar?id=<?= htmlspecialchars((string) $obreiro['id']) ?>" class="rounded-lg border border-slate-900 bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                                        Atualizar obreiro
                                                    </a>
                                                    <form method="post" action="/obreiros/inativar">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                            Afastar do quadro
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($podeGerarConvitesAcesso): ?>
                                                    <form method="post" action="/admin/convites/gerar" onsubmit="return confirm('Gerar convite de acesso para este obreiro?');">
                                                        <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToAtual) ?>">
                                                        <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                                            Gerar convite
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="/obreiros/editar?id=<?= htmlspecialchars((string) $obreiro['id']) ?>#cargos-oficiais" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                    Nominata
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php foreach ($obreiros as $obreiro): ?>
                    <?php
                    $nomeExibicao = (string) ($obreiro['nome_historico'] ?: $obreiro['nome']);
                    $situacao = (string) ($obreiro['situacao_quadro'] ?? 'ativo');
                    $situacaoVisual = match (mb_strtolower(trim($situacao), 'UTF-8')) {
                        'ativo' => 'Regular',
                        'inativo' => 'Afastado',
                        'bloqueado' => 'Irregular',
                        'pendente' => 'Em analise',
                        default => $situacao,
                    };
                    $alertas = $obreiro['alertas_cadastro'] ?? [];
                    $cargosAtuais = $obreiro['cargos_codigos'] ?? [];
                    ?>
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:hidden">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="h-14 w-14 rounded-full bg-areia border border-amber-200 flex items-center justify-center text-cobalto text-xl font-bold shrink-0">
                                    <?= htmlspecialchars(substr($nomeExibicao, 0, 1)) ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($nomeExibicao) ?></h3>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            <?= htmlspecialchars((string) ($obreiro['grau'] ?? 'NÃ£o informado')) ?>
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-100">
                                            <?= htmlspecialchars($situacaoVisual) ?>
                                        </span>
                                        <?php if ($alertas !== []): ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 border border-amber-200">
                                                <?= count($alertas) ?> alerta(s) de registro
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                                        <span>CIM: <?= htmlspecialchars((string) ($obreiro['cim'] ?? '-')) ?></span>
                                        <span>Profissao: <?= htmlspecialchars((string) ($obreiro['profissao'] ?? '-')) ?></span>
                                        <span>Escolaridade: <?= htmlspecialchars((string) ($obreiro['escolaridade'] ?? '-')) ?></span>
                                        <span>Potencia: <?= htmlspecialchars((string) ($obreiro['potencia_sigla'] ?? $obreiro['potencia_nome'] ?? '-')) ?></span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 border <?= !empty($obreiro['telegram_id']) ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-gray-50 border-gray-200 text-gray-500' ?>">
                                            <i class="fab fa-telegram"></i>
                                            <?= !empty($obreiro['telegram_id']) ? 'Bot vinculado' : 'Sem bot vinculado' ?>
                                        </span>
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                            Tesouraria: Em analise
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-gray-50 border border-gray-200 px-2.5 py-1 text-gray-600">
                                            Ingresso: <?= htmlspecialchars((string) ($obreiro['data_filiacao'] ?? $obreiro['data_iniciacao'] ?? '-')) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <?php if ($podeGerenciarObreiros): ?>
                                    <a href="/obreiros/editar?id=<?= htmlspecialchars((string) $obreiro['id']) ?>" class="rounded-lg border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                        Atualizar obreiro
                                    </a>
                                    <form method="post" action="/obreiros/inativar">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                                            Afastar do quadro
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($podeGerarConvitesAcesso): ?>
                                    <form method="post" action="/admin/convites/gerar" onsubmit="return confirm('Gerar convite de acesso para este obreiro?');">
                                        <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToAtual) ?>">
                                        <button type="submit" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                                            Gerar convite
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="/obreiros/editar?id=<?= htmlspecialchars((string) $obreiro['id']) ?>#cargos-oficiais" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 bg-white hover:bg-gray-50">
                                    Ver nominata
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_1fr_0.9fr]">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Cargos ativos</div>
                                <?php if ($cargosAtuais !== []): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($cargosAtuais as $codigo): ?>
                                            <span class="inline-flex items-center rounded-full bg-white border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700">
                                                <?= htmlspecialchars(Cargo::rotuloOficial((string) $codigo)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-gray-500">Sem cargo oficial em exercicio na nominata.</div>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Alertas de registro</div>
                                <?php if ($alertas !== []): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($alertas as $alerta): ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-medium text-amber-800">
                                                <?= htmlspecialchars($rotulosAlerta[$alerta] ?? $alerta) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-emerald-700">Sem alerta principal de registro.</div>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Acoes visiveis</div>
                                <div class="flex flex-col gap-2">
                                    <?php if ($podeGerenciarObreiros): ?>
                                        <a href="/obreiros/editar?id=<?= htmlspecialchars((string) $obreiro['id']) ?>" class="rounded-lg bg-white border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Atualizar obreiro
                                        </a>
                                        <form method="post" action="/obreiros/inativar">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                            <button type="submit" class="rounded-lg bg-white border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50 text-left">
                                                Afastar do quadro
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="/obreiros?busca=<?= urlencode((string) ($obreiro['cim'] ?? '')) ?>" class="rounded-lg bg-white border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Filtrar este obreiro
                                    </a>
                                    <?php if ($alertas !== []): ?>
                                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                            Secretaria: tratar reservadamente com o obreiro.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
    <script>
        document.querySelectorAll('.copy-btn').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const text = btn.getAttribute('data-copy') || '';
                if (!text) return;
                try {
                    await navigator.clipboard.writeText(text);
                    const original = btn.textContent;
                    btn.textContent = 'Copiado';
                    setTimeout(() => (btn.textContent = original || 'Copiar link'), 1200);
                } catch (error) {
                    alert('Nao foi possivel copiar automaticamente. Use o botao Selecionar link e copie manualmente.');
                }
            });
        });

        document.querySelectorAll('.select-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target') || '';
                const field = targetId ? document.getElementById(targetId) : null;
                if (!field) return;
                field.focus();
                field.select();
            });
        });

        (function () {
            const filtrosAvancados = document.getElementById('obreiros-filtros-avancados');
            if (!filtrosAvancados || !window.matchMedia) {
                return;
            }

            const media = window.matchMedia('(min-width: 768px)');
            const syncFiltros = function (event) {
                filtrosAvancados.open = event.matches;
            };

            syncFiltros(media);
            if (typeof media.addEventListener === 'function') {
                media.addEventListener('change', syncFiltros);
            } else if (typeof media.addListener === 'function') {
                media.addListener(syncFiltros);
            }
        })();
    </script>
<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

