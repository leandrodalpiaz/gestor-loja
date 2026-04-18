<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
$nomeAprendiz = (string) ($aprendiz['nome_historico'] ?? $aprendiz['nome'] ?? 'Aprendiz');
$etapaAtualOrdem = (int) ($etapaAtual['etapa_ordem'] ?? 1);
$etapaAtualTitulo = (string) ($etapaAtual['titulo_etapa'] ?? '');
$etapaAtualStatus = (string) ($etapaAtual['status'] ?? 'nao_iniciado');
$percentual = (int) ($resumoTrilha['percentual_conclusao'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ardosia: '#162131',
                        dourado: '#bc9a43',
                        pinho: '#315348',
                        pergaminho: '#f6f0e5'
                    },
                    fontFamily: {
                        display: ['"Cormorant Garamond"', 'serif'],
                        sans: ['"Inter"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-[11px] {
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
<body class="erp-readable min-h-screen bg-[radial-gradient(circle_at_top,#faf6ed_0%,#edf1f5_46%,#e7eaef_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="rounded-3xl border border-white/50 bg-[radial-gradient(circle_at_top_left,#d7bf88,transparent_28%),linear-gradient(135deg,#162131,#23384e_55%,#315348)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.22em] text-amber-200"><?= $somenteProprio ? 'Autoacompanhamento' : 'Acompanhamento formativo' ?></p>
            <h1 class="mt-2 font-display text-4xl font-bold"><?= htmlspecialchars($nomeAprendiz) ?></h1>
            <p class="mt-2 text-sm text-slate-200">
                <?= $somenteProprio ? 'Acompanhe sua trilha, sua leitura orientada e a situacao do certificado formativo.' : 'Linha do tempo individual do Aprendiz com trilha, leitura sugerida, devolutivas e pedido formal de certificado.' ?>
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="<?= $somenteProprio ? '/dashboard' : '/primeiro-vigilante' ?>" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Voltar</a>
                <?php if (!$somenteProprio): ?>
                    <a href="/obreiros" class="rounded-md bg-amber-300 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-200">Lista de obreiros</a>
                    <a href="/biblioteca" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Biblioteca e classificacao</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>
        <?php if (!empty($avisoInfra)): ?>
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800"><?= htmlspecialchars((string) $avisoInfra) ?></div>
        <?php endif; ?>

        <section class="mt-6 grid gap-4 md:grid-cols-4">
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-700">Etapa atual</div>
                <div class="mt-2 text-2xl font-bold text-ardosia"><?= $etapaAtualOrdem ?></div>
                <div class="mt-1 text-xs text-slate-700"><?= htmlspecialchars($etapaAtualTitulo) ?></div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-700">Status atual</div>
                <div class="mt-2 text-lg font-semibold text-ardosia"><?= htmlspecialchars($etapaAtualStatus) ?></div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-700">Etapas concluidas</div>
                <div class="mt-2 text-2xl font-bold text-ardosia"><?= (int) ($resumoTrilha['total_concluidas'] ?? 0) ?> / <?= (int) ($resumoTrilha['total_etapas'] ?? 0) ?></div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-700">Conclusao da trilha</div>
                <div class="mt-2 text-2xl font-bold text-ardosia"><?= $percentual ?>%</div>
            </article>
        </section>

        <?php if (!$somenteProprio): ?>
            <section class="mt-6 grid gap-6 xl:grid-cols-2">
                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Atualizar etapa da trilha</h2>
                    <p class="mt-1 text-sm text-slate-700">Registre andamento, recebimento, revisao e devolutiva do Aprendiz.</p>
                    <form action="/primeiro-vigilante/trilha/atualizar" method="POST" class="mt-4 grid gap-4 md:grid-cols-2">
                        <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Etapa</label>
                            <select name="etapa_ordem" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <?php foreach ($etapas as $etapa): ?>
                                    <option value="<?= (int) ($etapa['etapa_ordem'] ?? 0) ?>" <?= (int) ($etapa['etapa_ordem'] ?? 0) === $etapaAtualOrdem ? 'selected' : '' ?>>
                                        Etapa <?= (int) ($etapa['etapa_ordem'] ?? 0) ?> - <?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <?php foreach ($statusDisponiveis as $codigo => $rotulo): ?>
                                    <option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo === $etapaAtualStatus ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Observacao do 1o Vigilante</label>
                            <textarea name="observacao_vigilante" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Registre orientacoes, devolutivas e o proximo encaminhamento."><?= htmlspecialchars((string) ($etapaAtual['observacao_vigilante'] ?? '')) ?></textarea>
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Salvar andamento da trilha</button>
                        </div>
                    </form>
                </article>

                <article id="leitura-sugerida" class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Leitura sugerida</h2>
                    <p class="mt-1 text-sm text-slate-700">Conecte o acompanhamento formativo do Aprendiz com a biblioteca da Loja.</p>
                    <form action="/primeiro-vigilante/leitura/salvar" method="POST" class="mt-4 space-y-4">
                        <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Item do acervo</label>
                            <select name="acervo_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="">Sem vincular livro especifico</option>
                                <?php foreach ($leiturasDisponiveis as $livro): ?>
                                    <option value="<?= (int) ($livro['id'] ?? 0) ?>" <?= ((int) ($acompanhamento['leitura_acervo_id'] ?? 0) === (int) ($livro['id'] ?? 0)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($livro['titulo'] ?? 'Livro') . ' - ' . (string) ($livro['autor'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Orientacao de leitura</label>
                            <textarea name="observacao_leitura" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Explique o motivo da leitura, o foco da instrucao ou o capitulo recomendado."><?= htmlspecialchars((string) ($acompanhamento['leitura_observacao'] ?? '')) ?></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Salvar leitura sugerida</button>
                        </div>
                    </form>
                </article>
            </section>
        <?php endif; ?>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="font-display text-2xl font-semibold">Linha do tempo da trilha</h2>
                        <p class="mt-1 text-sm text-slate-700">Cada etapa mostra o estado real da jornada formativa.</p>
                    </div>
                    <div class="w-40">
                        <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-pinho" style="width: <?= max(0, min(100, $percentual)) ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <?php foreach ($etapas as $etapa): ?>
                        <?php
                        $status = (string) ($etapa['status'] ?? 'nao_iniciado');
                        $ordem = (int) ($etapa['etapa_ordem'] ?? 0);
                        $ativo = $ordem === $etapaAtualOrdem;
                        $concluido = in_array($status, ['concluido', 'certificado_solicitado'], true);
                        ?>
                        <article id="etapa-<?= $ordem ?>" class="rounded-2xl border px-5 py-4 <?= $ativo ? 'border-dourado bg-amber-50/60' : ($concluido ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-white') ?>">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium <?= $ativo ? 'bg-amber-200 text-amber-900' : ($concluido ? 'bg-emerald-200 text-emerald-900' : 'bg-slate-100 text-slate-700') ?>">
                                            Etapa <?= $ordem ?>
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700"><?= htmlspecialchars($status) ?></span>
                                    </div>
                                    <h3 class="mt-2 text-lg font-semibold text-ardosia"><?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?></h3>
                                    <?php if (!empty($etapa['observacao_vigilante'])): ?>
                                        <p class="mt-2 text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) $etapa['observacao_vigilante'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!$somenteProprio && !empty($acoesRapidasPorEtapa[$ordem])): ?>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <?php foreach ($acoesRapidasPorEtapa[$ordem] as $acao): ?>
                                                <form action="/primeiro-vigilante/trilha/acao-rapida" method="POST">
                                                    <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                                                    <input type="hidden" name="etapa_ordem" value="<?= $ordem ?>">
                                                    <input type="hidden" name="status" value="<?= htmlspecialchars((string) ($acao['status'] ?? '')) ?>">
                                                    <button type="submit" class="rounded-full bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">
                                                        <?= htmlspecialchars((string) ($acao['label'] ?? 'Avancar')) ?>
                                                    </button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="grid gap-2 text-xs text-slate-700 md:text-right">
                                    <div>Disponibilizacao: <?= !empty($etapa['data_disponibilizacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_disponibilizacao']))) : '-' ?></div>
                                    <div>Entrega: <?= !empty($etapa['data_entrega']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_entrega']))) : '-' ?></div>
                                    <div>Revisao: <?= !empty($etapa['data_revisao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_revisao']))) : '-' ?></div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>

            <div class="space-y-6">
                <article id="certificado" class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Certificado formativo</h2>
                    <p class="mt-1 text-sm text-slate-700">Solicitacao formal da conclusao da docencia maconica.</p>
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-700">Status</div>
                        <div class="mt-2 text-lg font-semibold text-ardosia"><?= htmlspecialchars((string) ($acompanhamento['certificado_status'] ?? 'nao_solicitado')) ?></div>
                        <?php if (!empty($acompanhamento['certificado_solicitado_em'])): ?>
                            <div class="mt-1 text-sm text-slate-700">Solicitado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $acompanhamento['certificado_solicitado_em']))) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$somenteProprio): ?>
                        <form action="/primeiro-vigilante/certificado/solicitar" method="POST" class="mt-4 space-y-4">
                            <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Observacao da solicitacao</label>
                                <textarea name="observacao_certificado" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Registre as condicoes para conclusao, avaliacao final ou pendencias residuais."><?= htmlspecialchars((string) ($acompanhamento['certificado_observacao'] ?? '')) ?></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Solicitar certificado</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Historico formativo</h2>
                    <p class="mt-1 text-sm text-slate-700">Linha consolidada de marcos da trilha, leitura e certificado.</p>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($historicoFormativo as $evento): ?>
                            <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                <div class="text-xs uppercase tracking-wide text-slate-700"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></div>
                                <div class="mt-1 text-sm font-medium text-slate-800"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Marco formativo')) ?></div>
                                <div class="mt-1 text-xs text-slate-700">
                                    <?= !empty($evento['momento']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $evento['momento']))) : '-' ?>
                                </div>
                                <?php if (!empty($evento['descricao'])): ?>
                                    <div class="mt-2 text-sm text-slate-700"><?= htmlspecialchars((string) $evento['descricao']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($historicoFormativo === []): ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">Ainda nao ha marcos registrados para este Aprendiz.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </section>
    </div>
</body>
</html>

