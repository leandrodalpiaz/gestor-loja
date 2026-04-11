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
$rotulosAlerta = [
    'sem_nascimento' => 'Nascimento ausente',
    'sem_escolaridade' => 'Escolaridade ausente',
    'sem_profissao' => 'Profissao ausente',
    'sem_situacao' => 'Situacao do quadro ausente',
    'sem_data_ingresso' => 'Data de ingresso ausente',
    'sem_potencia' => 'Potencia ausente',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalto: '#0a192f',
                        ouro: '#cfa935',
                        pedra: '#f3f4f6',
                        areia: '#faf7ef'
                    },
                    fontFamily: {
                        serif: ['Merriweather', 'serif'],
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pedra font-sans text-gray-800 antialiased">
    <header class="bg-cobalto text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-start justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-[0.22em] text-gray-300">Secretaria</div>
                <h1 class="font-serif text-xl font-bold tracking-wider">Central de Obreiros</h1>
            </div>
            <div class="flex w-full max-w-[14rem] flex-col gap-2 sm:w-auto sm:max-w-none sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <a href="/obreiros/novo" class="order-1 rounded-lg bg-white px-4 py-2 text-center text-sm font-medium text-cobalto hover:bg-amber-50 sm:order-3">Adicionar obreiro</a>
                <a href="/obreiros?alerta=cadastro" class="order-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-center text-sm text-amber-900 hover:bg-amber-100 sm:order-2">Somente alertas</a>
                <a href="/admin/cargos" class="order-3 rounded-lg border border-white/20 px-3 py-2 text-center text-sm text-white hover:bg-white/10 sm:order-1">Nominata oficial</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
        <section class="grid grid-cols-2 gap-3 md:grid-cols-5 md:gap-4">
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                <div class="text-xs text-gray-500 md:text-sm">Total filtrado</div>
                <div class="mt-1 text-2xl font-semibold text-cobalto md:mt-2 md:text-3xl"><?= (int) $resumoObreiros['total'] ?></div>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:p-5">
                <div class="text-xs text-gray-500 md:text-sm">No quadro</div>
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
            Alertas de cadastro servem como lembrete interno para a Secretaria tratar o tema reservadamente com o membro, sem expor o motivo como bloqueio operacional.
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 mb-4">
                <h2 class="text-xl font-semibold text-cobalto">Filtros administrativos</h2>
                <p class="text-sm text-gray-500">Use os filtros para saneamento cadastral, conferencia da nominata e preparacao dos relatorios.</p>
            </div>

            <form method="GET" action="/obreiros" class="space-y-4">
                <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                    <div class="md:col-span-2 xl:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Busca</label>
                    <input
                        type="text"
                        name="busca"
                        value="<?= htmlspecialchars((string) ($filtrosObreiros['busca'] ?? '')) ?>"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2"
                        placeholder="Nome, nome historico, grau ou CIM"
                    >
                </div>
                    <div class="grid grid-cols-2 gap-2 md:col-span-1 xl:col-span-1">
                        <button type="submit" class="rounded-lg bg-cobalto px-4 py-2 text-sm font-medium text-white hover:bg-blue-900">Aplicar</button>
                        <a href="/obreiros" class="rounded-lg border border-gray-300 px-4 py-2 text-center text-sm text-gray-700 bg-white hover:bg-gray-50">Limpar</a>
                    </div>
                </div>

                <details id="obreiros-filtros-avancados" class="group rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 md:border-0 md:bg-transparent md:p-0">
                    <summary class="cursor-pointer list-none text-sm font-medium text-cobalto md:hidden">
                        Mais filtros
                    </summary>
                    <div class="mt-3 grid gap-3 md:mt-0 md:grid-cols-3 xl:grid-cols-5">
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
                                <option value="cadastro" <?= ($filtrosObreiros['alerta'] ?? '') === 'cadastro' ? 'selected' : '' ?>>Com alerta cadastral</option>
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
            </form>
        </section>

        <section class="space-y-4">
            <?php if (empty($obreiros)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                    <p>Nenhum obreiro encontrado com os filtros atuais.</p>
                </div>
            <?php else: ?>
                <?php foreach ($obreiros as $obreiro): ?>
                    <?php
                    $nomeExibicao = (string) ($obreiro['nome_historico'] ?: $obreiro['nome']);
                    $situacao = (string) ($obreiro['situacao_quadro'] ?? 'ativo');
                    $alertas = $obreiro['alertas_cadastro'] ?? [];
                    $cargosAtuais = $obreiro['cargos_codigos'] ?? [];
                    ?>
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="h-14 w-14 rounded-full bg-areia border border-amber-200 flex items-center justify-center text-cobalto text-xl font-bold shrink-0">
                                    <?= htmlspecialchars(substr($nomeExibicao, 0, 1)) ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($nomeExibicao) ?></h3>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            <?= htmlspecialchars((string) ($obreiro['grau'] ?? 'Nao informado')) ?>
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-100">
                                            <?= htmlspecialchars($situacao) ?>
                                        </span>
                                        <?php if ($alertas !== []): ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 border border-amber-200">
                                                <?= count($alertas) ?> alerta(s)
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
                                            <?= !empty($obreiro['telegram_id']) ? 'Bot vinculado' : 'Sem bot' ?>
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-gray-50 border border-gray-200 px-2.5 py-1 text-gray-600">
                                            Ingresso: <?= htmlspecialchars((string) ($obreiro['data_filiacao'] ?? $obreiro['data_iniciacao'] ?? '-')) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a href="/obreiros/editar?id=<?= htmlspecialchars((string) $obreiro['id']) ?>" class="rounded-lg bg-cobalto px-4 py-2 text-sm font-medium text-white hover:bg-blue-900">
                                    Abrir ficha
                                </a>
                                <a href="/admin/cargos" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 bg-white hover:bg-gray-50">
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
                                    <div class="text-sm text-gray-500">Sem cargo oficial ativo na nominata.</div>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Alertas de cadastro</div>
                                <?php if ($alertas !== []): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($alertas as $alerta): ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-medium text-amber-800">
                                                <?= htmlspecialchars($rotulosAlerta[$alerta] ?? $alerta) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-emerald-700">Sem alerta cadastral principal.</div>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Acoes rapidas</div>
                                <div class="flex flex-col gap-2">
                                    <a href="/obreiros/editar?id=<?= htmlspecialchars((string) $obreiro['id']) ?>" class="rounded-lg bg-white border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Atualizar cadastro
                                    </a>
                                    <a href="/obreiros?busca=<?= urlencode((string) ($obreiro['cim'] ?? '')) ?>" class="rounded-lg bg-white border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Isolar este obreiro
                                    </a>
                                    <?php if ($alertas !== []): ?>
                                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                            Secretaria: tratar reservadamente com o membro.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <script>
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
</body>
</html>
