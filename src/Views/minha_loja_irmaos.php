<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Irmãos da Loja';
$appShellDescription = 'Consulta fraterna (sem dados sensíveis). Entre em contato ou veja detalhes.';
$appShellActiveHref = '/minha-loja/irmaos';
$appShellActions = [['label' => 'Voltar', 'href' => '/minha-loja']];

$q = trim((string) ($_GET['q'] ?? ''));
$selecionadoId = trim((string) ($_GET['id'] ?? ''));

$obreiros = is_array($obreiros ?? null) ? $obreiros : [];
$detalhe = is_array($detalhe ?? null) ? $detalhe : null;
$familiaresDetalhe = is_array($familiaresDetalhe ?? null) ? $familiaresDetalhe : [];
$estatisticasLoja = is_array($estatisticasLoja ?? null) ? $estatisticasLoja : [];
$aniversariantesMes = is_array($aniversariantesMes ?? null) ? $aniversariantesMes : [];

$fmtData = static function (?string $data): ?string {
    $data = trim((string) $data);
    if ($data === '') {
        return null;
    }
    try {
        return (new DateTimeImmutable($data))->format('d/m/Y');
    } catch (Throwable $e) {
        return $data;
    }
};

$obterIniciais = static function (string $nome): string {
    $partes = explode(' ', preg_replace('/\s+/', ' ', trim($nome)));
    if (count($partes) >= 2) {
        return strtoupper(substr($partes[0], 0, 1) . substr($partes[count($partes)-1], 0, 1));
    }
    return strtoupper(substr($nome, 0, 2));
};

$badgeGrau = static function (string $grau): string {
    $g = strtolower(trim($grau));
    if (str_contains($g, 'instalado')) {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">Mestre Instalado</span>';
    }
    if (str_contains($g, 'mestre')) {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-erp-gold/10 text-erp-gold border border-erp-gold/20 font-cinzel">Mestre</span>';
    }
    if (str_contains($g, 'companheiro')) {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Companheiro</span>';
    }
    return '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-500/10 text-slate-400 border border-slate-500/20">Aprendiz</span>';
};

require __DIR__ . '/partials/erp_shell_open.php';
?>

