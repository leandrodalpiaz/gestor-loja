<?php
/**
 * Componente: Card Obreiro
 * @param array $obreiro Dados do obreiro.
 * @param bool $podeGerenciarObreiros Flag de permissão.
 * @param bool $podeGerarConvitesAcesso Flag de permissão.
 * @param string $returnToAtual URL de retorno para ações.
 */

use App\Models\Cargo;

$obreiro = $obreiro ?? [];
$podeGerenciarObreiros = (bool) ($podeGerenciarObreiros ?? false);
$podeGerarConvitesAcesso = (bool) ($podeGerarConvitesAcesso ?? false);
$returnToAtual = (string) ($returnToAtual ?? '/obreiros');

$nomeExibicao = !empty($obreiro['nome_historico']) ? $obreiro['nome_historico'] : ($obreiro['nome'] ?? 'Desconhecido');
$situacao = $obreiro['situacao_quadro'] ?? 'Regular';
$cargosAtuais = $obreiro['cargos_codigos'] ?? [];
$alertas = $obreiro['alertas_cadastro'] ?? [];
$cim = $obreiro['cim'] ?? '-';
$grau = $obreiro['grau'] ?? '-';
$obreiroId = (string) ($obreiro['id'] ?? '');

?>
<div class="card mb-4">
    <div class="card-body">
        <div class="flex justify-between items-start gap-2">
            <div>
                <h3 class="font-bold text-lg text-white leading-tight"><?= htmlspecialchars($nomeExibicao) ?></h3>
                <p class="text-sm text-slate-400 mt-1">CIM: <?= htmlspecialchars($cim) ?> | Grau: <?= htmlspecialchars($grau) ?></p>
            </div>
            <div>
                <?php 
                $label = $situacao; 
                $type = 'info'; 
                require __DIR__ . '/badge-status.php'; 
                ?>
            </div>
        </div>
        
        <?php if (!empty($alertas)): ?>
            <div class="mt-3">
                <?php 
                $label = count($alertas) . ' Alerta(s) de Cadastro'; 
                $type = 'warning'; 
                require __DIR__ . '/badge-status.php'; 
                ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 pt-4 border-t border-white/10">
            <h4 class="text-xs uppercase tracking-wider font-semibold text-slate-400 mb-2">Cargos em Exercício</h4>
            <div class="flex flex-wrap gap-1.5">
                <?php if (empty($cargosAtuais)): ?>
                    <span class="text-xs text-slate-400 italic">Nenhum cargo atual.</span>
                <?php else: ?>
                    <?php foreach ($cargosAtuais as $codigo): ?>
                        <?php 
                        $label = Cargo::rotuloOficial($codigo); 
                        $type = 'neutral'; 
                        require __DIR__ . '/badge-status.php'; 
                        ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($podeGerenciarObreiros || $podeGerarConvitesAcesso): ?>
        <div class="mt-4 pt-4 border-t border-white/10 flex flex-wrap gap-2">
            <?php if ($podeGerenciarObreiros): ?>
                <a href="/obreiros/editar?id=<?= $obreiroId ?>" class="btn btn-primary text-sm flex-1 text-center">Editar</a>
                <form method="post" action="/obreiros/inativar" onsubmit="return confirm('Inativar este obreiro?');" class="flex-1">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($obreiroId) ?>">
                    <button type="submit" class="btn btn-secondary text-sm w-full">Inativar</button>
                </form>
                <form method="post" action="/obreiros/excluir" onsubmit="return confirm('Excluir este obreiro da gestão? Esta ação pode ser irreversível.');" class="w-full mt-2">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($obreiroId) ?>">
                    <button type="submit" class="btn border border-danger/30 text-danger hover:bg-danger/10 text-sm w-full">Excluir Registro</button>
                </form>
            <?php endif; ?>
            <?php if ($podeGerarConvitesAcesso): ?>
                <form method="post" action="/admin/convites/gerar" onsubmit="return confirm('Gerar convite de acesso para este obreiro?');" class="w-full mt-2">
                    <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars($obreiroId) ?>">
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToAtual) ?>">
                    <button type="submit" class="btn btn-secondary text-sm w-full">Gerar Convite de Acesso</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
