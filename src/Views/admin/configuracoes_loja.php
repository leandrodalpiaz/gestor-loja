<?php
$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
 
$erpPageTitle = 'Parâmetros da Loja';
$appShellEyebrow = 'Administração';
$appShellTitle = 'Parâmetros da Loja';
$appShellDescription = 'Registro oficial, parâmetros institucionais e memória administrativa da Loja.';
$appShellActiveHref = '/admin/loja';
$appShellActions = [
    ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Administração',
        'items' => [
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];
require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>
<?php /* TODO(layout): consolidar classes visuais legadas mantendo o formulário e o contrato de submissão atuais. */ ?>
<?php if (false): ?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parâmetros da Loja</title>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        marinho: '#10233f',
                        tinta: '#1d2d44',
                        dourado: '#c7a14b'
                    },
                    fontFamily: {
                        display: ['Cormorant Garamond', 'serif'],
                        sans: ['Inter', 'sans-serif']
                    },
                    boxShadow: {
                        dignidade: '0 24px 70px rgba(16, 35, 63, 0.12)'
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,_#f7f3ea_0%,_#eef2f6_100%)] font-sans text-slate-800">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] border border-white/50 bg-[linear-gradient(135deg,_rgba(16,35,63,0.96),_rgba(21,40,72,0.92)_52%,_rgba(199,161,75,0.28)_100%)] px-6 py-8 text-white shadow-dignidade sm:px-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="text-[0.72rem] uppercase tracking-[0.34em] text-amber-200/90">Administração • Loja</div>
                    <h1 class="mt-3 font-display text-5xl leading-none sm:text-6xl">Registro oficial e memória institucional.</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-200 sm:text-base">
                        Centralize aqui a identidade formal da Loja, os vínculos com a Potência, os dados do templo e a narrativa histórica que dará contexto aos relatórios e ao front institucional.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="/admin/cargos" class="rounded-full border border-white/20 px-4 py-2 text-sm text-white/90 transition hover:bg-white/10">Nominata oficial</a>
                    <a href="/dashboard" class="rounded-full border border-white/20 px-4 py-2 text-sm text-white/90 transition hover:bg-white/10">Voltar ao painel</a>
                </div>
            </div>
        </section>
<?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 grid items-start gap-7 xl:grid-cols-12">
            <form action="/admin/loja/salvar" method="POST" class="min-w-0 space-y-6 xl:col-span-8 2xl:col-span-9">
                <section class="rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Identificação institucional</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Dados formais da Loja</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Número</label>
                            <input type="text" name="numero_loja" value="<?= htmlspecialchars((string) ($configuracao['numero_loja'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Título de tratamento</label>
                            <input type="text" name="titulo_tratamento" value="<?= htmlspecialchars((string) ($configuracao['titulo_tratamento'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div class="xl:col-span-1">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Nome</label>
                            <input type="text" name="nome_loja" value="<?= htmlspecialchars((string) ($configuracao['nome_loja'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Cidade</label>
                            <input type="text" name="cidade" value="<?= htmlspecialchars((string) ($configuracao['cidade'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">UF</label>
                            <input type="text" name="uf" maxlength="2" value="<?= htmlspecialchars((string) ($configuracao['uf'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm uppercase focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Oriente oficial</label>
                            <input type="text" name="oriente" value="<?= htmlspecialchars((string) ($configuracao['oriente'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Fundação</label>
                            <input type="date" name="data_fundacao" value="<?= htmlspecialchars((string) ($configuracao['data_fundacao'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Decreto de fundação</label>
                            <input type="text" name="decreto_fundacao" value="<?= htmlspecialchars((string) ($configuracao['decreto_fundacao'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Tipo</label>
                            <input type="text" name="tipo_loja" value="<?= htmlspecialchars((string) ($configuracao['tipo_loja'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Reconhecimento</label>
                            <input type="date" name="data_reconhecimento" value="<?= htmlspecialchars((string) ($configuracao['data_reconhecimento'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Instalação</label>
                            <input type="date" name="data_instalacao" value="<?= htmlspecialchars((string) ($configuracao['data_instalacao'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Carta constitutiva</label>
                            <input type="date" name="data_entrega_carta_constitutiva" value="<?= htmlspecialchars((string) ($configuracao['data_entrega_carta_constitutiva'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Endereço administrativo</label>
                            <input type="text" name="endereco" value="<?= htmlspecialchars((string) ($configuracao['endereco'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">CEP</label>
                            <input type="text" name="cep" value="<?= htmlspecialchars((string) ($configuracao['cep'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Identidade Visual (PWA)</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Cores e Logotipo</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Cor Primária (Light)</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="cor_primaria_light" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_light'] ?? '#1E3A8A')) ?>" class="h-10 w-10 cursor-pointer rounded border border-slate-200 bg-transparent p-0.5 focus:border-dourado">
                                <input type="text" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_light'] ?? '#1E3A8A')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Cor Primária (Dark)</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="cor_primaria_dark" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_dark'] ?? '#0F172A')) ?>" class="h-10 w-10 cursor-pointer rounded border border-slate-200 bg-transparent p-0.5 focus:border-dourado">
                                <input type="text" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_dark'] ?? '#0F172A')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Caminho do Logotipo</label>
                            <input type="text" name="logo_path" placeholder="Ex: /assets/tenants/loja/logo.png" value="<?= htmlspecialchars((string) ($configuracao['logo_path'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                            <p class="mt-2 text-xs text-slate-400">Deixe em branco para usar o logotipo padrão resolvido pelo slug.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Potência e estrutura</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Vínculos institucionais</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div class="xl:col-span-2">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Nome da Potência</label>
                            <input type="text" name="potencia_nome" value="<?= htmlspecialchars((string) ($configuracao['potencia_nome'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Sigla da Potência</label>
                            <input type="text" name="potencia_sigla" value="<?= htmlspecialchars((string) ($configuracao['potencia_sigla'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div class="md:col-span-2 xl:col-span-3">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Grande Secretaria / Delegacia / órgão correlato</label>
                            <input type="text" name="grande_secretaria" value="<?= htmlspecialchars((string) ($configuracao['grande_secretaria'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Região / simpósios</label>
                            <input type="text" name="regiao_simposios" value="<?= htmlspecialchars((string) ($configuracao['regiao_simposios'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Rito</label>
                            <input type="text" name="rito" value="<?= htmlspecialchars((string) ($configuracao['rito'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Obediência do templo</label>
                            <input type="text" name="obediencia_templo" value="<?= htmlspecialchars((string) ($configuracao['obediencia_templo'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Templo e reuniões</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Operação ritual da Loja</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div class="md:col-span-2 xl:col-span-3">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Endereço do templo</label>
                            <input type="text" name="templo_endereco" value="<?= htmlspecialchars((string) ($configuracao['templo_endereco'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Proprietário / locatário</label>
                            <input type="text" name="proprietario_locatario" value="<?= htmlspecialchars((string) ($configuracao['proprietario_locatario'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Nome do templo</label>
                            <input type="text" name="nome_templo" value="<?= htmlspecialchars((string) ($configuracao['nome_templo'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Data de sagração</label>
                            <input type="date" name="data_sagracao_templo" value="<?= htmlspecialchars((string) ($configuracao['data_sagracao_templo'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Dia da semana</label>
                            <input type="text" name="dia_semana_reuniao" value="<?= htmlspecialchars((string) ($configuracao['dia_semana_reuniao'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Horário</label>
                            <input type="text" name="horario_reuniao" value="<?= htmlspecialchars((string) ($configuracao['horario_reuniao'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Periodicidade</label>
                            <input type="text" name="periodicidade_reuniao" value="<?= htmlspecialchars((string) ($configuracao['periodicidade_reuniao'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <label class="md:col-span-2 xl:col-span-3 inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <input type="checkbox" name="trabalha_palacio_maconico" value="1" <?= !empty($configuracao['trabalha_palacio_maconico']) ? 'checked' : '' ?>>
                            Loja trabalha no Palácio Maçônico
                        </label>
                    </div>
                </section>

                <section class="rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Contato e memória</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Uso transversal e história</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">E-mail oficial</label>
                            <input type="email" name="email_oficial" value="<?= htmlspecialchars((string) ($configuracao['email_oficial'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Telefone oficial</label>
                            <input type="text" name="telefone_oficial" value="<?= htmlspecialchars((string) ($configuracao['telefone_oficial'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">CNPJ</label>
                            <input type="text" name="cnpj" value="<?= htmlspecialchars((string) ($configuracao['cnpj'] ?? '')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Observação institucional para relatórios</label>
                            <textarea name="observacao_relatorios" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none"><?= htmlspecialchars((string) ($configuracao['observacao_relatorios'] ?? '')) ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">História da Loja</label>
                            <textarea name="historia_loja" rows="14" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 focus:border-dourado focus:bg-white focus:outline-none"><?= htmlspecialchars((string) ($configuracao['historia_loja'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="border-b border-slate-200 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Tesouraria e parâmetros</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Contribuição mensal e regra institucional</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Contribuição mensal padrão</label>
                            <input type="number" step="0.01" min="0" name="mensalidade_valor_padrao" value="<?= htmlspecialchars((string) ($configuracao['mensalidade_valor_padrao'] ?? '150.00')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Dia sugerido para pagamento</label>
                            <input type="number" min="1" max="31" name="mensalidade_dia_sugerido" value="<?= htmlspecialchars((string) ($configuracao['mensalidade_dia_sugerido'] ?? '10')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Regra de atraso</label>
                            <select name="mensalidade_regra_atraso" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                                <option value="primeiro_dia_util_mes_seguinte" <?= (($configuracao['mensalidade_regra_atraso'] ?? '') === 'primeiro_dia_util_mes_seguinte') ? 'selected' : '' ?>>Primeiro dia útil do mês seguinte</option>
                                <option value="dia_sugerido" <?= (($configuracao['mensalidade_regra_atraso'] ?? '') === 'dia_sugerido') ? 'selected' : '' ?>>No próprio dia sugerido</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Biblioteca por contribuinte</label>
                            <input type="number" step="0.01" min="0" name="contribuicao_biblioteca_valor_padrao" value="<?= htmlspecialchars((string) ($configuracao['contribuicao_biblioteca_valor_padrao'] ?? '44.00')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Contribuintes por mês</label>
                            <input type="number" min="1" max="12" name="contribuicao_biblioteca_quantidade_mensal" value="<?= htmlspecialchars((string) ($configuracao['contribuicao_biblioteca_quantidade_mensal'] ?? '2')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Tipo da chave PIX</label>
                            <input type="text" name="pix_chave_tipo" value="<?= htmlspecialchars((string) ($configuracao['pix_chave_tipo'] ?? 'CNPJ')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Chave PIX da Loja</label>
                            <input type="text" name="pix_chave_valor" value="<?= htmlspecialchars((string) ($configuracao['pix_chave_valor'] ?? '31.274.071/0001-06')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs uppercase tracking-[0.22em] text-slate-500">Beneficiário PIX</label>
                            <input type="text" name="pix_beneficiario" value="<?= htmlspecialchars((string) ($configuracao['pix_beneficiario'] ?? 'Nome da Loja')) ?>" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-dourado focus:bg-white focus:outline-none">
                        </div>
                        <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
                            Configure aqui as regras financeiras padrão da Loja atual. O valor continua editável no admin.
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="rounded-xl bg-marinho px-5 py-3 text-sm font-medium text-white transition hover:bg-tinta">
                        Salvar parâmetros da Loja
                    </button>
                    <a href="/admin/cargos" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Voltar para a nominata
                    </a>
                </div>
            </form>

            <aside class="space-y-6 xl:sticky xl:top-8 xl:col-span-4 2xl:col-span-3">
                <section class="rounded-[1.5rem] border border-slate-200 bg-white p-7 shadow-sm">
                    <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Leitura rápida</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">
                        <?= htmlspecialchars(trim((string) (($configuracao['nome_loja'] ?? '') . ' Nº ' . ($configuracao['numero_loja'] ?? '')), " Nº")) ?>
                    </h2>
                    <div class="mt-5 space-y-3 text-sm text-slate-600">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Potência: <strong class="text-slate-800"><?= htmlspecialchars((string) (($configuracao['potencia_sigla'] ?? '') !== '' ? $configuracao['potencia_sigla'] : ($configuracao['potencia_nome'] ?? '-'))) ?></strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Rito: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracao['rito'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Oriente: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracao['oriente'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Templo: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracao['nome_templo'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Sessões: <strong class="text-slate-800"><?= htmlspecialchars(trim((string) (($configuracao['dia_semana_reuniao'] ?? '') . ' • ' . ($configuracao['horario_reuniao'] ?? '') . ' • ' . ($configuracao['periodicidade_reuniao'] ?? '')), ' •')) ?></strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Contribuição mensal: <strong class="text-slate-800">R$ <?= htmlspecialchars(number_format((float) ($configuracao['mensalidade_valor_padrao'] ?? 150), 2, ',', '.')) ?></strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Biblioteca: <strong class="text-slate-800">R$ <?= htmlspecialchars(number_format((float) ($configuracao['contribuicao_biblioteca_valor_padrao'] ?? 44), 2, ',', '.')) ?> • <?= htmlspecialchars((string) ($configuracao['contribuicao_biblioteca_quantidade_mensal'] ?? 2)) ?> irmãos/mês</strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">PIX: <strong class="text-slate-800"><?= htmlspecialchars((string) ($configuracao['pix_chave_tipo'] ?? 'CNPJ')) ?> <?= htmlspecialchars((string) ($configuracao['pix_chave_valor'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">Atraso: <strong class="text-slate-800"><?= htmlspecialchars((string) (($configuracao['mensalidade_regra_atraso'] ?? '') === 'primeiro_dia_util_mes_seguinte' ? 'Primeiro dia útil do mês seguinte' : 'Dia sugerido')) ?></strong></div>
                    </div>
                </section>

                <section class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-7 shadow-sm">
                    <div class="text-[0.72rem] uppercase tracking-[0.32em] text-dourado">Memória institucional</div>
                        <h2 class="mt-2 text-3xl font-semibold leading-tight text-erp-navy">Prévia da história</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-700 whitespace-pre-line">
                        <?php
                        $historiaLojaPreview = (string) ($configuracao['historia_loja'] ?? '');
                        if (function_exists('mb_strimwidth')) {
                            $historiaLojaPreview = mb_strimwidth($historiaLojaPreview, 0, 1500, '...');
                        } elseif (strlen($historiaLojaPreview) > 1500) {
                            $historiaLojaPreview = substr($historiaLojaPreview, 0, 1497) . '...';
                        }
                        ?>
                        <?= htmlspecialchars($historiaLojaPreview) ?>
                    </p>
                </section>
            </aside>
        </div>
<?php if (false): ?>
    </main>
</body>
</html>
<?php endif; ?>
<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


