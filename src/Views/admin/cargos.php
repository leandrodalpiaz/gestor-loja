<?php
declare(strict_types=1);

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

$formatarDataExibicao = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return 'Data a definir';
    }

    try {
        return (new DateTimeImmutable($valor))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d-m-Y');
    } catch (\Throwable $e) {
        return $valor;
    }
};

$formatarDataHoraExibicao = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return 'Data a definir';
    }

    try {
        return (new DateTimeImmutable($valor))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d-m-Y \à\s H:i');
    } catch (\Throwable $e) {
        return $valor;
    }
};

foreach ($cargosResumo as $cargo) {
    $codigo = (string) ($cargo['codigo'] ?? '');
    if (strtoupper($codigo) === 'ADMINISTRADOR') {
        continue;
    }
    if (in_array($codigo, $codigosEleitos, true)) {
        $cargosEleitos[] = $cargo;
        continue;
    }

    $cargosNomeados[] = $cargo;
}

$gruposNominata = [
    'Eleitos' => [
        'descricao' => 'Ofícios definidos em pleito e usados como referência central da gestão.',
        'classe' => 'border-warning/20 bg-warning/5 text-warning',
        'cargos' => $cargosEleitos,
    ],
    'Nomeados' => [
        'descricao' => 'Ofícios administrativos providos para sustentar o trabalho ritual e executivo da Loja.',
        'classe' => 'border-white/10 bg-white/[0.02]',
        'cargos' => $cargosNomeados,
    ],
];

$erpPageTitle = 'Nominata Oficial';
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Nominata Oficial';
$appShellDescription = 'Fonte institucional dos cargos da Loja (base para acessos e permissões).';
$appShellActiveHref = '/admin/cargos';
$appShellActions = [
    ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Nominata Oficial', 'href' => '/admin/cargos'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos', 'href' => '/admin/acessos'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaústres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatório Anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

if (!empty($_SESSION['is_system_admin'])) {
    $appShellSidebarSections[] = [
        'title' => 'Sistema',
        'items' => [
            ['label' => 'Painel do sistema', 'href' => '/sistema'],
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
        ],
    ];
}
require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Notificações -->
<?php if ($mensagem): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<section class="grid items-start gap-7 xl:grid-cols-12 mb-8">
    <article class="card depth-1 xl:col-span-8 2xl:col-span-9">
        <div class="card-header border-b border-erp-border/50 p-6 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Gestão ativa</div>
                <?php if (!empty($gestaoAtual)): ?>
                    <h2 class="mt-2 text-2xl font-black leading-tight text-white"><?= htmlspecialchars((string) ($gestaoAtual['titulo'] ?? 'Gestão atual')) ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-400 font-sans">
                        A gestão permanece aberta até o encerramento formal ou até a consolidação final do relatório, preservando a flexibilidade administrativa da Loja.
                    </p>
                <?php else: ?>
                    <h2 class="mt-2 text-2xl font-black leading-tight text-white">Nenhuma gestão aberta</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-400 font-sans">Abra uma nova gestão para habilitar a nominata oficial e o controle dos ofícios.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($gestaoAtual)): ?>
                <div class="grid min-w-[20rem] gap-3 rounded-[1.5rem] border border-white/5 bg-white/[0.02] p-4">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.26em] text-slate-400">Início</div>
                        <div class="mt-1 text-lg font-semibold text-white"><?= htmlspecialchars((string) ($gestaoAtual['inicio_em'] ?? '-')) ?></div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.26em] text-slate-400">Encerramento</div>
                        <div class="mt-1 text-xs text-slate-300">Solicitado no fechamento da gestão ou na geração do relatório final.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($gestaoAtual)): ?>
            <div class="card-body p-6">
                <form action="/admin/cargos/gestao/encerrar" method="POST" class="grid gap-3 rounded-[1.5rem] border border-warning/20 bg-warning/5 p-4 sm:grid-cols-[1fr_auto] items-center">
                    <input type="hidden" name="gestao_id" value="<?= (int) ($gestaoAtual['id'] ?? 0) ?>">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Data de Encerramento</label>
                        <input type="date" name="encerrada_em" class="form-input w-full" required>
                    </div>
                    <button type="submit" class="btn btn-primary mt-5">
                        Encerrar gestão
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </article>

    <article class="card depth-1 xl:col-span-4 2xl:col-span-3">
        <div class="card-header border-b border-erp-border/50 p-6">
            <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Abertura de gestão</div>
            <h2 class="mt-2 text-xl font-black leading-tight text-white">Novo ciclo administrativo</h2>
        </div>
        <div class="card-body p-6">
            <form action="/admin/cargos/gestao/salvar" method="POST" class="grid gap-4">
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Título da gestão</label>
                    <input type="text" name="titulo" class="form-input w-full" placeholder="Ex.: Gestão 2026/2027" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Data de início</label>
                    <input type="date" name="inicio_em" class="form-input w-full" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Observação</label>
                    <input type="text" name="observacao" class="form-input w-full" placeholder="Informações adicionais opcionais">
                </div>
                <button type="submit" class="btn btn-primary w-full mt-2">
                    Abrir gestão
                </button>
            </form>
        </div>
    </article>
