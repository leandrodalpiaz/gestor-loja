<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$defaults = [
    'data_sessao' => trim((string) ($_GET['data_sessao'] ?? '')),
    'tipo_sessao' => trim((string) ($_GET['tipo_sessao'] ?? '')),
    'grau_sessao' => trim((string) ($_GET['grau_sessao'] ?? '')),
];

$formatDate = static fn($dateStr) => !empty($dateStr) ? (new DateTime($dateStr))->format('d/m/Y') : 'Não definida';

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Chancelaria';
$appShellTitle = 'Emitir Certificado de Presença';
$appShellDescription = 'Gere e envie certificados para visitantes com os dados oficiais da sessão.';
$appShellActiveHref = '/chancelaria/efemerides';

require __DIR__ . '/partials/erp_shell_open.php';

?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna de Informações -->
    <div class="lg:col-span-1 space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Dados da Sessão</h2></div>
            <div class="card-body space-y-4">
                <div class="list-item-param"><span>Data</span><strong><?= $formatDate($defaults['data_sessao']) ?></strong></div>
                <div class="list-item-param"><span>Tipo</span><strong><?= htmlspecialchars($defaults['tipo_sessao'] ?: 'Não definido') ?></strong></div>
                <div class="list-item-param"><span>Grau</span><strong><?= htmlspecialchars($defaults['grau_sessao'] ?: 'Não definido') ?></strong></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Fluxo de Trabalho</h2></div>
            <ul class="card-body space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">1.</span> <span>Preencha os dados do visitante e da sessão no formulário.</span></li>
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">2.</span> <span>Se estiver no Telegram, o certificado será enviado diretamente no chat.</span></li>
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">3.</span> <span>Caso contrário, o download do certificado será iniciado no navegador.</span></li>
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">4.</span> <span>Os dados da sessão podem ser pré-preenchidos a partir da tela de efemérides.</span></li>
            </ul>
        </div>
    </div>

    <!-- Coluna do Formulário -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Formulário de Emissão</h2></div>
            <form method="POST" action="/chancelaria/certificado/gerar" class="card-body">
                <input type="hidden" id="chat_id" name="chat_id">
                <input type="hidden" id="init_data" name="init_data">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="nome_visitante" class="form-label">Nome do Visitante *</label>
                        <input type="text" id="nome_visitante" name="nome_visitante" placeholder="Ex: João da Silva" required class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label for="loja_visitante" class="form-label">Loja do Visitante *</label>
                        <input type="text" id="loja_visitante" name="loja_visitante" placeholder="Ex: ARLS Luz e Verdade nº 123" required class="form-input">
                    </div>
                    <div>
                        <label for="oriente" class="form-label">Oriente *</label>
                        <input type="text" id="oriente" name="oriente" placeholder="Ex: São Paulo - SP" required class="form-input">
                    </div>
                    <div>
                        <label for="data_sessao" class="form-label">Data da Sessão *</label>
                        <input type="date" id="data_sessao" name="data_sessao" value="<?= htmlspecialchars($defaults['data_sessao']) ?>" required class="form-input">
                    </div>
                    <div>
                        <label for="tipo_sessao" class="form-label">Tipo de Sessão *</label>
                        <select id="tipo_sessao" name="tipo_sessao" required class="form-select">
                            <option value="Ordinaria" <?= $defaults['tipo_sessao'] === 'Ordinaria' ? 'selected' : '' ?>>Ordinária</option>
                            <option value="Magna" <?= $defaults['tipo_sessao'] === 'Magna' ? 'selected' : '' ?>>Magna</option>
                            <option value="Magna de Iniciacao" <?= $defaults['tipo_sessao'] === 'Magna de Iniciacao' ? 'selected' : '' ?>>Magna de Iniciação</option>
                            <option value="Magna de Elevacao" <?= $defaults['tipo_sessao'] === 'Magna de Elevacao' ? 'selected' : '' ?>>Magna de Elevação</option>
                            <option value="Magna de Exaltacao" <?= $defaults['tipo_sessao'] === 'Magna de Exaltacao' ? 'selected' : '' ?>>Magna de Exaltação</option>
                        </select>
                    </div>
                    <div>
                        <label for="grau_sessao" class="form-label">Grau da Sessão *</label>
                        <select id="grau_sessao" name="grau_sessao" required class="form-select">
                            <option value="Aprendiz Macom" <?= $defaults['grau_sessao'] === 'Aprendiz Macom' ? 'selected' : '' ?>>Aprendiz Maçom (Grau 1)</option>
                            <option value="Companheiro Macom" <?= $defaults['grau_sessao'] === 'Companheiro Macom' ? 'selected' : '' ?>>Companheiro Maçom (Grau 2)</option>
                            <option value="Mestre Macom" <?= $defaults['grau_sessao'] === 'Mestre Macom' ? 'selected' : '' ?>>Mestre Maçom (Grau 3)</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="btn btn-primary w-full">Gerar e Enviar Certificado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
        if (tg) {
            tg.ready();
            tg.expand();
            document.getElementById('init_data').value = tg.initData || '';
            if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                document.getElementById('chat_id').value = tg.initDataUnsafe.user.id;
            }
        }
    });
</script>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

