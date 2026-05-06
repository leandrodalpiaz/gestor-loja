<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$obreiroCompleto = $obreiroCompleto ?? [];
$irmaosAtivos = $irmaosAtivos ?? [];

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? $obreiroCompleto['nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? $obreiroCompleto['cargo'] ?? '');
$usuarioGrau = (string) ($_SESSION['usuario_grau'] ?? $obreiroCompleto['grau'] ?? '');
$usuarioCim = (string) ($_SESSION['usuario_cim'] ?? $obreiroCompleto['cim'] ?? '');
$usuarioEmail = (string) ($_SESSION['usuario_email'] ?? $obreiroCompleto['email'] ?? '');
$usuarioTelefone = (string) ($obreiroCompleto['telefone'] ?? '');

$cargoLabels = [
    'secretario'       => 'Secretário',
    'tesoureiro'       => 'Tesoureiro',
    'chanceler'        => 'Chanceler',
    'veneravel'        => 'Venerável Mestre',
    'orador'           => 'Orador',
    '1_vigilante'      => '1º Vigilante',
    '1 vigilante'      => '1º Vigilante',
    'primeiro_vigilante' => '1º Vigilante',
    '2_vigilante'      => '2º Vigilante',
    '2 vigilante'      => '2º Vigilante',
    'segundo_vigilante' => '2º Vigilante',
    'hospitaleiro'     => 'Hospitaleiro',
    'mestre_banquetes' => 'Mestre de Banquetes',
    'mestre_harmonia'  => 'Mestre de Harmonia',
    'admin'            => 'Administrador do Sistema',
];
$cargoDisplay = $cargoLabels[strtolower(trim($usuarioCargo))] ?? ($usuarioCargo !== '' ? $usuarioCargo : 'Obreiro');

$formatDate = static fn (?string $date): string => $date ? (new DateTimeImmutable($date))->format('d/m/Y') : 'Não cadastrada';

$pwaPageTitle = 'Perfil';
$pwaActiveTab = 'perfil';
$pwaShowBackButton = false;

// Preparar lista de irmãos ativos sanitizada para JSON
$irmaosJsonData = [];
foreach ($irmaosAtivos as $irm) {
    $cargoIrm = strtolower(trim((string) ($irm['cargo'] ?? '')));
    $cargoDisplayIrm = $cargoLabels[$cargoIrm] ?? ($irm['cargo'] !== '' ? $irm['cargo'] : 'Obreiro');
    
    $telFormat = preg_replace('/\D/', '', (string) ($irm['telefone'] ?? ''));
    if (str_starts_with($telFormat, '55') && strlen($telFormat) >= 12) {
        $whatsappUrl = 'https://wa.me/' . $telFormat;
    } elseif ($telFormat !== '') {
        $whatsappUrl = 'https://wa.me/55' . $telFormat;
    } else {
        $whatsappUrl = '';
    }

    $irmaosJsonData[] = [
        'id' => (string) ($irm['id'] ?? ''),
        'nome' => (string) ($irm['nome'] ?? ''),
        'cargo' => $cargoDisplayIrm,
        'grau' => (string) ($irm['grau'] ?? 'Obreiro'),
        'cim' => (string) ($irm['cim'] ?? ''),
        'telefone' => (string) ($irm['telefone'] ?? ''),
        'email' => (string) ($irm['email'] ?? ''),
        'whatsapp' => $whatsappUrl,
    ];
}

ob_start();
?>

<div class="p-4 sm:p-6 space-y-5" 
     x-data="{ 
         activeTab: 'meu-perfil',
         search: '',
         tema: localStorage.getItem('pwa-theme') || 'auto',
         openMyDetails: false,
         openBrotherDetails: false,
         selectedBrother: null,
         irmaosList: <?= htmlspecialchars(json_encode($irmaosJsonData), ENT_QUOTES, 'UTF-8') ?>,

         get filteredIrmaos() {
             if (!this.search.trim()) return this.irmaosList;
             const q = this.search.toLowerCase();
             return this.irmaosList.filter(i => 
                 i.nome.toLowerCase().includes(q) || 
                 i.cargo.toLowerCase().includes(q) || 
                 i.cim.includes(q)
             );
         },

         showBrother(irm) {
             this.selectedBrother = irm;
             this.openBrotherDetails = true;
         }
     }"
     x-init="
         function applyTheme(t) {
             if (t === 'dark') document.documentElement.classList.add('dark');
             else if (t === 'light') document.documentElement.classList.remove('dark');
             else {
                 if (window.matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.classList.add('dark');
                 else document.documentElement.classList.remove('dark');
             }
         }
         applyTheme(tema);
         $watch('tema', (val) => { localStorage.setItem('pwa-theme', val); applyTheme(val); });
     ">

    <!-- Sub-Abas superiores locais (Estilo Segmented Control nativo) -->
    <div class="flex rounded-xl bg-erpBg p-1 border border-erpBorder">
        <button type="button" 
                @click="activeTab = 'meu-perfil'"
                :class="activeTab === 'meu-perfil' ? 'bg-erpSurface text-erpNavy shadow-sm font-bold' : 'text-erpMuted hover:text-erpNavy'"
                class="flex-1 py-2 text-center text-xs rounded-lg transition-all active:scale-[0.98]">
            Meu Perfil
        </button>
        <button type="button" 
                @click="activeTab = 'contatos'"
                :class="activeTab === 'contatos' ? 'bg-erpSurface text-erpNavy shadow-sm font-bold' : 'text-erpMuted hover:text-erpNavy'"
                class="flex-1 py-2 text-center text-xs rounded-lg transition-all active:scale-[0.98]">
            Contatos (Irmãos)
        </button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         ABA: MEU PERFIL
    ════════════════════════════════════════════════════════════════════ -->
    <div x-show="activeTab === 'meu-perfil'" class="space-y-4" x-transition:enter="transition ease-out duration-200">
        
        <!-- Card de Identificação Superior -->
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-erpNavyDeep to-erpNavy text-xl font-bold text-white shadow-lg">
                    <?= strtoupper(mb_substr($usuarioNome, 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <h2 class="text-base font-extrabold text-erpNavy truncate"><?= htmlspecialchars($usuarioNome) ?></h2>
                    <p class="text-xs text-erpMuted mt-0.5"><?= htmlspecialchars($cargoDisplay) ?></p>
                </div>
            </div>
        </div>

        <!-- Dados Básicos Rápidos -->
        <div class="rounded-2xl border border-erpBorder bg-erpSurface shadow-sm divide-y divide-erpBorder">
            <?php if ($usuarioCim !== ''): ?>
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-erpMuted">CIM</span>
                    <span class="font-bold text-erpNavy font-mono"><?= htmlspecialchars($usuarioCim) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($usuarioGrau !== ''): ?>
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-erpMuted">Grau</span>
                    <span class="font-bold text-erpNavy"><?= htmlspecialchars($usuarioGrau) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($usuarioEmail !== ''): ?>
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-erpMuted">E-mail</span>
                    <span class="font-bold text-erpNavy truncate ml-4"><?= htmlspecialchars($usuarioEmail) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botão Ver Dados Cadastrais Completos (Abre o Drawer) -->
        <button type="button"
                @click="openMyDetails = true"
                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border border-erpBorder bg-erpSurface px-4 py-3.5 text-xs font-bold text-erpNavy shadow-sm active:scale-[0.98] transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-erpGold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-5M16.5 13.5L19 11m0 0l-2.5-2.5M19 11H9" />
            </svg>
            Ver Meus Dados Completos
        </button>

        <!-- Toggle de Aparência (Claro / Escuro / Auto) -->
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm space-y-1">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-erpNavy">Aparência</h3>
            <p class="text-[0.68rem] text-erpMuted">Escolha o tema visual ideal para o seu dispositivo.</p>
            <div class="mt-4 grid grid-cols-3 gap-2">
                <button type="button" @click="tema = 'light'"
                        :class="tema === 'light' ? 'border-erpNavy bg-erpNavy/5 text-erpNavy font-bold' : 'border-erpBorder text-erpMuted'"
                        class="rounded-xl border px-3 py-2.5 text-xs font-semibold active:scale-95 transition-all">
                    ☀️ Claro
                </button>
                <button type="button" @click="tema = 'dark'"
                        :class="tema === 'dark' ? 'border-erpNavy bg-erpNavy/5 text-erpNavy font-bold' : 'border-erpBorder text-erpMuted'"
                        class="rounded-xl border px-3 py-2.5 text-xs font-semibold active:scale-95 transition-all">
                    🌙 Escuro
                </button>
                <button type="button" @click="tema = 'auto'"
                        :class="tema === 'auto' ? 'border-erpNavy bg-erpNavy/5 text-erpNavy font-bold' : 'border-erpBorder text-erpMuted'"
                        class="rounded-xl border px-3 py-2.5 text-xs font-semibold active:scale-95 transition-all">
                    🔄 Auto
                </button>
            </div>
        </div>

        <!-- Links e Ações Rápidas -->
        <div class="space-y-3">
            <a href="/dashboard"
               class="flex items-center justify-center gap-2 rounded-2xl border border-erpBorder bg-erpSurface px-4 py-3.5 text-xs font-bold text-erpNavy shadow-sm active:scale-[0.98] transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-erpMuted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Abrir Painel Completo (Desktop)
            </a>

            <a href="/logout"
               class="flex items-center justify-center gap-2 rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3.5 text-xs font-bold text-rose-700 shadow-sm active:scale-[0.98] transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sair da Conta
            </a>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         ABA: CONTATOS (IRMÃOS)
    ════════════════════════════════════════════════════════════════════ -->
    <div x-show="activeTab === 'contatos'" class="space-y-4" x-transition:enter="transition ease-out duration-200" style="display: none;">
        
        <!-- Barra de busca com lupa -->
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-erpMuted">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input type="text" 
                   x-model="search" 
                   placeholder="Buscar irmão por nome, cargo, CIM..."
                   class="w-full pl-10 pr-4 py-3 rounded-2xl border border-erpBorder bg-erpSurface text-xs text-erpNavy focus:border-erpNavy focus:ring-1 focus:ring-erpNavy">
        </div>

        <!-- Lista Filtrada de Contatos -->
        <div class="space-y-2.5 overflow-y-auto max-h-[52dvh] pr-0.5">
            <template x-for="irm in filteredIrmaos" :key="irm.id">
                <div @click="showBrother(irm)"
                     class="flex items-center justify-between gap-3 p-3.5 rounded-2xl border border-erpBorder bg-erpSurface shadow-sm cursor-pointer active:scale-[0.99] transition-transform">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-erpBg font-bold text-erpNavy text-xs">
                            <span x-text="irm.nome.charAt(0)"></span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-extrabold text-erpNavy truncate" x-text="irm.nome"></h4>
                            <p class="text-[0.62rem] text-erpMuted mt-0.5" x-text="irm.cargo + ' · CIM ' + (irm.cim || 'N/A')"></p>
                        </div>
                    </div>
                    <div class="shrink-0 text-erpMuted">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </template>

            <!-- Fallback para pesquisa sem resultados -->
            <div x-show="filteredIrmaos.length === 0" 
                 class="rounded-2xl border border-dashed border-erpBorder p-8 text-center text-xs text-erpMuted bg-erpSurface/25">
                Nenhum irmão encontrado para a busca.
            </div>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         DRAWER 1: MEUS DADOS DETALHADOS (Fase 4 e 5)
    ════════════════════════════════════════════════════════════════════ -->
    <div class="fixed inset-0 z-50 overflow-hidden" 
         x-show="openMyDetails" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openMyDetails = false"></div>

        <div class="absolute inset-x-0 bottom-0 max-h-[85dvh] rounded-t-3xl border-t border-erpBorder bg-erpSurface pb-safe shadow-2xl flex flex-col"
             x-show="openMyDetails"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <div class="mx-auto my-3 h-1.5 w-12 rounded-full bg-erpMuted/30"></div>

            <div class="flex items-center justify-between px-5 pb-3 border-b border-erpBorder">
                <h3 class="text-base font-bold text-erpNavy">Meus Dados Cadastrais</h3>
                <button type="button" @click="openMyDetails = false" class="rounded-full bg-erpBg p-1.5 text-erpMuted hover:text-erpNavy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Conteúdo Cadastral com Scroll -->
            <div class="p-5 overflow-y-auto space-y-4 text-sm">
                
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-erpGold">Dados Pessoais</h4>
                    <div class="rounded-2xl bg-erpBg p-3.5 space-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-erpMuted">Nome Completo:</span> <strong class="text-erpNavy text-right"><?= htmlspecialchars($usuarioNome) ?></strong></div>
                        <div class="flex justify-between"><span class="text-erpMuted">Nascimento:</span> <strong class="text-erpNavy"><?= $formatDate($obreiroCompleto['data_nascimento_civil'] ?? null) ?></strong></div>
                        <div class="flex justify-between"><span class="text-erpMuted">CPF:</span> <strong class="text-erpNavy font-mono"><?= htmlspecialchars($obreiroCompleto['cpf'] ?? 'Não cadastrado') ?></strong></div>
                        <div class="flex justify-between"><span class="text-erpMuted">Profissão:</span> <strong class="text-erpNavy"><?= htmlspecialchars($obreiroCompleto['profissao'] ?? 'Não cadastrada') ?></strong></div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-erpGold">Datas de Elevação Maçônica</h4>
                    <div class="rounded-2xl bg-erpBg p-3.5 space-y-2 text-xs border border-erpBorder">
                        <div class="flex justify-between"><span class="text-erpMuted">Iniciação:</span> <strong class="text-erpNavy"><?= $formatDate($obreiroCompleto['data_iniciacao'] ?? null) ?></strong></div>
                        <div class="flex justify-between"><span class="text-erpMuted">Elevação:</span> <strong class="text-erpNavy"><?= $formatDate($obreiroCompleto['data_elevacao'] ?? null) ?></strong></div>
                        <div class="flex justify-between"><span class="text-erpMuted">Exaltação:</span> <strong class="text-erpNavy"><?= $formatDate($obreiroCompleto['data_exaltacao'] ?? null) ?></strong></div>
                    </div>
                </div>

                <div class="space-y-3 pb-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-erpGold">Contato</h4>
                    <div class="rounded-2xl bg-erpBg p-3.5 space-y-2 text-xs">
                        <div class="flex justify-between"><span class="text-erpMuted">Telefone:</span> <strong class="text-erpNavy"><?= htmlspecialchars($usuarioTelefone ?: 'Não cadastrado') ?></strong></div>
                        <div class="flex justify-between"><span class="text-erpMuted">E-mail:</span> <strong class="text-erpNavy"><?= htmlspecialchars($usuarioEmail ?: 'Não cadastrado') ?></strong></div>
                    </div>
                    <p class="text-[0.62rem] text-center text-erpMuted">Para solicitar qualquer alteração nos dados acima, entre em contato com a Secretaria.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         DRAWER 2: DETALHES DO IRMÃO SELECIONADO (Fase 5 em Uso Real)
    ════════════════════════════════════════════════════════════════════ -->
    <div class="fixed inset-0 z-50 overflow-hidden" 
         x-show="openBrotherDetails" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openBrotherDetails = false"></div>

        <div class="absolute inset-x-0 bottom-0 max-h-[85dvh] rounded-t-3xl border-t border-erpBorder bg-erpSurface pb-safe shadow-2xl flex flex-col"
             x-show="openBrotherDetails"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <div class="mx-auto my-3 h-1.5 w-12 rounded-full bg-erpMuted/30"></div>

            <div class="flex items-center justify-between px-5 pb-3 border-b border-erpBorder">
                <h3 class="text-base font-bold text-erpNavy">Contato do Irmão</h3>
                <button type="button" @click="openBrotherDetails = false" class="rounded-full bg-erpBg p-1.5 text-erpMuted hover:text-erpNavy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Dados do irmão selecionado -->
            <div class="p-5 overflow-y-auto space-y-4" x-show="selectedBrother">
                
                <div class="text-center space-y-2">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-erpNavyDeep to-erpNavy text-2xl font-bold text-white shadow-md">
                        <span x-text="selectedBrother ? selectedBrother.nome.charAt(0) : ''"></span>
                    </div>
                    <h4 class="text-base font-extrabold text-erpNavy" x-text="selectedBrother?.nome"></h4>
                    <p class="text-xs text-erpMuted" x-text="selectedBrother?.cargo + ' · ' + selectedBrother?.grau"></p>
                </div>

                <div class="rounded-2xl bg-erpBg p-4 space-y-2.5 text-xs">
                    <div class="flex justify-between"><span class="text-erpMuted">CIM:</span> <strong class="text-erpNavy font-mono" x-text="selectedBrother?.cim || 'Não informado'"></strong></div>
                    <div class="flex justify-between"><span class="text-erpMuted">E-mail:</span> <strong class="text-erpNavy" x-text="selectedBrother?.email || 'Não informado'"></strong></div>
                    <div class="flex justify-between"><span class="text-erpMuted">Telefone:</span> <strong class="text-erpNavy" x-text="selectedBrother?.telefone || 'Não informado'"></strong></div>
                </div>

                <!-- Botões de Ação Rápida de Comunicação (Atalho Telefone e WhatsApp) -->
                <div class="grid grid-cols-2 gap-3 pt-2 pb-4" x-show="selectedBrother?.telefone">
                    <a :href="'tel:' + selectedBrother?.telefone"
                       class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-erpBorder bg-erpSurface px-4 py-3 text-xs font-bold text-erpNavy shadow-sm active:scale-95 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-erpNavy" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        Ligar
                    </a>
                    <a x-show="selectedBrother?.whatsapp"
                       :href="selectedBrother?.whatsapp" 
                       target="_blank"
                       class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.262 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.59 1.978 14.113.953 11.5.953c-5.44 0-9.865 4.371-9.87 9.8a9.69 9.69 0 001.523 5.152l-.993 3.623 3.714-.966c1.554.897 3.109 1.396 4.686 1.396zm10.155-6.852c-.27-.133-1.597-.779-1.845-.867-.248-.088-.429-.133-.61.134-.181.266-.7.867-.859 1.045-.158.177-.317.199-.587.066-.27-.133-1.139-.415-2.17-1.325-.802-.708-1.344-1.583-1.501-1.85-.158-.266-.017-.41.118-.543.122-.12.27-.31.406-.465.135-.155.181-.266.27-.443.09-.176.045-.332-.022-.465-.067-.133-.61-1.447-.836-1.978-.22-.527-.44-.456-.61-.464-.158-.008-.339-.01-.52-.01-.18 0-.475.067-.723.333-.248.266-.95.918-.95 2.24 0 1.323.974 2.599 1.11 2.776.135.177 1.917 2.893 4.643 4.053.649.276 1.155.44 1.548.563.652.205 1.246.176 1.716.107.524-.078 1.597-.645 1.823-1.267.226-.623.226-1.155.158-1.267-.067-.111-.248-.177-.518-.31z"/>
                        </svg>
                        WhatsApp
                    </a>
                </div>

            </div>
        </div>
    </div>

    <div class="text-center text-[0.62rem] text-erpMuted pt-2">
        Gestor-Loja · Oficina Digital · v2026
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
