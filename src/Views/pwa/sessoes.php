<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$sessao = is_array($sessao ?? null) ? $sessao : null;
$resposta = is_array($resposta ?? null) ? $resposta : null;

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$isTestSession = isset($_SESSION['usuario_id']) && (string) $_SESSION['usuario_id'] === '0';

$statusConfirmacao = (string) ($resposta['status_confirmacao'] ?? '');
$participaraAgape = (bool) ($resposta['participara_agape'] ?? false);
$observacaoAtual = trim((string) ($resposta['observacao'] ?? ''));

$statusBadge = match ($statusConfirmacao) {
    'confirmado' => $participaraAgape ? ['Confirmado (com ágape)', 'bg-emerald-600'] : ['Confirmado', 'bg-emerald-600'],
    'ausente' => ['Ausente', 'bg-rose-600'],
    default => ['Sem resposta', 'bg-slate-500'],
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessões</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Sessões</div>
                <h1 class="text-lg font-semibold">Próxima sessão</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/dashboard">Painel</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-4">
        <?php if ($mensagemSucesso): ?>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                <?= htmlspecialchars((string) $mensagemSucesso) ?>
            </div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                <?= htmlspecialchars((string) $mensagemErro) ?>
            </div>
        <?php endif; ?>

        <?php if (!$sessao): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-600">Nenhuma sessão futura publicada para sua loja.</div>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></div>
                        <div class="text-sm text-slate-600">
                            <?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?>
                            <?php if (!empty($sessao['tipo_sessao'])): ?> · <?= htmlspecialchars((string) $sessao['tipo_sessao']) ?><?php endif; ?>
                            <?php if (!empty($sessao['grau_sessao'])): ?> · <?= htmlspecialchars((string) $sessao['grau_sessao']) ?><?php endif; ?>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white <?= htmlspecialchars($statusBadge[1]) ?>">
                        <?= htmlspecialchars($statusBadge[0]) ?>
                    </span>
                </div>

                <?php if (!empty($sessao['resumo_publico'])): ?>
                    <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700 whitespace-pre-line">
                        <?= htmlspecialchars((string) $sessao['resumo_publico']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($sessao['ordem_dia'])): ?>
                    <details class="rounded-lg bg-slate-50 p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-800">Ordem do dia</summary>
                        <div class="mt-2 text-sm text-slate-700 whitespace-pre-line"><?= htmlspecialchars((string) $sessao['ordem_dia']) ?></div>
                    </details>
                <?php endif; ?>

                <form method="post" action="/pwa/sessoes/atualizar" class="space-y-3">
                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button name="acao" value="confirmar" class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            Confirmar presença
                        </button>

                        <button name="acao" value="cancelar" class="w-full rounded-lg bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-200">
                            Cancelar resposta
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button name="acao" value="confirmar_agape" class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                            Confirmar com ágape
                        </button>

                        <button name="acao" value="confirmar_sem_agape" class="w-full rounded-lg bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
                            Confirmar sem ágape
                        </button>
                    </div>

                    <div class="rounded-lg border border-slate-200 p-3 space-y-2">
                        <div class="text-sm font-semibold text-slate-800">Justificar ausência</div>
                        <textarea name="observacao" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none" placeholder="Ex.: compromisso, viagem, saúde..."><?= htmlspecialchars($observacaoAtual) ?></textarea>
                        <button name="acao" value="ausencia" class="w-full rounded-lg bg-rose-600 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-700">
                            Marcar como ausente
                        </button>
                        <?php if ($isTestSession): ?>
                            <div class="text-xs text-slate-500">Modo teste: algumas ações podem ser bloqueadas.</div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="text-xs text-slate-500">
            Dica: se você veio pelo Telegram, ele agora serve como atalho e notificação. A fonte de verdade é este painel.
        </div>
    </main>
</body>
</html>

