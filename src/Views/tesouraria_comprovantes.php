<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$configuracaoLoja = $configuracaoLoja ?? [];
$categoriasEntrada = $categoriasEntrada ?? [];
$pixTipo = (string) ($configuracaoLoja['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoLoja['pix_chave_valor'] ?? '');
$pixBeneficiario = (string) ($configuracaoLoja['pix_beneficiario'] ?? '');

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Caixa de Entrada - Comprovantes';
$appShellDescription = 'Validação dos comprovantes PIX recebidos, com prioridade para pendências.';
$appShellActiveHref = '/tesouraria/comprovantes';

require __DIR__ . '/partials/erp_shell_open.php';

?>

<?php require __DIR__ . '/partials/erp_tesouraria_topbar.php'; ?>

<div class="space-y-6">
    <!-- Info PIX -->
    <div class="card">
        <div class="card-body">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PIX oficial da Loja</p>
            <p class="mt-1 font-semibold text-gray-800 dark:text-gray-200">
                <?= htmlspecialchars($pixTipo) ?>: <?= htmlspecialchars($pixValor) ?>
                <?php if ($pixBeneficiario): ?>
                    <span class="font-normal text-gray-600 dark:text-gray-400">&bull; <?= htmlspecialchars($pixBeneficiario) ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Abas de Status -->
    <div class="erp-tab-nav">
        <button type="button" data-status="pendente" onclick="filtrarStatus('pendente')" class="erp-tab-link active">
            Pendentes (<span id="count-pendentes">0</span>)
        </button>
        <button type="button" data-status="aprovado" onclick="filtrarStatus('aprovado')" class="erp-tab-link">
            Aprovados (<span id="count-aprovados">0</span>)
        </button>
        <button type="button" data-status="rejeitado" onclick="filtrarStatus('rejeitado')" class="erp-tab-link">
            Rejeitados (<span id="count-rejeitados">0</span>)
        </button>
    </div>

    <!-- Container de Comprovantes -->
    <div id="comprovantes-container" class="space-y-4">
        <div class="text-center text-sm text-gray-500 py-8">Carregando...</div>
    </div>
</div>

<!-- Modal de Validação -->
<div id="modal-validacao" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h2 class="modal-title">Validar Comprovante</h2>
            <button class="modal-close" onclick="fecharModalValidacao()">&times;</button>
        </div>
        <form id="form-validacao" class="modal-body max-h-[70vh] overflow-y-auto">
            <input type="hidden" id="comprovante-id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label-readonly">Obreiro</label><input type="text" id="obreiro-info" class="form-input-readonly" readonly></div>
                <div><label class="form-label-readonly">Valor Informado</label><input type="text" id="valor-informado" class="form-input-readonly" readonly></div>
                <div><label class="form-label-readonly">Período Informado</label><input type="text" id="periodo-informado" class="form-input-readonly" readonly></div>
                <div><label class="form-label-readonly">Data do Envio</label><input type="text" id="data-envio" class="form-input-readonly" readonly></div>
            </div>
            <hr class="border-gray-200 dark:border-gray-700 my-4">
            <div><label for="valor-validado" class="form-label">Valor Validado (R$) *</label><input type="number" id="valor-validado" step="0.01" min="0" class="form-input" required></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="mes-validado" class="form-label">Mês de Referência *</label>
                    <select id="mes-validado" class="form-select" required>
                        <?php for ($m=1; $m<=12; $m++) echo "<option value='$m'>".ucfirst(strftime('%B', mktime(0,0,0,$m,1))).'</option>'; ?>
                    </select>
                </div>
                <div>
                    <label for="ano-validado" class="form-label">Ano de Referência *</label>
                    <select id="ano-validado" class="form-select" required>
                        <?php for ($a = date('Y') - 1; $a <= date('Y'); $a++) echo "<option value='$a'>$a</option>"; ?>
                    </select>
                </div>
            </div>
            <div><label for="rotulo-pagamento" class="form-label">Rótulo do Pagamento</label><input type="text" id="rotulo-pagamento" class="form-input" placeholder="Ex: Mensalidade 05/2026 + Biblioteca"></div>
            <div>
                <label for="categoria-id" class="form-label">Categoria Financeira</label>
                <select id="categoria-id" class="form-select">
                    <option value="">Selecionar</option>
                    <?php foreach ($categoriasEntrada as $categoria): ?>
                        <option value="<?= (int) $categoria['id'] ?>"><?= htmlspecialchars((string) $categoria['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label for="obrigacao-parcela-id" class="form-label">Baixar em Obrigação Aberta</label><select id="obrigacao-parcela-id" class="form-select"><option value="">Lançar sem vincular parcela</option></select></div>
        </form>
        <div class="modal-footer">
            <button type="button" onclick="fecharModalValidacao()" class="btn btn-secondary">Cancelar</button>
            <button type="button" onclick="rejeitarComprovante()" class="btn btn-danger">Rejeitar</button>
            <button type="button" onclick="document.getElementById('form-validacao').requestSubmit()" class="btn btn-success">Aprovar</button>
        </div>
    </div>
</div>

<!-- Modal de Rejeição -->
<div id="modal-rejeicao" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h2 class="modal-title">Motivo da Rejeição</h2>
            <button class="modal-close" onclick="fecharModalRejeicao()">&times;</button>
        </div>
        <div class="modal-body">
            <textarea id="motivo-rejeicao" class="form-textarea w-full h-24" placeholder="Explique por que este comprovante está sendo rejeitado..."></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="fecharModalRejeicao()" class="btn btn-secondary">Cancelar</button>
            <button type="button" onclick="confirmarRejeicao()" class="btn btn-danger">Confirmar Rejeição</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

<script src="/assets/js/tesouraria_comprovantes.js"></script>



