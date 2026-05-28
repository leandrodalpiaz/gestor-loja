<?php
declare(strict_types=1);

$sessoes = is_array($sessoes ?? null) ? $sessoes : [];
$sessao = is_array($sessao ?? null) ? $sessao : null;
$confirmados = is_array($confirmados ?? null) ? $confirmados : [];
$agape = is_array($agape ?? null) ? $agape : [];
$trabalhos = is_array($trabalhos ?? null) ? $trabalhos : [];
$publicacoes = is_array($publicacoes ?? null) ? $publicacoes : [];
$balaustres = is_array($balaustres ?? null) ? $balaustres : [];
$relatorio = is_array($relatorio ?? null) ? $relatorio : [];
$sessaoId = (int) ($sessao['id'] ?? 0);

$pwaPageTitle = 'Secretaria';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/admin';
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="px-4 py-4 space-y-4">
    <?php if (!empty($mensagemSucesso)): ?>
        <div class="pwa-alert-success"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if (!empty($mensagemErro)): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero">
        <p class="pwa-eyebrow">Operação da Secretaria</p>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-white"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Agenda e balaústres')) ?></h2>
        <p class="pwa-muted mt-1.5 text-xs">Sessões, confirmados, ágape, trabalhos, publicações, balaústre e relatórios.</p>
    </section>

    <!-- Atalhos da secretaria (Estilo botões de aplicativo) -->
    <section class="grid grid-cols-2 gap-2.5 select-none">
        <a href="/pwa/secretaria/sessoes" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Sessões</a>
        <a href="/pwa/secretaria/balaustres" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Balaústres</a>
        <a href="/pwa/secretaria/trabalhos-publicacoes" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Trabalhos/Pubs</a>
        <a href="/pwa/secretaria/convites-externos" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Convites Ext</a>
        <a href="/pwa/secretaria/votacao" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Votação</a>
        <a href="/pwa/secretaria/relatorio-anual" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Relatórios</a>
        <a href="/pwa/secretaria/nominata" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Nominata</a>
        <a href="/pwa/secretaria/acessos" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Acessos</a>
        <a href="/pwa/secretaria/convites" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Convites</a>
        <a href="/pwa/secretaria/conteudo-publico" class="pwa-btn-secondary py-2.5 px-1 text-center font-bold text-[11px] truncate">Conteúdo Público</a>
    </section>

    <!-- Sessão de trabalho select -->
    <form method="get" action="/pwa/secretaria" class="pwa-card">
        <label class="pwa-label">Sessão de Trabalho</label>
        <div class="relative mt-1">
            <select name="sessao_id" class="pwa-select pr-10">
                <?php foreach ($sessoes as $opcao): ?>
                    <option value="<?= (int) ($opcao['id'] ?? 0) ?>" <?= (int) ($opcao['id'] ?? 0) === $sessaoId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($opcao['titulo'] ?? 'Sessão')) ?> - <?= htmlspecialchars((string) ($opcao['data_hora_inicio'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
        <button class="pwa-btn-secondary mt-3 w-full">Carregar</button>
    </form>

    <!-- Indicadores Rápidos -->
    <section class="grid grid-cols-3 gap-2.5">
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-xl font-bold text-slate-100"><?= count($confirmados) ?></div>
            <div class="text-[9px] text-slate-500 font-semibold uppercase mt-0.5 tracking-wider">Confirmados</div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-xl font-bold text-slate-100"><?= count($agape) ?></div>
            <div class="text-[9px] text-slate-500 font-semibold uppercase mt-0.5 tracking-wider">Ágape</div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-xl font-bold text-slate-100"><?= (int) ($relatorio['totais']['sessoes'] ?? 0) ?></div>
            <div class="text-[9px] text-slate-500 font-semibold uppercase mt-0.5 tracking-wider">Sessões/ano</div>
        </div>
    </section>

    <!-- Ações da Sessão -->
    <?php if ($sessaoId > 0): ?>
        <section class="pwa-card space-y-3 select-none">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Ações da Sessão</h3>
            <div class="grid grid-cols-3 gap-2">
                <form method="post" action="/pwa/secretaria/sessoes/publicar" class="w-full">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg py-2 text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 active:scale-95 transition-transform">Publicar</button>
                </form>
                <form method="post" action="/pwa/secretaria/sessoes/cancelar" class="w-full">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg py-2 text-xs font-bold text-red-400 bg-red-500/10 border border-red-500/20 active:scale-95 transition-transform">Cancelar</button>
                </form>
                <form method="post" action="/pwa/secretaria/sessoes/reabrir" class="w-full">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg py-2 text-xs font-bold text-slate-300 bg-slate-800 border border-white/5 active:scale-95 transition-transform">Reabrir</button>
                </form>
            </div>
            <a href="/secretaria/sessoes?editar_sessao=<?= $sessaoId ?>" class="pwa-btn-secondary text-xs font-bold w-full mt-2">Editar Sessão Completa no Desktop</a>
        </section>
    <?php endif; ?>

    <!-- Registrar Trabalho Form -->
    <section class="pwa-card space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Registrar Trabalho</h3>
        <form method="post" action="/pwa/secretaria/trabalhos/salvar" class="space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="titulo" class="pwa-input" placeholder="Título do trabalho">
            <input name="autor_nome_livre" class="pwa-input" placeholder="Autor">
            <button class="pwa-btn-primary">Salvar trabalho</button>
        </form>
    </section>

    <!-- Publicação Form -->
    <section class="pwa-card space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Comunicação/Publicação</h3>
        <form method="post" action="/pwa/secretaria/publicacoes/salvar" class="space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="titulo" class="pwa-input" placeholder="Título">
            <textarea name="conteudo" rows="3" class="pwa-textarea" placeholder="Conteúdo"></textarea>
            <button class="pwa-btn-primary">Salvar publicação</button>
        </form>
        <a href="/pwa/comunicacao" class="pwa-btn-secondary w-full text-xs font-bold mt-2 select-none">Abrir Comunicação PWA</a>
    </section>

    <!-- Balaústre rápido -->
    <section class="pwa-card space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Balaústre Rápido</h3>
        <form method="post" action="/pwa/secretaria/balaustres/salvar" class="space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="numero_balaustre" class="pwa-input" placeholder="Número do balaústre">
            <textarea name="texto_final" rows="4" class="pwa-textarea" placeholder="Texto ou apontamentos"></textarea>
            <button class="pwa-btn-primary">Salvar rascunho</button>
        </form>
        <a href="/secretaria/balaustres?sessao_resumo=<?= $sessaoId ?>" class="pwa-btn-secondary w-full text-xs font-bold mt-2 select-none">Compor balaústre completo no Desktop</a>
    </section>

    <!-- Últimos Registros list -->
    <section class="space-y-3 pb-4">
        <div class="flex items-center gap-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Últimos Registros
            </p>
            <div class="flex-1 h-[1px] bg-white/5"></div>
        </div>
        <?php foreach ([['Trabalhos', $trabalhos], ['Publicações', $publicacoes], ['Balaustres', $balaustres]] as $grupo): ?>
            <div class="pwa-card space-y-3 border border-white/5">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-1"><?= htmlspecialchars($grupo[0]) ?></h4>
                <div class="pwa-list-group">
                    <?php foreach (array_slice($grupo[1], 0, 3) as $item): ?>
                        <div class="pwa-list-item flex flex-col items-start gap-1 justify-center">
                            <div class="text-xs font-bold text-slate-200 truncate w-full"><?= htmlspecialchars((string) ($item['titulo'] ?? $item['numero_balaustre'] ?? 'Registro')) ?></div>
                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars((string) ($item['status'] ?? $item['status_publicacao'] ?? $item['status_envio_potencia'] ?? '')) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
