<?php
declare(strict_types=1);

if (!isset($obreiroTesouraria) || !$obreiroTesouraria) {
    $isFallbackAdmin = !empty($mensagens_contextuais['financeiro']) && empty($dados_financeiro['obrigacoes']);
    if (!$isFallbackAdmin) {
        http_response_code(401);
        echo 'Acesso não autorizado.';
        exit;
    }
}

$abaAtiva = $aba_ativa ?? 'financeiro';
$abas = $abas_disponiveis ?? ['financeiro', 'cadastro', 'familia', 'agenda_trabalhos', 'presencas_eventos', 'alertas_recados'];
$rotulosAbas = [
    'financeiro' => 'Financeiro',
    'cadastro' => 'Cadastro',
    'familia' => 'Família',
    'agenda_trabalhos' => 'Agenda/Trabalhos',
    'presencas_eventos' => 'Presenças/Eventos',
    'alertas_recados' => 'Alertas/Recados',
];

$resumoObreiro = $resumoObreiro ?? [];
$obrigacoesObreiro = $obrigacoesObreiro ?? [];
$dados_cadastro = $dados_cadastro ?? [];
$dados_familia = $dados_familia ?? [];
$dados_agenda_trabalhos = $dados_agenda_trabalhos ?? ['sessoes_futuras' => [], 'trabalhos' => []];
$dados_presencas_eventos = $dados_presencas_eventos ?? ['confirmacoes' => []];
$dados_alertas_recados = $dados_alertas_recados ?? ['alertas' => []];
$mensagens_contextuais = $mensagens_contextuais ?? [];
$estados_vazios = $estados_vazios ?? [];
$acessos_hub = $acessos_hub ?? ['dashboard' => true, 'obreiros' => false, 'secretaria' => false, 'chancelaria' => false, 'tesouraria_manage' => false];

$configuracaoFinanceira = (new \App\Models\ConfiguracaoLoja())->obter();
$formatCurrency = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static fn (?string $date): string => $date ? (new DateTimeImmutable($date))->format('d/m/Y') : '-';
$nomeObreiro = (string) ($obreiroTesouraria['nome_historico'] ?? $obreiroTesouraria['nome'] ?? 'Irmão');

