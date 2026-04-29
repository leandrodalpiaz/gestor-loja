<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Data MaÃ§Ã´nica</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        input, textarea, select {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
    </style>
</head>
<body class="min-h-screen p-4">
<?php
$tipo = $_GET['tipo'] ?? 'IniciaÃ§Ã£o';
$tiposValidos = [
    'IniciaÃ§Ã£o', 'ElevaÃ§Ã£o', 'ExaltaÃ§Ã£o', 'InstalaÃ§Ã£o',
    'Oriente Eterno', 'FiliaÃ§Ã£o', 'Posse GrÃ£o Mestre',
    'ConcessÃ£o de Obreiro HonorÃ¡rio',
];
if (!in_array($tipo, $tiposValidos, true)) {
    $tipo = 'IniciaÃ§Ã£o';
}

$emojis = [
    'IniciaÃ§Ã£o'                      => 'Iniciacao',
    'ElevaÃ§Ã£o'                       => 'Elevacao',
    'ExaltaÃ§Ã£o'                      => 'Exaltacao',
    'InstalaÃ§Ã£o'                     => 'Instalacao',
    'Oriente Eterno'                 => 'Oriente',
    'FiliaÃ§Ã£o'                       => 'Filiacao',
    'Posse GrÃ£o Mestre'              => 'Posse',
    'ConcessÃ£o de Obreiro HonorÃ¡rio'  => 'Honraria',
];
$emoji = $emojis[$tipo] ?? 'Data';
// Oriente Eterno nÃ£o exibe loja nem mensagem custom na ficha
$mostraLoja   = $tipo !== 'Oriente Eterno';
$mostraCustom = $tipo !== 'Oriente Eterno';
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-lg font-bold mb-1"><?= $emoji ?> <?= htmlspecialchars($tipo) ?></h1>
    <p class="text-sm text-gray-500 mb-4">Preencha os dados para registrar esta data maÃ§Ã´nica.</p>

    <div id="alert-ok"  class="hidden mb-3 rounded p-3 bg-green-100 text-green-800 text-sm font-medium">Registro salvo com sucesso!</div>
    <div id="alert-err" class="hidden mb-3 rounded p-3 bg-red-100 text-red-800 text-sm font-medium"></div>

    <form id="form" class="space-y-4">
        <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

        <div>
            <label class="block text-sm font-medium mb-1">Nome do IrmÃ£o <span class="text-red-500">*</span></label>
            <input name="nome" type="text" required autocomplete="off"
                   placeholder="Nome histÃ³rico usado na Loja"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">
                <?= $tipo === 'Oriente Eterno' ? 'Data do falecimento' : 'Data da cerimÃ´nia' ?>
                <span class="text-red-500">*</span>
            </label>
            <input name="data_evento" type="date" required
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-400 mt-1">O ano importa e Ã© usado para calcular quantos anos se passaram.</p>
        </div>

        <?php if ($mostraLoja): ?>
        <div>
            <label class="block text-sm font-medium mb-1">Loja onde ocorreu <span class="text-red-500">*</span></label>
            <input name="local" type="text" required autocomplete="off"
                   placeholder="Ex.: Loja UniÃ£o - BagÃ©, RS"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-400 mt-1">Informe o nome completo da Loja onde ocorreu o evento.</p>
        </div>
        <?php endif; ?>

        <?php if ($mostraCustom): ?>
        <div>
            <label class="block text-sm font-medium mb-1">Mensagem personalizada <span class="text-gray-400 font-normal">(opcional)</span></label>
            <textarea name="mensagem_custom" rows="4"
                      placeholder="Se preenchida, substitui o texto automÃ¡tico gerado pelo sistema."
                      class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>
        <?php endif; ?>

        <button type="submit"
                class="w-full py-3 rounded-xl bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700 active:scale-95 transition-transform">
            Salvar data maÃ§Ã´nica
        </button>
    </form>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

document.getElementById('form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    const data = Object.fromEntries(new FormData(this));
    data.initData = tg.initData;

    try {
        const res = await fetch('/api/miniapp/efemeride/salvar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const json = await res.json();

        if (json.ok) {
            document.getElementById('alert-ok').classList.remove('hidden');
            document.getElementById('alert-err').classList.add('hidden');
            this.reset();
            setTimeout(() => tg.close(), 1800);
        } else {
            throw new Error(json.erro || 'NÃ£o foi possÃ­vel salvar agora. Revise os dados e tente novamente.');
        }
    } catch (err) {
        const el = document.getElementById('alert-err');
        el.textContent = 'Erro: ' + err.message;
        el.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar data maÃ§Ã´nica';
    }
});
</script>
</body>
</html>



