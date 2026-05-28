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

<div class="px-4 py-4 space-y-4" 
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
    <section class="pwa-hero">
        <p class="pwa-eyebrow">Fila de Comprovantes PIX</p>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-white">Validação de Envios</h2>
        <p class="pwa-muted mt-1.5 text-xs">Valide os envios recebidos pelo PWA ou Telegram de forma direta e segura.</p>
    </section>

    <!-- Lista de Comprovantes Pendentes -->
    <div class="space-y-3.5">
        <div class="flex items-center gap-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Pendentes (<span x-text="comprovantes.length">0</span>)
            </p>
            <div class="flex-1 h-[1px] bg-white/5"></div>
        </div>

        <!-- Caso não haja comprovantes -->
        <template x-if="comprovantes.length === 0">
            <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-2xl border border-dashed border-white/10 select-none">
                Nenhum comprovante pendente na fila.
            </div>
        </template>

        <!-- Cards de Comprovantes -->
        <template x-for="item in comprovantes" :key="item.id">
            <div class="pwa-card flex flex-col gap-3">
                
                <!-- Obreiro e Data -->
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h5 class="text-xs font-bold text-slate-200 truncate" x-text="item.obreiro_nome || 'Comprovante s/ Obreiro'"></h5>
                        <p class="text-[10px] text-slate-400 mt-0.5" x-text="'Enviado em: ' + (item.criado_em ? new Date(item.criado_em.replace(/-/g, '/')).toLocaleString('pt-BR', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '-')"></p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-xs font-black text-slate-200" x-text="'R$ ' + parseFloat(item.valor_informado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        <p class="text-[9px] text-amber-400 font-bold uppercase tracking-wider mt-0.5" x-text="(item.mes_ref_informado && item.ano_ref_informado) ? item.mes_ref_informado + '/' + item.ano_ref_informado : 'Sem período'"></p>
                    </div>
                </div>

                <!-- Detalhes do comprovante -->
                <div class="pwa-list-group text-[11px] p-2.5 space-y-1">
                    <p class="truncate"><span class="text-slate-400 font-medium">Descrição:</span> <span class="text-slate-200" x-text="item.descricao_usuario || item.rotulo_pagamento || '-'"></span></p>
                    <p x-show="item.nome_arquivo">
                        <span class="text-slate-400 font-medium">Arquivo:</span> 
                        <a :href="'/assets/uploads/comprovantes/' + item.nome_arquivo" target="_blank" class="font-bold underline text-slate-200 inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Ver Anexo
                        </a>
                    </p>
                </div>

                <!-- Ações Diretas no Card -->
                <div class="flex items-center gap-2 mt-1 select-none">
                    <button type="button" 
                            @click="initRejection(item)"
                            class="pwa-btn-secondary py-1.5 text-xs font-bold border-red-500/20 bg-red-500/10 hover:bg-red-500/20 text-red-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Rejeitar
                    </button>
                    <button type="button" 
                            @click="initApproval(item)"
                            class="pwa-btn-primary py-1.5 text-xs font-bold bg-amber-500 text-slate-950">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Aprovar
                    </button>
                </div>

            </div>
        </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         DRAWER DE APROVAÇÃO
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

        <div class="absolute inset-x-0 bottom-0 max-h-[90dvh] rounded-t-3xl pb-safe shadow-2xl flex flex-col bg-slate-900 border-t border-white/10"
             x-show="openApproveDrawer"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <div class="mx-auto my-3 h-1 w-9 rounded-full bg-slate-700 select-none"></div>

            <div class="flex items-center justify-between px-5 pb-3 border-b border-white/5">
                <h3 class="text-sm font-bold text-slate-100">Validar e Aprovar PIX</h3>
                <button type="button" @click="openApproveDrawer = false" class="rounded-full p-1.5 bg-slate-800 text-slate-400 active:scale-90 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Formulário no Drawer -->
            <div class="p-5 overflow-y-auto space-y-4">
                
                <div>
                    <label class="pwa-label">Valor do Lançamento (R$) *</label>
                    <input type="number" step="0.01" required x-model="approveValor" class="pwa-input">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="pwa-label">Mês *</label>
                        <select x-model="approveMes" class="pwa-select">
                            <?php foreach ($nomesMeses as $mIdx => $mNome): ?>
                                <option value="<?= $mIdx ?>"><?= $mNome ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="pwa-label">Ano *</label>
                        <select x-model="approveAno" class="pwa-select">
                            <option value="<?= date('Y') - 1 ?>"><?= date('Y') - 1 ?></option>
                            <option value="<?= date('Y') ?>"><?= date('Y') ?></option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="pwa-label">Rótulo / Descrição</label>
                    <input type="text" x-model="approveRotulo" class="pwa-input">
                </div>

                <div>
                    <label class="pwa-label">Categoria Financeira</label>
                    <select x-model="approveCategoriaId" class="pwa-select">
                        <option value="">Lançar sem categoria específica</option>
                        <template x-for="cat in categorias" :key="cat.id">
                            <option :value="cat.id" x-text="cat.nome"></option>
                        </template>
                    </select>
                </div>

                <!-- Vincular a Parcela Aberta (Se houver) -->
                <div>
                    <label class="pwa-label">Baixar em Obrigação Aberta</label>
                    <select x-model="approveParcelaId" class="pwa-select">
                        <option value="">Lançar sem vincular parcela</option>
                        <template x-for="parc in approveParcelasDisponiveis" :key="parc.id">
                            <option :value="parc.id" x-text="parc.titulo + ' (' + parseFloat(parc.valor_restante || parc.valor_previsto).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'}) + ')'"></option>
                        </template>
                    </select>
                </div>

                <div class="pt-2 pb-4 select-none">
                    <button type="button"
                            @click="submitApprove"
                            :disabled="submitting"
                            class="pwa-btn-primary">
                        <span x-text="submitting ? 'Processando...' : 'Aprovar e Lançar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         MODAL DE REJEIÇÃO
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

        <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 max-w-md mx-auto rounded-3xl shadow-2xl flex flex-col z-10 bg-slate-900 border border-white/10"
             x-show="openRejectModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="scale-100 opacity-100"
             x-transition:leave-end="scale-95 opacity-0">
            
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                <h3 class="text-sm font-bold text-slate-100">Rejeitar Comprovante</h3>
                <button type="button" @click="openRejectModal = false" class="rounded-full p-1.5 bg-slate-800 text-slate-400 active:scale-90 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="pwa-label">Motivo da Rejeição *</label>
                    <textarea x-model="rejectMotivo" required placeholder="Escreva o motivo claro para que o Obreiro compreenda..." class="pwa-textarea"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2 select-none">
                    <button type="button" @click="openRejectModal = false" class="pwa-btn-secondary flex-1 py-2.5">
                        Cancelar
                    </button>
                    <button type="button"
                            @click="submitReject"
                            :disabled="submitting"
                            class="pwa-btn-secondary flex-1 py-2.5 border-red-500/20 bg-red-500/10 hover:bg-red-500/20 text-red-300">
                        <span x-text="submitting ? 'Processando...' : 'Rejeitar'"></span>
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
