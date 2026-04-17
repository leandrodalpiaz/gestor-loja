<?php
use App\Models\Cargo;

$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$codigosEleitos = [
    'VENERAVEL',
    'PRIMEIRO_VIGILANTE',
    'SEGUNDO_VIGILANTE',
    'ORADOR',
    'TESOUREIRO',
];

$cargosEleitos = [];
$cargosNomeados = [];

foreach ($cargosResumo as $cargo) {
    $codigo = (string) ($cargo['codigo'] ?? '');
    if (in_array($codigo, $codigosEleitos, true)) {
        $cargosEleitos[] = $cargo;
        continue;
    }

    $cargosNomeados[] = $cargo;
}

$gruposNominata = [
    'Eleitos' => [
        'descricao' => 'Ofícios definidos em pleito e usados como referência central da gestão.',
        'classe' => 'border-amber-200 bg-amber-50',
        'cargos' => $cargosEleitos,
    ],
    'Nomeados' => [
        'descricao' => 'Ofícios administrativos providos para sustentar o trabalho ritual e executivo da Loja.',
        'classe' => 'border-slate-200 bg-slate-50',
        'cargos' => $cargosNomeados,
    ],
];

$erpPageTitle = 'Nominata Oficial';
$appShellEyebrow = 'Administracao';
$appShellTitle = 'Nominata Oficial e Gestoes';
$appShellDescription = 'Cargos eleitos, nomeados e historico administrativo da gestao em exercicio.';
$appShellActiveHref = '/admin/cargos';
$appShellActions = [
    ['label' => 'Parametros da Loja', 'href' => '/admin/loja'],
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Administracao',
        'items' => [
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
            ['label' => 'Parametros da Loja', 'href' => '/admin/loja'],
            ['label' => 'Dashboard', 'href' => '/dashboard'],
        ],
    ],
];
require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>
<?php /* TODO: a estrutura interna ainda carrega classes visuais legadas; consolidar depois sem mexer no fluxo da nominata. */ ?>
<?php if (false): ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominata Oficial e Gestões</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        marinho: '#10233f',
                        tinta: '#1d2d44',
                        dourado: '#c7a14b',
                        pergaminho: '#f6f0e1',
                        pedra: '#edf1f5'
                    },
                    fontFamily: {
                        display: ['Cormorant Garamond', 'serif'],
                        sans: ['Inter', 'sans-serif']
                    },
                    boxShadow: {
                        dignidade: '0 24px 70px rgba(16, 35, 63, 0.12)'
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,_#f7f3ea_0%,_#eef2f6_45%,_#e7edf3_100%)] font-sans text-slate-800">
    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[28rem] bg-[radial-gradient(circle_at_top,_rgba(199,161,75,0.22),_transparent_50%)]"></div>
        <div class="pointer-events-none absolute right-[-8rem] top-24 h-72 w-72 rounded-full bg-[radial-gradient(circle,_rgba(16,35,63,0.12),_transparent_68%)]"></div>

        <main class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <section class="overflow-hidden rounded-[2rem] border border-white/50 bg-[linear-gradient(135deg,_rgba(16,35,63,0.96),_rgba(21,40,72,0.92)_52%,_rgba(199,161,75,0.28)_100%)] px-6 py-8 text-white shadow-dignidade sm:px-8 lg:px-10 lg:py-10">
                <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <div class="text-[0.72rem] uppercase tracking-[0.34em] text-amber-200/90">Secretaria • Nominata Oficial</div>
                        <h1 class="mt-3 font-display text-5xl leading-none text-white sm:text-6xl">Gestão, ofícios e continuidade administrativa.</h1>
                        <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-200 sm:text-base">
                            Esta é a base oficial que sustenta validações de cargo, conferência de relatórios e a leitura formal da gestão em exercício.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="/admin/loja" class="rounded-full border border-white/20 px-4 py-2 text-sm text-white/90 transition hover:bg-white/10">Parametros da Loja</a>
                        <a href="/dashboard" class="rounded-full border border-white/20 px-4 py-2 text-sm text-white/90 transition hover:bg-white/10">Voltar ao painel</a>
                        <span class="rounded-full border border-amber-300/35 bg-white/10 px-4 py-2 text-sm text-amber-100">
                            <?= !empty($gestaoAtual['titulo']) ? htmlspecialchars((string) $gestaoAtual['titulo']) : 'Sem gestão aberta' ?>
                        </span>
                    </div>
                </div>
            </section>

            <?php if ($mensagem): ?>
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <?php if ($mensagemErro): ?>
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    <?= htmlspecialchars($mensagemErro) ?>
                </div>
            <?php endif; ?>
<?php endif; ?>

            <?php if ($mensagem): ?>
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <?php if ($mensagemErro): ?>
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                    <?= htmlspecialchars($mensagemErro) ?>
                </div>
            <?php endif; ?>

            <section class="mt-8 grid items-start gap-7 xl:grid-cols-12">
                <article class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm xl:col-span-8 2xl:col-span-9">
                    <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                        <div>
                            <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Gestão ativa</div>
                            <?php if (!empty($gestaoAtual)): ?>
                                <h2 class="mt-3 text-3xl font-semibold leading-tight text-erp-navy"><?= htmlspecialchars((string) ($gestaoAtual['titulo'] ?? 'Gestão atual')) ?></h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                                    A gestão permanece aberta até o encerramento formal ou até a consolidação final do relatório, preservando a flexibilidade administrativa da Loja.
                                </p>
                            <?php else: ?>
                                <h2 class="mt-3 text-3xl font-semibold leading-tight text-erp-navy">Nenhuma gestão aberta</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Abra uma nova gestão para habilitar a nominata oficial e o controle dos ofícios.</p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($gestaoAtual)): ?>
                            <div class="grid min-w-[20rem] gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50/90 p-4">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.26em] text-slate-500">Início</div>
                                    <div class="mt-1 text-lg font-semibold text-tinta"><?= htmlspecialchars((string) ($gestaoAtual['inicio_em'] ?? '-')) ?></div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-[0.26em] text-slate-500">Encerramento</div>
                                    <div class="mt-1 text-sm text-slate-600">Solicitado no fechamento da gestão ou na geração do relatório final.</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($gestaoAtual)): ?>
                        <form action="/admin/cargos/gestao/encerrar" method="POST" class="mt-6 grid gap-3 rounded-[1.5rem] border border-amber-200 bg-amber-50 p-4 sm:grid-cols-[1fr_auto]">
                            <input type="hidden" name="gestao_id" value="<?= (int) ($gestaoAtual['id'] ?? 0) ?>">
                            <input type="date" name="encerrada_em" class="w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-dourado focus:outline-none">
                            <button type="submit" class="rounded-xl bg-marinho px-5 py-3 text-sm font-medium text-white transition hover:bg-tinta">
                                Encerrar gestão
                            </button>
                        </form>
                    <?php endif; ?>
                </article>

                <article class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm xl:col-span-4 2xl:col-span-3">
                    <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Abertura de gestão</div>
                    <h2 class="mt-3 text-3xl font-semibold leading-tight text-erp-navy">Registrar um novo ciclo.</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        O período nasce com o início da gestão e pode ser encerrado depois, sem travar a rotina do Secretário.
                    </p>

                    <form action="/admin/cargos/gestao/salvar" method="POST" class="mt-6 grid gap-3">
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Título da gestão</label>
                            <input type="text" name="titulo" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-dourado focus:bg-white focus:outline-none" placeholder="Ex.: Gestão 2026/2027">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Data de início</label>
                            <input type="date" name="inicio_em" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Observação</label>
                            <input type="text" name="observacao" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-dourado focus:bg-white focus:outline-none" placeholder="Informações administrativas opcionais">
                        </div>
                        <button type="submit" class="mt-2 rounded-xl bg-erp-navy px-5 py-3 text-sm font-semibold text-white transition hover:opacity-95">
                            Abrir gestão
                        </button>
                    </form>
                </article>
            </section>

            <section class="mt-10">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Nominata da gestão atual</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Leitura operacional da gestão.</h2>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-slate-600">
                        Os ofícios permanecem organizados por bloco e podem ser atualizados individualmente sem perder o histórico da gestão.
                    </p>
                </div>

                <div class="mt-6 space-y-8">
                    <?php foreach ($gruposNominata as $tituloGrupo => $grupo): ?>
                        <section class="overflow-hidden rounded-[1.5rem] border <?= $grupo['classe'] ?> p-5 shadow-sm sm:p-6">
                            <div class="flex flex-col gap-3 border-b border-slate-200/70 pb-5 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <div class="text-[0.72rem] uppercase tracking-[0.3em] text-slate-500"><?= htmlspecialchars($tituloGrupo) ?></div>
                                    <h3 class="mt-2 text-2xl font-semibold leading-tight text-erp-navy"><?= htmlspecialchars($tituloGrupo) ?></h3>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600"><?= htmlspecialchars($grupo['descricao']) ?></p>
                                </div>
                                <div class="rounded-full border border-white/70 bg-white/70 px-4 py-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-600">
                                    <?= count($grupo['cargos']) ?> cargo(s)
                                </div>
                            </div>

                            <div class="mt-6 grid gap-6 2xl:grid-cols-2">
                                <?php foreach ($grupo['cargos'] as $cargo): ?>
                                    <article class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                                        <div class="flex flex-col gap-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="text-[0.7rem] uppercase tracking-[0.28em] text-slate-500"><?= htmlspecialchars((string) ($cargo['codigo'] ?? '')) ?></div>
                                                    <h4 class="mt-2 text-2xl font-semibold leading-tight text-slate-900">
                                                        <?= htmlspecialchars(Cargo::rotuloOficial((string) ($cargo['codigo'] ?? ''), (string) ($cargo['nome_exibicao'] ?? ''))) ?>
                                                    </h4>
                                                </div>
                                                <div class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[0.68rem] uppercase tracking-[0.22em] text-amber-800">
                                                    <?= $tituloGrupo === 'Eleitos' ? 'Pleito' : 'Nomeação' ?>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 rounded-[1.25rem] border border-slate-200 bg-slate-50/90 p-4 sm:grid-cols-[1.2fr_0.8fr]">
                                                <div>
                                                    <div class="text-[0.68rem] uppercase tracking-[0.24em] text-slate-500">Titular atual</div>
                                                    <?php if (!empty($cargo['titular_nome'])): ?>
                                                        <div class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars((string) $cargo['titular_nome']) ?></div>
                                                        <div class="mt-1 text-sm text-slate-600">CIM <?= htmlspecialchars((string) ($cargo['titular_cim'] ?? '-')) ?></div>
                                                    <?php else: ?>
                                                        <div class="mt-2 text-sm text-slate-500">Titular ainda não definido.</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="text-[0.68rem] uppercase tracking-[0.24em] text-slate-500">Registro</div>
                                                    <div class="mt-2 text-sm text-slate-700">
                                                        <?= !empty($cargo['inicio_em']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $cargo['inicio_em']))) : 'Sem data' ?>
                                                    </div>
                                                    <div class="mt-2 text-xs leading-5 text-slate-500">
                                                        <?= !empty($cargo['observacao']) ? htmlspecialchars((string) $cargo['observacao']) : 'Sem observação complementar.' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <form action="/admin/cargos/salvar" method="POST" class="grid gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                                                <input type="hidden" name="cargo_codigo" value="<?= htmlspecialchars((string) ($cargo['codigo'] ?? '')) ?>">
                                                <input type="hidden" name="gestao_id" value="<?= (int) ($gestaoAtual['id'] ?? 0) ?>">

                                                <div>
                                                    <label class="mb-2 block text-[0.68rem] uppercase tracking-[0.24em] text-slate-500">Novo titular</label>
                                                    <select name="obreiro_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-dourado focus:bg-white focus:outline-none" required>
                                                        <option value="">Selecione o obreiro</option>
                                                        <?php foreach ($obreiros as $obreiro): ?>
                                                            <option value="<?= htmlspecialchars((string) $obreiro['id']) ?>">
                                                                <?= htmlspecialchars((string) (($obreiro['nome_historico'] ?? $obreiro['nome'] ?? '') . ' - CIM ' . ($obreiro['cim'] ?? '-'))) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="grid gap-3 sm:grid-cols-[0.92fr_1.08fr]">
                                                    <div>
                                                        <label class="mb-2 block text-[0.68rem] uppercase tracking-[0.24em] text-slate-500">Início da titularidade</label>
                                                        <input type="datetime-local" name="inicio_em" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-dourado focus:bg-white focus:outline-none">
                                                    </div>
                                                    <div>
                                                        <label class="mb-2 block text-[0.68rem] uppercase tracking-[0.24em] text-slate-500">Observação</label>
                                                        <input type="text" name="observacao" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-dourado focus:bg-white focus:outline-none" placeholder="Motivo ou nota administrativa">
                                                    </div>
                                                </div>

                                                <button type="submit" class="rounded-xl bg-marinho px-4 py-3 text-sm font-medium text-white transition hover:bg-tinta">
                                                    Atualizar titular
                                                </button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="mt-10 grid items-start gap-7 xl:grid-cols-12">
                <article class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm xl:col-span-5 2xl:col-span-4">
                    <div class="flex items-end justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Histórico</div>
                    <h2 class="mt-2 text-2xl font-semibold leading-tight text-erp-navy">Movimentações da gestão atual</h2>
                        </div>
                    </div>
                    <div class="mt-5 space-y-3 max-h-[42rem] overflow-y-auto pr-1">
                        <?php foreach ($historico as $item): ?>
                            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/90 p-4">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-tinta">
                                            <?= htmlspecialchars(Cargo::rotuloOficial((string) ($item['codigo'] ?? ''), (string) ($item['nome_exibicao'] ?? ''))) ?>
                                        </div>
                                        <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($item['obreiro_nome'] ?? '')) ?> • CIM <?= htmlspecialchars((string) ($item['cim'] ?? '-')) ?></div>
                                    </div>
                                    <div class="text-xs uppercase tracking-[0.22em] text-slate-500">
                                        <?= !empty($item['fim_em']) ? 'Concluído' : 'Ativo' ?>
                                    </div>
                                </div>
                                <div class="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                    <div>Início: <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $item['inicio_em']))) ?></div>
                                    <div>Fim: <?= !empty($item['fim_em']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $item['fim_em']))) : 'Ativo' ?></div>
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    <?= !empty($item['observacao']) ? htmlspecialchars((string) $item['observacao']) : 'Sem observação complementar.' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm xl:col-span-7 2xl:col-span-8">
                    <div class="flex items-end justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Arquivo administrativo</div>
                    <h2 class="mt-2 text-2xl font-semibold leading-tight text-erp-navy">Gestões cadastradas</h2>
                        </div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <?php foreach ($gestoes as $gestao): ?>
                            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-lg font-semibold text-tinta"><?= htmlspecialchars((string) ($gestao['titulo'] ?? 'Gestão')) ?></div>
                                    <div class="rounded-full px-3 py-1 text-[0.68rem] uppercase tracking-[0.22em] <?= ($gestao['status'] ?? '') === 'aberta' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                        <?= htmlspecialchars((string) ($gestao['status'] ?? '')) ?>
                                    </div>
                                </div>
                                <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                                    <div>Início: <?= htmlspecialchars((string) ($gestao['inicio_em'] ?? '-')) ?></div>
                                    <div>Encerramento: <?= htmlspecialchars((string) ($gestao['encerrada_em'] ?? '-')) ?></div>
                                </div>
                                <div class="mt-2 text-xs leading-5 text-slate-500">
                                    <?= htmlspecialchars((string) ($gestao['observacao'] ?? 'Sem observações.')) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>
<?php if (false): ?>
        </main>
    </div>
</body>
</html>
<?php endif; ?>
<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
