<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$comprovantesPendentes = $comprovantesPendentes ?? [];
$categoriasEntrada = $categoriasEntrada ?? [];

$formatCurrency = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static fn (?string $date): string => $date ? (new DateTimeImmutable($date))->format('d/m/Y H:i') : '-';

$nomesMeses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$pwaPageTitle = 'Validar PIX';
$pwaActiveTab = 'cargo';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-5" 
     x-data="{ 
         comprovantes: <?= htmlspecialchars(json_encode(array_values($comprovantesPendentes)), ENT_QUOTES, 'UTF-8') ?>,
         categorias: <?= htmlspecialchars(json_encode(array_values($categoriasEntrada)), ENT_QUOTES, 'UTF-8') ?>,
         openApproveDrawer: false,
         openRejectModal: false,
         selectedItem: null,
         approveValor: '',
         approveMes: '',
         approveAno: '',
         approveRotulo: '',
         approveCategoriaId: '',
         approveParcelasDisponiveis: [],
         approveParcelaId: '',
         rejectMotivo: '',
         submitting: false,

         initApproval(item) {
             this.selectedItem = item;
             this.approveValor = item.valor_informado || 0;
             this.approveMes = item.mes_ref_informado || new Date().getMonth() + 1;
             this.approveAno = item.ano_ref_informado || new Date().getFullYear();
             this.approveRotulo = item.rotulo_pagamento || item.descricao_usuario || 'Pagamento via PIX';
             this.approveCategoriaId = this.categorias[0]?.id || '';
             this.approveParcelasDisponiveis = [];
             this.approveParcelaId = '';
             this.openApproveDrawer = true;

             // Carregar parcelas abertas do obreiro assincronamente se houver obreiro_id
             if (item.obreiro_id) {
                 fetch('/api/tesouraria/obrigacoes-abertas?obreiro_id=' + encodeURIComponent(item.obreiro_id))
                     .then(res => res.json())
                     .then(data => {
                         if (data.ok) {
                             this.approveParcelasDisponiveis = data.parcelas || [];
                         }
                     })
                     .catch(err => console.error('Erro ao buscar parcelas:', err));
             }
         },

         async submitApprove() {
             if (this.submitting) return;
             this.submitting = true;
             try {
                 const response = await fetch('/api/tesouraria/comprovantes/aprovar', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify({
                         id: this.selectedItem.id,
                         valor: parseFloat(this.approveValor),
                         mes: parseInt(this.approveMes),
                         ano: parseInt(this.approveAno),
                         rotulo_pagamento: this.approveRotulo,
                         categoria_id: this.approveCategoriaId ? parseInt(this.approveCategoriaId) : null,
                         obrigacao_parcela_id: this.approveParcelaId ? parseInt(this.approveParcelaId) : null
                     })
                 });
                 const data = await response.json();
                 if (data.ok) {
                     alert('Comprovante aprovado com sucesso!');
                     this.comprovantes = this.comprovantes.filter(c => c.id !== this.selectedItem.id);
                     this.openApproveDrawer = false;
                 } else {
                     alert('Erro: ' + (data.erro || 'Falha ao aprovar comprovante.'));
                 }
             } catch (err) {
                 alert('Erro de conexão ao aprovar comprovante.');
             } finally {
                 this.submitting = false;
             }
         },

         initRejection(item) {
             this.selectedItem = item;
             this.rejectMotivo = '';
             this.openRejectModal = true;
         },

         async submitReject() {
             if (!this.rejectMotivo.trim()) {
                 alert('Por favor, informe o motivo da rejeição.');
                 return;
             }
             if (this.submitting) return;
             this.submitting = true;
             try {
                 const response = await fetch('/api/tesouraria/comprovantes/rejeitar', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify({
                         id: this.selectedItem.id,
                         motivo: this.rejectMotivo
                     })
                 });
                 const data = await response.json();
                 if (data.ok) {
                     alert('Comprovante rejeitado.');
                     this.comprovantes = this.comprovantes.filter(c => c.id !== this.selectedItem.id);
                     this.openRejectModal = false;
                 } else {
                     alert('Erro: ' + (data.erro || 'Falha ao rejeitar comprovante.'));
                 }
             } catch (err) {
                 alert('Erro de conexão ao rejeitar comprovante.');
             } finally {
                 this.submitting = false;
             }
         }
     }">

    <!-- Cabeçalho Informativo -->
    <div class="rounded-2xl bg-gradient-to-br from-erpNavyDeep to-erpNavy p-4 text-white shadow-md">
        <h3 class="text-sm font-extrabold tracking-wide uppercase text-erpGold">Fila de Comprovantes PIX</h3>
        <p class="text-xs text-white/80 mt-1">Valide os envios recebidos pelo PWA ou Telegram de forma direta e segura.</p>
    </div>

    <!-- Lista de Comprovantes Pendentes -->
    <div class="space-y-4">
        <h4 class="text-xs font-bold uppercase tracking-wider text-erpMuted">Pendentes (<span x-text="comprovantes.length">0</span>)</h4>

        <!-- Caso não haja comprovantes -->
        <template x-if="comprovantes.length === 0">
            <div class="rounded-2xl border border-dashed border-erpBorder p-8 text-center text-sm text-erpMuted bg-erpSurface/20">
                Nenhum comprovante pendente na fila.
            </div>
        </template>

        <!-- Cards de Comprovantes -->
        <template x-for="item in comprovantes" :key="item.id">
            <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4 shadow-sm space-y-3 active:scale-[0.99] transition-transform">
                
                <!-- Obreiro e Data -->
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h5 class="text-sm font-bold text-erpNavy truncate" x-text="item.obreiro_nome || 'Comprovante s/ Obreiro'"></h5>
                        <p class="text-[0.68rem] text-erpMuted mt-0.5" x-text="'Enviado em: ' + (item.criado_em ? new Date(item.criado_em.replace(/-/g, '/')).toLocaleString('pt-BR', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '-')"></p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-sm font-extrabold text-erpNavy" x-text="'R$ ' + parseFloat(item.valor_informado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        <p class="text-[0.62rem] text-amber-600 font-bold uppercase tracking-wider mt-0.5" x-text="(item.mes_ref_informado && item.ano_ref_informado) ? item.mes_ref_informado + '/' + item.ano_ref_informado : 'Sem período'"></p>
                    </div>
                </div>

                <!-- Detalhes do comprovante -->
                <div class="rounded-xl bg-erpBg p-2.5 text-xs text-erpNavy space-y-1">
                    <p class="truncate"><strong class="text-erpMuted">Descrição:</strong> <span x-text="item.descricao_usuario || item.rotulo_pagamento || '-'"></span></p>
                    <p class="text-[0.68rem]" x-show="item.nome_arquivo">
                        <strong class="text-erpMuted">Arquivo:</strong> 
                        <a :href="'/assets/uploads/comprovantes/' + item.nome_arquivo" target="_blank" class="text-erpNavy font-bold underline hover:text-erpNavyDeep inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Ver Comprovante
                        </a>
                    </p>
                </div>

                <!-- Ações Diretas no Card -->
                <div class="flex items-center gap-2 pt-1">
                    <button type="button" 
                            @click="initRejection(item)"
                            class="flex-1 inline-flex items-center justify-center gap-1 rounded-xl bg-rose-50 border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 active:scale-95 transition-transform hover:bg-rose-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Rejeitar
                    </button>
                    <button type="button" 
                            @click="initApproval(item)"
                            class="flex-1 inline-flex items-center justify-center gap-1 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-sm active:scale-95 transition-transform hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Aprovar
                    </button>
                </div>

            </div>
        </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         DRAWER DE APROVAÇÃO RÁPIDA VIA ALPINEJS
    ════════════════════════════════════════════════════════════════════ -->
    <div class="fixed inset-0 z-50 overflow-hidden" 
         x-show="openApproveDrawer" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openApproveDrawer = false"></div>

        <div class="absolute inset-x-0 bottom-0 max-h-[90dvh] rounded-t-3xl border-t border-erpBorder bg-erpSurface pb-safe shadow-2xl flex flex-col"
             x-show="openApproveDrawer"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <div class="mx-auto my-3 h-1.5 w-12 rounded-full bg-erpMuted/30"></div>

            <div class="flex items-center justify-between px-5 pb-3 border-b border-erpBorder">
                <h3 class="text-base font-bold text-erpNavy">Validar e Aprovar PIX</h3>
                <button type="button" @click="openApproveDrawer = false" class="rounded-full bg-erpBg p-1.5 text-erpMuted hover:text-erpNavy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Formulário no Drawer -->
            <div class="p-5 overflow-y-auto space-y-4">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Valor do Lançamento (R$) *</label>
                    <input type="number" step="0.01" required x-model="approveValor"
                           class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy focus:ring-1 focus:ring-erpNavy">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Mês *</label>
                        <select x-model="approveMes" class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy">
                            <?php foreach ($nomesMeses as $mIdx => $mNome): ?>
                                <option value="<?= $mIdx ?>"><?= $mNome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Ano *</label>
                        <select x-model="approveAno" class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy">
                            <option value="<?= date('Y') - 1 ?>"><?= date('Y') - 1 ?></option>
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Rótulo / Descrição</label>
                    <input type="text" x-model="approveRotulo"
                           class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy focus:ring-1 focus:ring-erpNavy">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Categoria Financeira</label>
                    <select x-model="approveCategoriaId" class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy">
                        <option value="">Lançar sem categoria específica</option>
                        <template x-for="cat in categorias" :key="cat.id">
                            <option :value="cat.id" x-text="cat.nome"></option>
                        </template>
                    </select>
                </div>

                <!-- Vincular a Parcela Aberta (Se houver) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Baixar em Obrigação Aberta</label>
                    <select x-model="approveParcelaId" class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy">
                        <option value="">Lançar sem vincular parcela</option>
                        <template x-for="parc in approveParcelasDisponiveis" :key="parc.id">
                            <option :value="parc.id" x-text="parc.titulo + ' (' + parseFloat(parc.valor_restante || parc.valor_previsto).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'}) + ')'"></option>
                        </template>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="button"
                            @click="submitApprove"
                            :disabled="submitting"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 p-3 text-sm font-bold text-white shadow-lg hover:bg-emerald-700 active:scale-[0.98] transition-transform disabled:opacity-50">
                        <span x-text="submitting ? 'Processando...' : 'Aprovar e Lançar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         MODAL DE REJEIÇÃO VIA ALPINEJS
    ════════════════════════════════════════════════════════════════════ -->
    <div class="fixed inset-0 z-50 overflow-hidden" 
         x-show="openRejectModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openRejectModal = false"></div>

        <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 max-w-md mx-auto rounded-3xl border border-erpBorder bg-erpSurface shadow-2xl flex flex-col z-10"
             x-show="openRejectModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="scale-100 opacity-100"
             x-transition:leave-end="scale-95 opacity-0">
            
            <div class="flex items-center justify-between px-5 py-4 border-b border-erpBorder">
                <h3 class="text-base font-bold text-erpNavy">Rejeitar Comprovante</h3>
                <button type="button" @click="openRejectModal = false" class="rounded-full bg-erpBg p-1.5 text-erpMuted hover:text-erpNavy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Motivo da Rejeição *</label>
                    <textarea x-model="rejectMotivo" required placeholder="Escreva o motivo claro para que o Obreiro compreenda..."
                              class="mt-1 block w-full h-24 rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy focus:ring-1 focus:ring-erpNavy resize-none"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" @click="openRejectModal = false" class="flex-1 rounded-xl bg-erpBg border border-erpBorder py-3 text-xs font-bold text-erpNavy">
                        Cancelar
                    </button>
                    <button type="button"
                            @click="submitReject"
                            :disabled="submitting"
                            class="flex-1 rounded-xl bg-rose-600 py-3 text-xs font-bold text-white shadow-lg hover:bg-rose-700 disabled:opacity-50">
                        <span x-text="submitting ? 'Processando...' : 'Confirmar Rejeição'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
