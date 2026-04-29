<?php
$itens = $itens ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria Administrativa</title>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
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
<body class="erp-readable min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-5xl px-4 py-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Auditoria administrativa</h1>
                <p class="mt-2 text-sm text-slate-700">Leitura consolidada de alterações críticas da administração.</p>
            </div>
            <a href="/dashboard" class="text-sm text-blue-700 hover:underline">Voltar</a>
        </div>

        <div class="space-y-3">
            <?php if ($itens !== []): ?>
                <?php foreach ($itens as $item): ?>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-700"><?= htmlspecialchars((string) ($item['origem'] ?? 'admin')) ?> · <?= htmlspecialchars((string) ($item['entidade'] ?? 'evento')) ?></div>
                                <h2 class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars((string) ($item['resumo'] ?? 'Registro administrativo')) ?></h2>
                            </div>
                            <div class="text-right text-xs text-slate-700"><?= htmlspecialchars((string) ($item['created_at'] ?? '')) ?></div>
                        </div>
                        <div class="mt-3 text-sm text-slate-700">
                            Acao: <strong><?= htmlspecialchars((string) ($item['acao'] ?? '-')) ?></strong>
                            <?php if (!empty($item['criado_por_nome'])): ?>
                                · Por <strong><?= htmlspecialchars((string) $item['criado_por_nome']) ?></strong>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-700">Nenhum registro de auditoria administrativa encontrado.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

