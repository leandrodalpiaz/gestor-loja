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
        <h4 class="text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Pendentes (<span x-text="comprovantes.length">0</span>)</h4>

        <!-- Caso não haja comprovantes -->
        <template x-if="comprovantes.length === 0">
            <div class="p-8 text-center text-sm" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-radius:1rem;border-style:dashed;color:#94a3b8">
                Nenhum comprovante pendente na fila.
            </div>
        </template>

        <!-- Cards de Comprovantes -->
        <template x-for="item in comprovantes" :key="item.id">
            <div style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-radius:1rem;" class="p-4 shadow-sm space-y-3 active:scale-[0.99] transition-transform">
                
                <!-- Obreiro e Data -->
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h5 class="text-sm font-bold truncate" style="color:#f1f5f9" x-text="item.obreiro_nome || 'Comprovante s/ Obreiro'"></h5>
                        <p class="text-[0.68rem] mt-0.5" style="color:#94a3b8" x-text="'Enviado em: ' + (item.criado_em ? new Date(item.criado_em.replace(/-/g, '/')).toLocaleString('pt-BR', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '-')"></p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-sm font-extrabold" style="color:#f1f5f9" x-text="'R$ ' + parseFloat(item.valor_informado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        <p class="text-[0.62rem] text-amber-400 font-bold uppercase tracking-wider mt-0.5" x-text="(item.mes_ref_informado && item.ano_ref_informado) ? item.mes_ref_informado + '/' + item.ano_ref_informado : 'Sem período'"></p>
                    </div>
                </div>

                <!-- Detalhes do comprovante -->
                <div class="rounded-xl p-2.5 text-xs space-y-1" style="background:rgba(255,255,255,0.03);color:#e2e8f0">
                    <p class="truncate"><strong style="color:#94a3b8">Descrição:</strong> <span x-text="item.descricao_usuario || item.rotulo_pagamento || '-'"></span></p>
                    <p class="text-[0.68rem]" x-show="item.nome_arquivo">
                        <strong style="color:#94a3b8">Arquivo:</strong> 
                        <a :href="'/assets/uploads/comprovantes/' + item.nome_arquivo" target="_blank" class="font-bold underline hover:opacity-80 inline-flex items-center gap-1" style="color:#f1f5f9">
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
                            class="flex-1 inline-flex items-center justify-center gap-1 rounded-xl px-3 py-2 text-xs font-bold active:scale-95 transition-transform"
                            style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Rejeitar
                    </button>
                    <button type="button" 
                            @click="initApproval(item)"
                            class="flex-1 inline-flex items-center justify-center gap-1 rounded-xl px-3 py-2 text-xs font-bold shadow-sm active:scale-95 transition-transform"
                            style="background:#C9A227;color:#0f172a">
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

        <div class="absolute inset-x-0 bottom-0 max-h-[90dvh] rounded-t-3xl pb-safe shadow-2xl flex flex-col"
             style="background:rgba(255,255,255,0.055);border-top:1px solid rgba(255,255,255,0.09);"
             x-show="openApproveDrawer"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <div class="mx-auto my-3 h-1.5 w-12 rounded-full" style="background:rgba(148,163,184,0.3)"></div>

            <div class="flex items-center justify-between px-5 pb-3" style="border-bottom:1px solid rgba(255,255,255,0.09)">
                <h3 class="text-base font-bold" style="color:#f1f5f9">Validar e Aprovar PIX</h3>
                <button type="button" @click="openApproveDrawer = false" class="rounded-full p-1.5" style="background:rgba(255,255,255,0.03);color:#94a3b8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Formulário no Drawer -->
            <div class="p-5 overflow-y-auto space-y-4">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Valor do Lançamento (R$) *</label>
                    <input type="number" step="0.01" required x-model="approveValor"
                           class="mt-1 block w-full focus:outline-none focus:ring-1 focus:ring-white/20"
                           style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Mês *</label>
                        <select x-model="approveMes" class="mt-1 block w-full focus:outline-none"
                                style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
                            <?php foreach ($nomesMeses as $mIdx => $mNome): ?>
                                <option value="<?= $mIdx ?>"><?= $mNome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Ano *</label>
                        <select x-model="approveAno" class="mt-1 block w-full focus:outline-none"
                                style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
                            <option value="<?= date('Y') - 1 ?>"><?= date('Y') - 1 ?></option>
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Rótulo / Descrição</label>
                    <input type="text" x-model="approveRotulo"
                           class="mt-1 block w-full focus:outline-none focus:ring-1 focus:ring-white/20"
                           style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Categoria Financeira</label>
                    <select x-model="approveCategoriaId" class="mt-1 block w-full focus:outline-none"
                            style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
                        <option value="">Lançar sem categoria específica</option>
                        <template x-for="cat in categorias" :key="cat.id">
                            <option :value="cat.id" x-text="cat.nome"></option>
                        </template>
                    </select>
                </div>

                <!-- Vincular a Parcela Aberta (Se houver) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Baixar em Obrigação Aberta</label>
                    <select x-model="approveParcelaId" class="mt-1 block w-full focus:outline-none"
                            style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
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
                            class="w-full inline-flex items-center justify-center rounded-xl p-3 text-sm font-bold shadow-lg active:scale-[0.98] transition-transform disabled:opacity-50"
                            style="background:#C9A227;color:#0f172a">
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

        <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 max-w-md mx-auto rounded-3xl shadow-2xl flex flex-col z-10"
             style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-radius:1rem;"
             x-show="openRejectModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="scale-100 opacity-100"
             x-transition:leave-end="scale-95 opacity-0">
            
            <div class="flex items-center justify-between px-5 py-4" style="border-bottom:1px solid rgba(255,255,255,0.09)">
                <h3 class="text-base font-bold" style="color:#f1f5f9">Rejeitar Comprovante</h3>
                <button type="button" @click="openRejectModal = false" class="rounded-full p-1.5" style="background:rgba(255,255,255,0.03);color:#94a3b8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Motivo da Rejeição *</label>
                    <textarea x-model="rejectMotivo" required placeholder="Escreva o motivo claro para que o Obreiro compreenda..."
                              class="mt-1 block w-full h-24 focus:outline-none focus:ring-1 focus:ring-white/20 resize-none"
                              style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" @click="openRejectModal = false" class="flex-1 rounded-xl py-3 text-xs font-bold" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.09);color:#f1f5f9">
                        Cancelar
                    </button>
                    <button type="button"
                            @click="submitReject"
                            :disabled="submitting"
                            class="flex-1 rounded-xl py-3 text-xs font-bold text-white shadow-lg hover:bg-rose-700 disabled:opacity-50"
                            style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25)">
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