</section>

<section class="mt-10">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Nominata da gestão atual</div>
            <h2 class="mt-2 text-2xl font-black leading-tight text-white">Leitura operacional da gestão</h2>
        </div>
        <p class="max-w-xl text-sm leading-6 text-slate-400 font-sans">
            Os ofícios permanecem organizados por bloco e podem ser atualizados individualmente sem perder o histórico da gestão.
        </p>
    </div>

    <div class="space-y-8">
        <?php foreach ($gruposNominata as $tituloGrupo => $grupo): ?>
            <section class="card depth-1 p-6 relative">
                <div class="flex flex-col gap-3 border-b border-white/5 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-500"><?= htmlspecialchars($tituloGrupo) ?></div>
                        <h3 class="mt-1 text-xl font-bold leading-tight text-white"><?= htmlspecialchars($tituloGrupo) ?></h3>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400 font-sans"><?= htmlspecialchars($grupo['descricao']) ?></p>
                    </div>
                    <div class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-black uppercase tracking-[0.2em] text-slate-300">
                        <?= count($grupo['cargos']) ?> cargo(s)
                    </div>
                </div>

                <div class="mt-6 grid gap-6 2xl:grid-cols-2">
                    <?php foreach ($grupo['cargos'] as $cargo): ?>
                        <article id="cargo-<?= htmlspecialchars((string) ($cargo['codigo'] ?? '')) ?>" class="p-5 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-white/10 transition-all flex flex-col justify-between gap-4">
                            <div>
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-[10px] font-mono uppercase tracking-[0.28em] text-slate-500"><?= htmlspecialchars((string) ($cargo['codigo'] ?? '')) ?></div>
                                        <h4 class="mt-1 text-lg font-bold leading-tight text-white">
                                            <?= htmlspecialchars(Cargo::rotuloOficial((string) ($cargo['codigo'] ?? ''), (string) ($cargo['nome_exibicao'] ?? ''))) ?>
                                        </h4>
                                    </div>
                                    <span class="badge <?= $tituloGrupo === 'Eleitos' ? 'badge-status-warning' : 'badge-status-info' ?> text-[10px] font-black uppercase tracking-[0.22em]">
                                        <?= $tituloGrupo === 'Eleitos' ? 'Pleito' : 'Nomeação' ?>
                                    </span>
                                </div>

                                <div class="grid gap-4 rounded-[1.25rem] border border-white/5 bg-white/[0.02] p-4 mt-4 sm:grid-cols-[1.2fr_0.8fr]">
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Titular atual</div>
                                        <?php if (!empty($cargo['titular_nome'])): ?>
                                            <div class="mt-2 text-base font-semibold text-white"><?= htmlspecialchars((string) $cargo['titular_nome']) ?></div>
                                            <div class="mt-1 text-xs text-slate-400">CIM <?= htmlspecialchars((string) ($cargo['titular_cim'] ?? '-')) ?></div>
                                        <?php else: ?>
                                            <div class="mt-2 text-xs text-slate-500 italic">Titular ainda não definido.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Registro</div>
                                        <div class="mt-2 text-xs text-slate-300">
                                            <?= !empty($cargo['inicio_em']) ? htmlspecialchars($formatarDataHoraExibicao((string) $cargo['inicio_em'])) : 'Data a definir' ?>
                                        </div>
                                        <div class="mt-2 text-[10px] leading-5 text-slate-500">
                                            <?= !empty($cargo['observacao']) ? htmlspecialchars((string) $cargo['observacao']) : 'Sem observação complementar.' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form action="/admin/cargos/salvar" method="POST" class="grid gap-3 rounded-[1.25rem] border border-white/5 bg-white/[0.01] p-4 mt-2">
                                <input type="hidden" name="cargo_codigo" value="<?= htmlspecialchars((string) ($cargo['codigo'] ?? '')) ?>">
                                <input type="hidden" name="gestao_id" value="<?= (int) ($gestaoAtual['id'] ?? 0) ?>">

                                <div>
                                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Novo titular</label>
                                    <select name="obreiro_id" class="form-select w-full" required>
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
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Início da titularidade</label>
                                        <input type="datetime-local" name="inicio_em" class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Observação</label>
                                        <input type="text" name="observacao" class="form-input w-full" placeholder="Motivo ou nota">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-full text-xs font-black uppercase tracking-widest">
                                    Atualizar titular
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-10 grid items-start gap-7 xl:grid-cols-12 mb-8">
    <article class="card depth-1 xl:col-span-5 2xl:col-span-4">
        <div class="card-header border-b border-erp-border/50 p-6">
            <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Histórico</div>
            <h2 class="mt-2 text-xl font-black leading-tight text-white">Movimentações</h2>
        </div>
        <div class="card-body p-6 space-y-3 max-h-[42rem] overflow-y-auto pr-1">
            <?php foreach ($historico as $item): ?>
                <div class="rounded-[1.25rem] border border-white/5 bg-white/[0.02] p-4 text-xs space-y-2">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="font-bold text-white">
                                <?= htmlspecialchars(Cargo::rotuloOficial((string) ($item['codigo'] ?? ''), (string) ($item['nome_exibicao'] ?? ''))) ?>
                            </div>
                            <div class="mt-1 text-slate-300"><?= htmlspecialchars((string) ($item['obreiro_nome'] ?? '')) ?> • CIM <?= htmlspecialchars((string) ($item['cim'] ?? '-')) ?></div>
                        </div>
                        <span class="badge border text-[9px] font-black uppercase tracking-[0.22em] <?= !empty($item['fim_em']) ? 'bg-white/5 text-slate-400 border-white/10' : 'badge-status-success' ?>">
                            <?= !empty($item['fim_em']) ? 'Concluído' : 'Ativo' ?>
                        </span>
                    </div>
                    <div class="grid gap-2 text-[10px] text-slate-400 sm:grid-cols-2 pt-2 border-t border-white/5">
                        <div>Início: <?= htmlspecialchars($formatarDataHoraExibicao((string) ($item['inicio_em'] ?? ''))) ?></div>
                        <div>Fim: <?= !empty($item['fim_em']) ? htmlspecialchars($formatarDataHoraExibicao((string) $item['fim_em'])) : 'Atual' ?></div>
                    </div>
                    <div class="text-[10px] leading-relaxed text-slate-500">
                        <?= !empty($item['observacao']) ? htmlspecialchars((string) $item['observacao']) : 'Sem observação complementar.' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="card depth-1 xl:col-span-7 2xl:col-span-8">
        <div class="card-header border-b border-erp-border/50 p-6">
            <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Arquivo administrativo</div>
            <h2 class="mt-2 text-xl font-black leading-tight text-white">Gestões registradas</h2>
        </div>
        <div class="card-body p-6 space-y-3">
            <?php foreach ($gestoes as $gestao): ?>
                <div class="rounded-[1.25rem] border border-white/5 bg-white/[0.02] p-4 text-xs">
                    <div class="flex items-center justify-between gap-3 border-b border-white/5 pb-2.5 mb-2.5">
                        <div class="text-base font-bold text-white"><?= htmlspecialchars((string) ($gestao['titulo'] ?? 'Gestão')) ?></div>
                        <span class="badge text-[9px] font-black uppercase tracking-[0.22em] <?= ($gestao['status'] ?? '') === 'aberta' ? 'badge-status-success' : 'badge-status-secondary' ?>">
                            <?= htmlspecialchars((string) ($gestao['status'] ?? '')) ?>
                        </span>
                    </div>
                    <div class="grid gap-2 text-slate-300 sm:grid-cols-2">
                        <div>Início: <?= htmlspecialchars(!empty($gestao['inicio_em']) ? $formatarDataExibicao((string) $gestao['inicio_em']) : 'Data a definir') ?></div>
                        <div>Encerramento: <?= htmlspecialchars(!empty($gestao['encerrada_em']) ? $formatarDataExibicao((string) $gestao['encerrada_em']) : '-') ?></div>
                    </div>
                    <div class="mt-2 text-[10px] leading-relaxed text-slate-500">
                        <?= htmlspecialchars((string) ($gestao['observacao'] ?? 'Sem observações.')) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const { hash } = window.location;
        if (!hash) return;
        const el = document.querySelector(hash);
        if (!el) return;
        el.classList.add('ring-2', 'ring-amber-300', 'ring-offset-2');
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
</script>