<!-- Mobile Selected Detail Anchor / Card at the top -->
<?php if ($detalhe): ?>
    <div class="block lg:hidden mb-6 space-y-6">
        <div class="card border border-erp-gold/30">
            <div class="card-header flex justify-between items-center">
                <h2 class="card-title text-white"><?= htmlspecialchars((string) ($detalhe['nome_historico'] ?? $detalhe['nome'] ?? 'Irmão')) ?></h2>
                <a href="/minha-loja/irmaos?q=<?= urlencode($q) ?>" class="text-xs text-slate-400 hover:text-white">&times; Fechar Detalhes</a>
            </div>
            <div class="card-body grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-slate-400 text-xs uppercase tracking-wider font-semibold">Grau</span><div class="font-medium text-white"><?= $badgeGrau((string) ($detalhe['grau'] ?? '')) ?></div></div>
                <div><span class="text-slate-400 text-xs uppercase tracking-wider font-semibold">Telefone</span><div class="font-medium text-white"><?= htmlspecialchars((string) ($detalhe['telefone'] ?? '-')) ?></div></div>
                <div class="col-span-2"><span class="text-slate-400 text-xs uppercase tracking-wider font-semibold">E-mail</span><div class="font-medium text-white"><?= htmlspecialchars((string) ($detalhe['email'] ?? '-')) ?></div></div>
                <?php $dn = $fmtData($detalhe['data_nascimento_civil'] ?? null); ?>
                <div><span class="text-slate-400 text-xs uppercase tracking-wider font-semibold">Nascimento</span><div class="font-medium text-white"><?= htmlspecialchars((string) ($dn ?: '-')) ?></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Familiares vinculados</h2></div>
            <div class="card-body space-y-3">
                <?php if ($familiaresDetalhe === []): ?>
                    <div class="text-sm text-slate-400">Nenhum familiar cadastrado.</div>
                <?php else: ?>
                    <?php foreach ($familiaresDetalhe as $f): ?>
                        <div class="list-item-condensed">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold text-white"><?= htmlspecialchars((string) ($f['nome_completo'] ?? '')) ?></div>
                                <span class="text-xs text-slate-400"><?= htmlspecialchars((string) ($f['parentesco'] ?? '')) ?></span>
                            </div>
                            <div class="text-sm text-slate-400 mt-1">
                                <?php $fdn = $fmtData($f['data_nascimento'] ?? null); ?>
                                <?php if ($fdn): ?>Nasc.: <?= htmlspecialchars((string) $fdn) ?><?php endif; ?>
                                <?php if (!empty($f['falecido'])): ?> · Falecido<?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Area: Search + Cards Grid (Cols 1 & 2) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Search bar -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="/minha-loja/irmaos" class="flex gap-2">
                    <input class="form-input" name="q" placeholder="Buscar por nome..." value="<?= htmlspecialchars($q) ?>">
                    <button class="btn btn-secondary px-6" type="submit">Buscar</button>
                    <?php if ($q !== ''): ?>
                        <a href="/minha-loja/irmaos" class="btn btn-secondary flex items-center justify-center">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Brothers Grid -->
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-white font-bold text-sm tracking-wider uppercase">Membros Encontrados (<?= count($obreiros) ?>)</h3>
            </div>
            
            <?php if ($obreiros === []): ?>
                <div class="card p-8 text-center text-slate-400">
                    Nenhum irmão encontrado na busca.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($obreiros as $o): ?>
                        <?php 
                        $oid = (string) ($o['id'] ?? ''); 
                        $nomeExibicao = (string) ($o['nome_historico'] ?? $o['nome'] ?? 'Irmão');
                        $isSelecionado = $selecionadoId === $oid;
                        ?>
                        <div class="glass-surface p-5 rounded-2xl border transition-all duration-300 relative group flex flex-col justify-between <?= $isSelecionado ? 'border-erp-gold/50 bg-[#162a42]' : 'border-white/5 hover:border-white/15 hover:bg-white/[0.02]' ?>">
                            <div class="flex items-start gap-4">
                                <!-- Initials Avatar -->
                                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-sm shrink-0 shadow-inner <?= $isSelecionado ? 'bg-erp-gold text-erp-navy-deep' : 'bg-white/5 text-erp-gold group-hover:scale-105 transition-transform' ?>">
                                    <?= htmlspecialchars($obterIniciais($nomeExibicao)) ?>
                                </div>
                                <div class="space-y-1">
                                    <div class="font-cinzel font-bold text-white text-sm line-clamp-1"><?= htmlspecialchars($nomeExibicao) ?></div>
                                    <div class="pt-0.5"><?= $badgeGrau((string) ($o['grau'] ?? '')) ?></div>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-xs">
                                <div class="flex gap-2">
                                    <?php if (!empty($o['telefone'])): ?>
                                        <?php 
                                        $whatsLink = 'https://wa.me/' . preg_replace('/\D/', '', (string) $o['telefone']); 
                                        ?>
                                        <a href="<?= $whatsLink ?>" target="_blank" rel="noopener" class="text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            Contato
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($o['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars((string) $o['email']) ?>" class="text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1 ml-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            Email
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <a href="/minha-loja/irmaos?id=<?= urlencode($oid) ?>&q=<?= urlencode($q) ?>" class="text-erp-gold hover:text-white font-bold tracking-wider uppercase text-[10px]">
                                    Detalhes &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Area: Detail Card or Lodge Dashboard (Col 3) -->
    <div class="hidden lg:block space-y-6">
        <?php if ($detalhe): ?>
            <!-- Selected Obreiro Details -->
            <div class="card border border-erp-gold/30">
                <div class="card-header flex justify-between items-center">
                    <h2 class="card-title text-white"><?= htmlspecialchars((string) ($detalhe['nome_historico'] ?? $detalhe['nome'] ?? 'Irmão')) ?></h2>
                    <a href="/minha-loja/irmaos?q=<?= urlencode($q) ?>" class="text-xs text-slate-400 hover:text-white" title="Fechar">&times; Fechar</a>
                </div>
                <div class="card-body grid grid-cols-1 gap-4 text-sm">
                    <div>
                        <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">Grau Ritualístico</span>
                        <div class="font-medium text-white mt-1"><?= $badgeGrau((string) ($detalhe['grau'] ?? '')) ?></div>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">Telefone</span>
                        <div class="font-medium text-white mt-0.5"><?= htmlspecialchars((string) ($detalhe['telefone'] ?? '-')) ?></div>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">E-mail</span>
                        <div class="font-medium text-white mt-0.5 break-all"><?= htmlspecialchars((string) ($detalhe['email'] ?? '-')) ?></div>
                    </div>
                    <?php $dn = $fmtData($detalhe['data_nascimento_civil'] ?? null); ?>
                    <div>
                        <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">Data de Nascimento</span>
                        <div class="font-medium text-white mt-0.5"><?= htmlspecialchars((string) ($dn ?: '-')) ?></div>
                    </div>
                </div>
            </div>

            <!-- Linked family members -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Familiares Vinculados</h2></div>
                <div class="card-body space-y-3">
                    <?php if ($familiaresDetalhe === []): ?>
                        <div class="text-sm text-slate-400">Nenhum familiar cadastrado.</div>
                    <?php else: ?>
                        <?php foreach ($familiaresDetalhe as $f): ?>
                            <div class="list-item-condensed">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-semibold text-white"><?= htmlspecialchars((string) ($f['nome_completo'] ?? '')) ?></div>
                                    <span class="text-xs text-slate-400 capitalize"><?= htmlspecialchars((string) ($f['parentesco'] ?? '')) ?></span>
                                </div>
                                <div class="text-sm text-slate-400 mt-1">
                                    <?php $fdn = $fmtData($f['data_nascimento'] ?? null); ?>
                                    <?php if ($fdn): ?>Nasc.: <?= htmlspecialchars((string) $fdn) ?><?php endif; ?>
                                    <?php if (!empty($f['falecido'])): ?> · Falecido<?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Lodge Summary & Statistics Mural -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title text-white font-cinzel tracking-wider">Mural Fraterno</h2>
                </div>
                <div class="card-body space-y-6 text-sm">
                    <!-- Lodge statistics -->
                    <div>
                        <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">Resumo do Quadro</span>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-center">
                            <div class="p-3 bg-white/5 rounded-xl border border-white/5">
                                <div class="text-2xl font-bold text-erp-gold"><?= (int) ($estatisticasLoja['total_ativos'] ?? 0) ?></div>
                                <div class="text-[10px] text-slate-400 uppercase font-semibold">Ativos</div>
                            </div>
                            <div class="p-3 bg-white/5 rounded-xl border border-white/5 flex flex-col justify-center">
                                <div class="text-xs text-slate-300 font-semibold">Graus Ativos</div>
                                <div class="text-[10px] text-slate-400 mt-1"><?= count($estatisticasLoja['graus'] ?? []) ?> registrados</div>
                            </div>
                        </div>
                    </div>

                    <!-- Degrees breakdown -->
                    <?php if (!empty($estatisticasLoja['graus'])): ?>
                        <div>
                            <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">Distribuição por Graus</span>
                            <div class="mt-2 space-y-2">
                                <?php foreach ($estatisticasLoja['graus'] as $gRow): ?>
                                    <div class="flex items-center justify-between p-2 bg-white/[0.02] rounded-lg border border-white/5">
                                        <span class="text-xs text-slate-300 font-cinzel"><?= htmlspecialchars((string) $gRow['grau']) ?></span>
                                        <span class="font-bold text-erp-gold"><?= (int) $gRow['qtd'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Month Birthdays -->
                    <div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-400 text-[10px] uppercase tracking-wider font-semibold">Aniversariantes do Mês</span>
                            <span class="text-xs">🎂</span>
                        </div>
                        
                        <?php if ($aniversariantesMes === []): ?>
                            <div class="text-xs text-slate-400 mt-3 text-center">
                                Nenhum aniversariante neste mês.
                            </div>
                        <?php else: ?>
                            <div class="mt-3 space-y-3 max-h-[250px] overflow-y-auto pr-1">
                                <?php foreach ($aniversariantesMes as $n): ?>
                                    <div class="flex items-center justify-between p-2 bg-white/[0.02] hover:bg-white/5 rounded-lg border border-white/5">
                                        <div>
                                            <div class="font-semibold text-white text-xs"><?= htmlspecialchars((string) ($n['nome_historico'] ?? $n['nome'] ?? '')) ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars((string) ($n['grau'] ?? '')) ?></div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-bold text-erp-gold">Dia <?= (int) ($n['dia'] ?? 0) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>
