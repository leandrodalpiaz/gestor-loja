<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Presenca - Chancelaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.06rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[11px\] {
                font-size: 0.92rem !important;
                line-height: 1.4rem !important;
            }
            .erp-readable .text-sm {
                font-size: 1.02rem !important;
                line-height: 1.55rem !important;
            }
        }
    </style>
</head>
<body class="erp-readable bg-gray-50 text-gray-800 p-4 antialiased">
    <?php
    $defaults = [
        'data_sessao' => trim((string) ($_GET['data_sessao'] ?? '')),
        'tipo_sessao' => trim((string) ($_GET['tipo_sessao'] ?? '')),
        'grau_sessao' => trim((string) ($_GET['grau_sessao'] ?? '')),
    ];
    ?>
    <div class="max-w-2xl mx-auto">
        <div class="mb-6 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d6b672,transparent_30%),linear-gradient(135deg,#162033,#223145)] px-6 py-6 text-white shadow-xl">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Chancelaria</p>
                    <h1 class="mt-2 text-3xl font-semibold">Emitir certificado</h1>
                    <p class="mt-1 text-sm text-slate-200">Fluxo web da Chancelaria para gerar e enviar certificado de presenca com os dados oficiais da sessao.</p>
                </div>
                <a href="/chancelaria/efemerides" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Voltar para Chancelaria</a>
            </div>
        </div>

        <form method="POST" action="/chancelaria/certificado/gerar">
            <input type="hidden" id="chat_id" name="chat_id">
            <input type="hidden" id="init_data" name="init_data">

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 grid gap-3 md:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Data da sessao</p>
                        <p class="mt-2 text-sm text-slate-700"><?= htmlspecialchars($defaults['data_sessao'] !== '' ? $defaults['data_sessao'] : 'Definir no formulario') ?></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Tipo</p>
                        <p class="mt-2 text-sm text-slate-700"><?= htmlspecialchars($defaults['tipo_sessao'] !== '' ? $defaults['tipo_sessao'] : 'Definir no formulario') ?></p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Grau</p>
                        <p class="mt-2 text-sm text-slate-700"><?= htmlspecialchars($defaults['grau_sessao'] !== '' ? $defaults['grau_sessao'] : 'Definir no formulario') ?></p>
                    </article>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="nome_visitante" class="block mb-2 font-semibold">Nome do visitante</label>
                        <input type="text" id="nome_visitante" name="nome_visitante" placeholder="Ex: Joao da Silva" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div class="md:col-span-2">
                        <label for="loja_visitante" class="block mb-2 font-semibold">Loja do visitante</label>
                        <input type="text" id="loja_visitante" name="loja_visitante" placeholder="Ex: ARLS Luz e Verdade n 123" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div>
                        <label for="oriente" class="block mb-2 font-semibold">Oriente</label>
                        <input type="text" id="oriente" name="oriente" placeholder="Ex: Sao Paulo - SP" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div>
                        <label for="data_sessao" class="block mb-2 font-semibold">Data da sessao</label>
                        <input type="date" id="data_sessao" name="data_sessao" value="<?= htmlspecialchars($defaults['data_sessao']) ?>" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700">
                    </div>

                    <div>
                        <label for="tipo_sessao" class="block mb-2 font-semibold">Tipo de sessao</label>
                        <select id="tipo_sessao" name="tipo_sessao" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white">
                            <option value="Ordinaria" <?= $defaults['tipo_sessao'] === 'Ordinaria' ? 'selected' : '' ?>>Ordinaria</option>
                            <option value="Magna" <?= $defaults['tipo_sessao'] === 'Magna' ? 'selected' : '' ?>>Magna</option>
                            <option value="Magna de Iniciacao" <?= $defaults['tipo_sessao'] === 'Magna de Iniciacao' ? 'selected' : '' ?>>Magna de Iniciacao</option>
                            <option value="Magna de Elevacao" <?= $defaults['tipo_sessao'] === 'Magna de Elevacao' ? 'selected' : '' ?>>Magna de Elevacao</option>
                            <option value="Magna de Exaltacao" <?= $defaults['tipo_sessao'] === 'Magna de Exaltacao' ? 'selected' : '' ?>>Magna de Exaltacao</option>
                        </select>
                    </div>

                    <div>
                        <label for="grau_sessao" class="block mb-2 font-semibold">Grau da sessao</label>
                        <select id="grau_sessao" name="grau_sessao" required class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-700 bg-white">
                            <option value="Aprendiz Macom" <?= $defaults['grau_sessao'] === 'Aprendiz Macom' ? 'selected' : '' ?>>Aprendiz Macom (Grau 1)</option>
                            <option value="Companheiro Macom" <?= $defaults['grau_sessao'] === 'Companheiro Macom' ? 'selected' : '' ?>>Companheiro Macom (Grau 2)</option>
                            <option value="Mestre Macom" <?= $defaults['grau_sessao'] === 'Mestre Macom' ? 'selected' : '' ?>>Mestre Macom (Grau 3)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-700 text-white font-bold py-3 rounded-lg mt-6 hover:bg-blue-800">Gerar e enviar certificado</button>
            </div>
        </form>
    </div>

    <script>
        const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
        if (tg) {
            tg.ready();
            tg.expand();
            document.getElementById('init_data').value = tg.initData || '';
            if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                document.getElementById('chat_id').value = tg.initDataUnsafe.user.id;
            }
        }
    </script>
</body>
</html>