$pixTipo = (string) ($configuracaoFinanceira['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoFinanceira['pix_chave_valor'] ?? '');
$pixBeneficiario = (string) ($configuracaoFinanceira['pix_beneficiario'] ?? '');

$totalAbertoResumo = (float) ($resumoObreiro['saldo_em_aberto'] ?? 0);
$totalAtrasadoResumo = (float) ($resumoObreiro['saldo_em_atraso'] ?? 0);
$parcelasAtrasadasResumo = (int) ($resumoObreiro['parcelas_atrasadas'] ?? 0);
$proximoVencimento = (string) ($resumoObreiro['proximo_vencimento'] ?? '');

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Minha Área';
$appShellDescription = 'Hub pessoal do obreiro';
$appShellActiveHref = '/minhas-obrigacoes';
$appShellActions = [['label' => 'Voltar ao Painel', 'href' => '/dashboard']];
require __DIR__ . '/partials/erp_shell_open.php';
?>

<div class="card mb-6">
    <div class="card-body">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2">
            <?php foreach ($abas as $aba): ?>
                <?php $isAtiva = $aba === $abaAtiva; ?>
                <a href="/financeiro/minhas-obrigacoes?<?= htmlspecialchars(http_build_query(['aba' => $aba])) ?>"
                   class="px-3 py-2 text-sm text-center rounded-lg border <?= $isAtiva ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-700' ?>">
                    <?= htmlspecialchars($rotulosAbas[$aba] ?? ucfirst($aba)) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($mensagens_contextuais[$abaAtiva])): ?>
            <div class="mt-3 alert alert-info"><?= htmlspecialchars((string) $mensagens_contextuais[$abaAtiva]) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($abaAtiva === 'financeiro'): ?>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="metric-card"><div class="metric-label">Obreiro</div><div class="metric-value"><?= htmlspecialchars($nomeObreiro) ?></div></div>
        <div class="metric-card"><div class="metric-label">Saldo em aberto</div><div class="metric-value"><?= $formatCurrency($totalAbertoResumo) ?></div></div>
        <div class="metric-card"><div class="metric-label">Saldo em atraso</div><div class="metric-value"><?= $formatCurrency($totalAtrasadoResumo) ?></div></div>
        <div class="metric-card"><div class="metric-label">Parcelas atrasadas</div><div class="metric-value"><?= (int) $parcelasAtrasadasResumo ?></div></div>
    </div>
    <div class="card mt-6">
        <div class="card-header"><h2 class="card-title">Contribuição via PIX</h2></div>
        <div class="card-body text-sm">
            <div>Chave <?= htmlspecialchars($pixTipo) ?>: <strong class="font-mono"><?= htmlspecialchars($pixValor !== '' ? $pixValor : 'Não informada') ?></strong></div>
            <div>Beneficiário: <?= htmlspecialchars($pixBeneficiario) ?></div>
            <div>Próximo vencimento: <?= htmlspecialchars($proximoVencimento !== '' ? $formatDate($proximoVencimento) : '-') ?></div>
            <?php if ($pixValor !== ''): ?>
                <button type="button" class="btn btn-secondary mt-3" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($pixValor)) ?>')">Copiar chave PIX</button>
            <?php endif; ?>
            <?php if (!empty($acessos_hub['tesouraria_manage'])): ?>
                <a class="btn btn-secondary mt-3 ml-2" href="/tesouraria/obrigacoes">Abrir obrigações detalhadas</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header"><h2 class="card-title"><?= htmlspecialchars($rotulosAbas[$abaAtiva] ?? 'Área') ?></h2></div>
        <div class="card-body text-sm space-y-2">
            <?php if ($abaAtiva === 'cadastro'): ?>
                <div class="mb-2">
                    <a class="btn btn-secondary" href="/meu-cadastro">Atualizar cadastro</a>
                </div>
                <?php foreach ($dados_cadastro as $campo => $valor): if ($campo === 'pendencias') continue; ?>
                    <div><strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $campo))) ?>:</strong> <?= htmlspecialchars((string) ($valor !== '' ? $valor : '-')) ?></div>
                <?php endforeach; ?>
                <?php if (!empty($dados_cadastro['pendencias'])): ?><div class="alert alert-warning">Pendências: <?= htmlspecialchars(implode(', ', $dados_cadastro['pendencias'])) ?></div><?php endif; ?>
            <?php elseif ($abaAtiva === 'familia'): ?>
                <div class="mb-2">
                    <a class="btn btn-secondary" href="/meu-cadastro">Atualizar dados familiares</a>
                </div>
                <div><strong>Estado civil:</strong> <?= htmlspecialchars((string) (($dados_familia['estado_civil'] ?? '') ?: '-')) ?></div>
                <div><strong>Cônjuge:</strong> <?= htmlspecialchars((string) (($dados_familia['conjuge'] ?? '') ?: '-')) ?></div>
                <div><strong>Filhos:</strong> <?= htmlspecialchars((string) (($dados_familia['filhos'] ?? '') ?: '-')) ?></div>
            <?php elseif ($abaAtiva === 'agenda_trabalhos'): ?>
                <div class="mb-2">
                    <?php if (!empty($acessos_hub['secretaria'])): ?>
                        <a class="btn btn-secondary" href="/secretaria/sessoes">Ver agenda de sessões</a>
                        <a class="btn btn-secondary ml-2" href="/secretaria">Registrar trabalho</a>
                    <?php else: ?>
                        <a class="btn btn-secondary" href="/dashboard">Abrir painel</a>
                    <?php endif; ?>
                </div>
                <?php foreach (($dados_agenda_trabalhos['sessoes_futuras'] ?? []) as $sessao): ?><div><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?> - <?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '-')) ?></div><?php endforeach; ?>
                <?php foreach (($dados_agenda_trabalhos['trabalhos'] ?? []) as $trabalho): ?><div><?= htmlspecialchars((string) ($trabalho['titulo'] ?? 'Trabalho')) ?> - <?= htmlspecialchars((string) ($trabalho['status_envio_potencia'] ?? '-')) ?></div><?php endforeach; ?>
            <?php elseif ($abaAtiva === 'presencas_eventos'): ?>
                <div class="mb-2">
                    <?php if (!empty($acessos_hub['chancelaria'])): ?><a class="btn btn-secondary" href="/chanceler/sessao">Atualizar presença</a><?php endif; ?>
                    <?php if (!empty($acessos_hub['secretaria'])): ?><a class="btn btn-secondary ml-2" href="/secretaria/votacao">Ver eventos e votações</a><?php endif; ?>
                    <?php if (empty($acessos_hub['chancelaria']) && empty($acessos_hub['secretaria'])): ?><a class="btn btn-secondary" href="/dashboard">Abrir painel</a><?php endif; ?>
                </div>
                <?php foreach (($dados_presencas_eventos['confirmacoes'] ?? []) as $c): ?><div><?= htmlspecialchars((string) ($c['sessao_titulo'] ?? 'Sessão')) ?> - <?= htmlspecialchars((string) ($c['data_hora_inicio'] ?? '-')) ?></div><?php endforeach; ?>
            <?php elseif ($abaAtiva === 'alertas_recados'): ?>
                <div class="mb-2">
                    <?php if (!empty($acessos_hub['tesouraria_manage'])): ?><a class="btn btn-secondary" href="/tesouraria/obrigacoes">Resolver pendências financeiras</a><?php endif; ?>
                    <?php if (!empty($acessos_hub['dashboard'])): ?><a class="btn btn-secondary ml-2" href="/dashboard">Abrir painel geral</a><?php endif; ?>
                </div>
                <?php foreach (($dados_alertas_recados['alertas'] ?? []) as $alerta): ?><div class="alert alert-warning"><?= htmlspecialchars((string) $alerta) ?></div><?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($estados_vazios[$abaAtiva])): ?><div class="text-gray-500">Sem dados para esta aba.</div><?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>
