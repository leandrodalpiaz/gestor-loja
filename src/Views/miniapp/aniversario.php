<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Cadastrar AniversÃ¡rio</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
$tratamento = $_GET['tratamento'] ?? 'irmao';
$tratamentos = [
    'irmao'    => ['label' => 'ðŸ‘” IrmÃ£o',    'cod' => 1, 'exibe_vinculo' => false, 'exibe_idade' => true],
    'cunhada'  => ['label' => 'ðŸ‘© Cunhada',  'cod' => 2, 'exibe_vinculo' => true,  'exibe_idade' => false],
    'sobrinha' => ['label' => 'ðŸ‘§ Sobrinha', 'cod' => 3, 'exibe_vinculo' => true,  'exibe_idade' => true],
    'sobrinho' => ['label' => 'ðŸ‘¦ Sobrinho', 'cod' => 4, 'exibe_vinculo' => true,  'exibe_idade' => true],
];
$cfg = $tratamentos[$tratamento] ?? $tratamentos['irmao'];
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-lg font-bold mb-1">ðŸŽ‚ AniversÃ¡rio â€” <?= htmlspecialchars($cfg['label']) ?></h1>
    <p class="text-sm text-gray-500 mb-4">Preencha os dados para cadastrar este aniversariante.</p>

    <div id="alert-ok"  class="hidden mb-3 rounded p-3 bg-green-100 text-green-800 text-sm font-medium">âœ… Registro salvo com sucesso!</div>
    <div id="alert-err" class="hidden mb-3 rounded p-3 bg-red-100 text-red-800 text-sm font-medium"></div>

    <form id="form" class="space-y-4">
        <input type="hidden" name="tipo"        value="AniversÃ¡rio">
        <input type="hidden" name="cod_vinculo" value="<?= (int) $cfg['cod'] ?>">

        <div>
            <label class="block text-sm font-medium mb-1">
                <?= $cfg['exibe_vinculo'] ? 'Nome do familiar' : 'Nome HistÃ³rico do IrmÃ£o' ?> <span class="text-red-500">*</span>
            </label>
            <input name="nome" type="text" required autocomplete="off"
                   placeholder="<?= $cfg['exibe_vinculo'] ? 'Ex.: Maria Oliveira' : 'Ex.: Leandro Ferreira' ?>"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Data de nascimento <span class="text-red-500">*</span></label>
            <input name="data_evento" type="date" required
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <?php if ($cfg['exibe_vinculo']): ?>
        <div>
            <label class="block text-sm font-medium mb-1">Nome do IrmÃ£o MaÃ§om <span class="text-red-500">*</span></label>
            <input name="parentesco" type="text" required autocomplete="off"
                   placeholder="Ex.: Leandro Ferreira"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Grau de parentesco <span class="text-red-500">*</span></label>
            <input name="vinculo" type="text" required autocomplete="off"
                   placeholder="<?= $tratamento === 'cunhada' ? 'Ex.: cunhada, esposa' : ($tratamento === 'sobrinha' ? 'Ex.: sobrinha, filha' : 'Ex.: sobrinho, filho') ?>"
                   class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <?php endif; ?>

        <button type="submit"
                class="w-full py-3 rounded-xl bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700 active:scale-95 transition-transform">
            Salvar AniversÃ¡rio
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
    btn.textContent = 'Salvandoâ€¦';

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
            throw new Error(json.erro || 'Não foi possível salvar agora. Revise os dados e tente novamente.');
        }
    } catch (err) {
        const el = document.getElementById('alert-err');
        el.textContent = 'âŒ ' + err.message;
        el.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar AniversÃ¡rio';
    }
});
</script>
</body>
</html>


