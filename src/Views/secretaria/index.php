<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretaria - Gestor da Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalto: '#183153',
                        areia: '#f4efe6',
                        cobre: '#9c6b30',
                        tinta: '#1f2937'
                    },
                    fontFamily: {
                        display: ['"Merriweather"', 'serif'],
                        sans: ['"Inter"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@700&display=swap" rel="stylesheet">
</head>
<body class="bg-areia text-tinta font-sans min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-8">
            <div>
                <p class="text-sm uppercase tracking-[0.25em] text-cobre">Secretaria</p>
                <h1 class="font-display text-3xl text-cobalto">Centro operacional do Secretario</h1>
                <p class="text-sm text-slate-600 mt-2">Sessões, publicações, trabalhos da ordem do dia e gestão cadastral dos membros em um fluxo único de secretaria.</p>
            </div>
            <div class="flex gap-3">
                <a href="/obreiros" class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium">Cadastros dos membros</a>
                <a href="/secretaria/votacao" class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium">Painel de votacao</a>
                <a href="/secretaria/relatorio-anual" class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium">Relatorio anual</a>
                <a href="/dashboard" class="px-4 py-2 rounded-lg bg-cobalto text-white text-sm font-medium">Voltar ao painel</a>
            </div>
        </div>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <div class="grid gap-4 md:grid-cols-5 mb-8">
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-500">Obreiros ativos</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) $resumo['obreiros_ativos'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-500">Sessoes futuras</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) $resumo['sessoes_futuras'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-500">Trabalhos pendentes</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) $resumo['trabalhos_pendentes'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-500">Publicacoes em rascunho</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) $resumo['publicacoes_rascunho'] ?></div>
            </div>
            <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                <div class="text-sm text-slate-500">Balaustres aptos</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) $resumo['balaustres_aptos'] ?></div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <section class="space-y-6">
                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-display text-xl text-cobalto">Proxima sessao e agenda</h2>
                            <p class="text-sm text-slate-500">Base operacional das sessoes sob responsabilidade da Secretaria.</p>
                        </div>
                    </div>

                    <?php if ($proximaSessao): ?>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 mb-4">
                            <div class="text-sm text-slate-500">Proxima sessao oficial</div>
                            <div class="mt-1 font-semibold text-cobalto"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></div>
                            <div class="text-sm text-slate-600 mt-1"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/secretaria/sessoes/salvar" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Titulo da sessao</label>
                            <input type="text" name="titulo" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Data e hora de inicio</label>
                            <input type="datetime-local" name="data_hora_inicio" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo de sessao</label>
                            <select name="tipo_sessao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="Economica">Economica</option>
                                <option value="Magna">Magna</option>
                                <option value="Instrucao">Instrucao</option>
                                <option value="Administrativa">Administrativa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Grau da sessao</label>
                            <select name="grau_sessao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="Aprendiz">Aprendiz</option>
                                <option value="Companheiro">Companheiro</option>
                                <option value="Mestre">Mestre</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Resumo publico</label>
                            <textarea name="resumo_publico" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Observacao interna</label>
                            <textarea name="observacao_interna" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <label class="md:col-span-2 inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="agape_ativo" value="1">
                            Sessao com agape
                        </label>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-lg bg-cobalto px-4 py-2 text-white font-medium">Cadastrar sessao</button>
                        </div>
                    </form>

                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-slate-500">
                                <tr>
                                    <th class="py-2">Sessao</th>
                                    <th class="py-2">Data</th>
                                    <th class="py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessoes as $sessao): ?>
                                    <tr class="border-t border-slate-100">
                                        <td class="py-2"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></td>
                                        <td class="py-2"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></td>
                                        <td class="py-2"><?= htmlspecialchars((string) ($sessao['status'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-display text-xl text-cobalto">Trabalhos e pecas de arquitetura</h2>
                    <p class="text-sm text-slate-500 mb-4">Registro dos trabalhos apresentados em ordem do dia, com controle do envio em PDF para a Potencia e acervo futuro da Loja.</p>
                    <form method="POST" action="/secretaria/trabalhos/salvar" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Sessao</label>
                            <select name="sessao_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecione</option>
                                <?php foreach ($sessoes as $sessao): ?>
                                    <option value="<?= (int) $sessao['id'] ?>"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo</label>
                            <select name="tipo_trabalho" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="peca_arquitetura">Peca de arquitetura</option>
                                <option value="trabalho">Trabalho</option>
                                <option value="prancha">Prancha</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Titulo</label>
                            <input type="text" name="titulo" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Autor do quadro</label>
                            <select name="autor_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecionar membro</option>
                                <?php foreach ($obreiros as $obreiro): ?>
                                    <option value="<?= htmlspecialchars($obreiro['id']) ?>"><?= htmlspecialchars($obreiro['nome_historico'] ?: $obreiro['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Autor livre</label>
                            <input type="text" name="autor_nome_livre" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Local do PDF</label>
                            <input type="text" name="arquivo_pdf_path" placeholder="Ex.: /documentos/trabalhos/arquivo.pdf" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Status do envio</label>
                            <select name="status_envio_potencia" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="pendente">Pendente</option>
                                <option value="enviado">Enviado</option>
                                <option value="dispensado">Dispensado</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Observacao</label>
                            <textarea name="observacao" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-lg bg-cobalto px-4 py-2 text-white font-medium">Registrar trabalho</button>
                        </div>
                    </form>

                    <div class="mt-6 space-y-3">
                        <?php foreach ($trabalhos as $trabalho): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="font-medium text-cobalto"><?= htmlspecialchars($trabalho['titulo']) ?></div>
                                <div class="text-sm text-slate-500 mt-1">
                                    <?= htmlspecialchars($trabalho['sessao_titulo'] ?: (string) ($trabalho['data_hora_inicio'] ?? '')) ?>
                                    · <?= htmlspecialchars($trabalho['autor_nome'] ?: ($trabalho['autor_nome_livre'] ?? 'Autor nao informado')) ?>
                                </div>
                                <div class="text-xs text-slate-500 mt-1">Status Potencia: <?= htmlspecialchars((string) $trabalho['status_envio_potencia']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-display text-xl text-cobalto">Balaustre e votacao</h2>
                    <p class="text-sm text-slate-500 mb-4">
                        O Secretario prepara o balaustre e deixa apto para votacao. A abertura e o encerramento da votacao ficam sob atribuicao do Veneravel Mestre.
                    </p>

                    <?php if ($podeOperarSecretaria): ?>
                    <form method="POST" action="/secretaria/balaustres/salvar" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Sessao</label>
                            <select name="sessao_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecione</option>
                                <?php foreach ($sessoes as $sessao): ?>
                                    <option value="<?= (int) $sessao['id'] ?>"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Numero do balaustre</label>
                            <input type="text" name="numero_balaustre" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Texto final revisado</label>
                            <textarea name="texto_final" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Palavra a bem da ordem (visitantes)</h3>
                            <p class="text-xs text-slate-500 mb-3">
                                Use as lojas frequentes como apoio de preenchimento. Registre as apresentacoes e agradecimentos dos visitantes.
                            </p>
                            <div class="mb-3">
                                <label class="block text-xs font-medium mb-1">Lojas frequentes (uma por linha ou separadas por virgula)</label>
                                <textarea name="lojas_visitantes_frequentes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= htmlspecialchars(implode("\n", $lojasVisitantesFrequentes ?? [])) ?></textarea>
                            </div>
                            <datalist id="lojas-frequentes-sugestoes">
                                <?php foreach (($lojasVisitantesFrequentes ?? []) as $lojaSugestao): ?>
                                    <option value="<?= htmlspecialchars((string) $lojaSugestao) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <div class="space-y-2">
                                <?php for ($linhaVisitante = 0; $linhaVisitante < 4; $linhaVisitante++): ?>
                                    <div class="grid gap-2 md:grid-cols-6">
                                        <input type="text" name="palavra_visitante_nome[]" placeholder="Nome do visitante" class="rounded-lg border border-slate-300 px-2 py-2 text-sm md:col-span-2">
                                        <input type="text" name="palavra_visitante_loja[]" placeholder="Loja de origem" list="lojas-frequentes-sugestoes" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_oriente[]" placeholder="Oriente" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_potencia[]" placeholder="Potencia" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_grau[]" placeholder="Grau" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_dia_reuniao[]" placeholder="Dia da reuniao" class="rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        <input type="text" name="palavra_visitante_fala[]" placeholder="Resumo da fala/impressao" class="rounded-lg border border-slate-300 px-2 py-2 text-sm md:col-span-6">
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Nominata de cargos da sessao</h3>
                            <p class="text-xs text-slate-500 mb-3">
                                O sistema assume <strong>regular</strong> quando o ocupante bate com o titular oficial da gestao. Se divergir, salva automaticamente como <strong>ad hoc</strong>.
                            </p>
                            <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                <?php foreach (($cargosSessaoBase ?? []) as $cargoSessao): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <input type="hidden" name="cargo_sessao_codigo[]" value="<?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?>">
                                        <input type="hidden" name="cargo_sessao_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['label'] ?? '')) ?>">
                                        <div class="md:col-span-3 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-700">
                                            <div class="font-semibold"><?= htmlspecialchars((string) ($cargoSessao['label'] ?? 'Cargo')) ?></div>
                                            <div class="text-slate-500"><?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?></div>
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="text" name="cargo_sessao_titular_oficial[]" value="<?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '')) ?>" placeholder="Titular oficial" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-4">
                                            <input type="text" name="cargo_sessao_ocupante_nome[]" placeholder="Quem ocupou na sessao" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="cargo_sessao_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Saco de propostas: visitas a outras Lojas</h3>
                            <p class="text-xs text-slate-500 mb-3">
                                Registre aqui quando algum membro do quadro da Loja informar visita realizada a outra Loja.
                            </p>
                            <div class="space-y-2">
                                <?php for ($linhaVisita = 0; $linhaVisita < 4; $linhaVisita++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-4">
                                            <select name="visita_externa_obreiro_id[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                                <option value="">Selecione o membro do quadro</option>
                                                <?php foreach ($obreiros as $obreiro): ?>
                                                    <option value="<?= htmlspecialchars((string) $obreiro['id']) ?>"><?= htmlspecialchars($obreiro['nome_historico'] ?: $obreiro['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="visita_externa_obreiro_nome[]" value="">
                                        </div>
                                        <div class="md:col-span-4">
                                            <input type="text" name="visita_externa_loja[]" placeholder="Loja visitada" list="lojas-frequentes-sugestoes" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="visita_externa_oriente[]" placeholder="Oriente" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="visita_externa_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Congressos realizados</h3>
                            <div class="space-y-2">
                                <?php for ($linhaCongresso = 0; $linhaCongresso < 3; $linhaCongresso++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-5">
                                            <input type="text" name="congresso_titulo[]" placeholder="Titulo do congresso" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="text" name="congresso_promotor[]" placeholder="Promotor/organizacao" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="date" name="congresso_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="congresso_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-2">Palestras realizadas</h3>
                            <div class="space-y-2">
                                <?php for ($linhaPalestra = 0; $linhaPalestra < 4; $linhaPalestra++): ?>
                                    <div class="grid gap-2 md:grid-cols-12">
                                        <div class="md:col-span-5">
                                            <input type="text" name="palestra_titulo[]" placeholder="Tema ou titulo da palestra" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-3">
                                            <input type="text" name="palestra_palestrante[]" placeholder="Palestrante" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="date" name="palestra_data[]" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                        <div class="md:col-span-2">
                                            <input type="text" name="palestra_observacao[]" placeholder="Obs." class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Observacoes livres da secretaria</label>
                            <textarea name="observacoes_secretaria" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Dados capturados adicionais (JSON opcional)</label>
                            <textarea name="dados_capturados" rows="2" placeholder='{"outros":"campos complementares"}' class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-lg bg-cobalto px-4 py-2 text-white font-medium">Salvar balaustre</button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div class="mt-6 space-y-3">
                        <?php foreach ($balaustres as $balaustre): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="font-medium text-cobalto">
                                    <?= htmlspecialchars($balaustre['numero_balaustre'] ?: 'Sem numero') ?>
                                    · <?= htmlspecialchars($balaustre['sessao_titulo'] ?: (string) ($balaustre['data_hora_inicio'] ?? '')) ?>
                                </div>
                                <div class="text-sm text-slate-500 mt-1">Status: <?= htmlspecialchars((string) ($balaustre['status'] ?? '')) ?></div>
                                <div class="text-xs text-slate-500 mt-1">
                                    Palavra a bem da ordem: <?= (int) ($balaustre['resumo_palavra_bem_ordem'] ?? 0) ?> registro(s)
                                    · Cargos ad hoc: <?= (int) ($balaustre['resumo_cargos_ad_hoc'] ?? 0) ?>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <?php if ($podeOperarSecretaria && (($balaustre['status'] ?? '') !== 'em_votacao')): ?>
                                    <form method="POST" action="/secretaria/balaustres/apto">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <button type="submit" class="rounded-md border border-cobalto px-3 py-1.5 text-sm text-cobalto">Deixar apto para votacao</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($podeAbrirVotacao && (($balaustre['status'] ?? '') === 'apto_votacao')): ?>
                                    <form method="POST" action="/secretaria/balaustres/abrir-votacao">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <button type="submit" class="rounded-md bg-cobalto px-3 py-1.5 text-sm text-white">Abrir votacao (Veneravel Mestre)</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (($balaustre['status'] ?? '') === 'em_votacao' && (($elegibilidadeVoto[(int) $balaustre['id']] ?? false) === true)): ?>
                                    <form method="POST" action="/secretaria/balaustres/votar" class="flex flex-wrap gap-2 items-center">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <select name="voto" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                            <option value="aprovar">aprovar</option>
                                            <option value="pedir_correcao">pedir correcao</option>
                                            <option value="rejeitar">rejeitar</option>
                                        </select>
                                        <input type="text" name="justificativa" placeholder="Justificativa (opcional)" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                        <button type="submit" class="rounded-md border border-cobalto px-3 py-1.5 text-sm text-cobalto">Votar</button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($podeAbrirVotacao && (($balaustre['status'] ?? '') === 'em_votacao')): ?>
                                    <form method="POST" action="/secretaria/balaustres/encerrar-votacao">
                                        <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                        <button type="submit" class="rounded-md bg-slate-700 px-3 py-1.5 text-sm text-white">Encerrar votacao (Veneravel Mestre)</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-display text-xl text-cobalto">Publicacoes oficiais</h2>
                    <p class="text-sm text-slate-500 mb-4">Informativos das Potencias, agenda, proxima sessao e convites externos sob rastreio da Secretaria.</p>
                    <form method="POST" action="/secretaria/publicacoes/salvar" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Tipo de publicacao</label>
                            <select name="tipo_publicacao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="agenda_oficial">Agenda oficial</option>
                                <option value="proxima_sessao">Proxima sessao</option>
                                <option value="informativo_potencia">Informativo da Potencia</option>
                                <option value="convite_externo">Convite externo</option>
                                <option value="comunicado">Comunicado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Titulo</label>
                            <input type="text" name="titulo" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Origem</label>
                            <input type="text" name="origem" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Canal</label>
                            <select name="canal_destino" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="grupo">Grupo</option>
                                <option value="web">Web</option>
                                <option value="interno">Interno</option>
                                <option value="misto">Misto</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Status</label>
                            <select name="status_publicacao" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="rascunho">Rascunho</option>
                                <option value="publicado">Publicado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Conteudo</label>
                            <textarea name="conteudo" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Observacao</label>
                            <textarea name="observacao" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <button type="submit" class="rounded-lg bg-cobalto px-4 py-2 text-white font-medium">Registrar publicacao</button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-display text-xl text-cobalto">Ultimos registros</h2>
                    <div class="space-y-3 mt-4">
                        <?php foreach ($publicacoes as $publicacao): ?>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="font-medium text-cobalto"><?= htmlspecialchars($publicacao['titulo']) ?></div>
                                <div class="text-sm text-slate-500 mt-1"><?= htmlspecialchars((string) $publicacao['tipo_publicacao']) ?> · <?= htmlspecialchars((string) $publicacao['status_publicacao']) ?></div>
                                <?php if (!empty($publicacao['origem'])): ?>
                                    <div class="text-xs text-slate-500 mt-1">Origem: <?= htmlspecialchars((string) $publicacao['origem']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
                    <h2 class="font-display text-xl text-cobalto">Responsabilidades consolidadas</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600 list-disc pl-5">
                        <li>Cadastro e atualizacao dos membros, inclusive grau e acesso a plataformas externas.</li>
                        <li>Operacao central das sessoes, publicacoes e fluxo documental da Loja.</li>
                        <li>Registro dos trabalhos da ordem do dia e preservacao do acervo em PDF.</li>
                        <li>Preparacao dos insumos do balaustre em ambiente web, com fechamento posterior.</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
