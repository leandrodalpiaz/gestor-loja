<?php
$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
 
$erpPageTitle = 'Parâmetros da Loja';
$appShellEyebrow = 'Sistema';
$appShellTitle = 'Parâmetros da Loja';
$appShellDescription = 'Registro oficial, parâmetros institucionais e memória administrativa da Loja.';
$appShellActiveHref = '/admin/loja';
$appShellActions = [
    ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];

$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Nominata Oficial', 'href' => '/admin/cargos'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos', 'href' => '/admin/acessos'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaústres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatório Anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

if (!empty($_SESSION['is_system_admin'])) {
    $appShellSidebarSections[] = [
        'title' => 'Sistema',
        'items' => [
            ['label' => 'Painel do sistema', 'href' => '/sistema'],
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
        ],
    ];
}

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

        <?php if ($mensagem): ?>
            <div class="alert alert-success mb-6">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger mb-6">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>

        <div class="grid items-start gap-7 xl:grid-cols-12">
            <form action="/admin/loja/salvar" method="POST" class="min-w-0 space-y-6 xl:col-span-8 2xl:col-span-9">
                
                <section class="card depth-1 p-7">
                    <div class="card-header border-b border-white/5 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Identificação institucional</div>
                        <h2 class="mt-2 text-2xl font-black leading-tight text-white">Dados formais da Loja</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div>
                            <label class="form-label">Número</label>
                            <input type="text" name="numero_loja" value="<?= htmlspecialchars((string) ($configuracao['numero_loja'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Título de tratamento</label>
                            <input type="text" name="titulo_tratamento" value="<?= htmlspecialchars((string) ($configuracao['titulo_tratamento'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div class="xl:col-span-1">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome_loja" value="<?= htmlspecialchars((string) ($configuracao['nome_loja'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Cidade</label>
                            <input type="text" name="cidade" value="<?= htmlspecialchars((string) ($configuracao['cidade'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">UF</label>
                            <input type="text" name="uf" maxlength="2" value="<?= htmlspecialchars((string) ($configuracao['uf'] ?? '')) ?>" class="form-input w-full uppercase">
                        </div>
                        <div>
                            <label class="form-label">Oriente oficial</label>
                            <input type="text" name="oriente" value="<?= htmlspecialchars((string) ($configuracao['oriente'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Fundação</label>
                            <input type="date" name="data_fundacao" value="<?= htmlspecialchars((string) ($configuracao['data_fundacao'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Decreto de fundação</label>
                            <input type="text" name="decreto_fundacao" value="<?= htmlspecialchars((string) ($configuracao['decreto_fundacao'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Tipo</label>
                            <input type="text" name="tipo_loja" value="<?= htmlspecialchars((string) ($configuracao['tipo_loja'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Reconhecimento</label>
                            <input type="date" name="data_reconhecimento" value="<?= htmlspecialchars((string) ($configuracao['data_reconhecimento'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Instalação</label>
                            <input type="date" name="data_instalacao" value="<?= htmlspecialchars((string) ($configuracao['data_instalacao'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Carta constitutiva</label>
                            <input type="date" name="data_entrega_carta_constitutiva" value="<?= htmlspecialchars((string) ($configuracao['data_entrega_carta_constitutiva'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Endereço administrativo</label>
                            <input type="text" name="endereco" value="<?= htmlspecialchars((string) ($configuracao['endereco'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">CEP</label>
                            <input type="text" name="cep" value="<?= htmlspecialchars((string) ($configuracao['cep'] ?? '')) ?>" class="form-input w-full">
                        </div>
                    </div>
                </section>

                <section class="card depth-1 p-7">
                    <div class="card-header border-b border-white/5 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Identidade Visual (PWA)</div>
                        <h2 class="mt-2 text-2xl font-black leading-tight text-white">Cores e Logotipo</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div>
                            <label class="form-label">Cor Primária (Light)</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="cor_primaria_light" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_light'] ?? '#1E3A8A')) ?>" class="h-10 w-10 cursor-pointer rounded border border-white/10 bg-transparent p-0.5 focus:border-erp-gold">
                                <input type="text" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_light'] ?? '#1E3A8A')) ?>" class="form-input w-full" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Cor Primária (Dark)</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="cor_primaria_dark" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_dark'] ?? '#0F172A')) ?>" class="h-10 w-10 cursor-pointer rounded border border-white/10 bg-transparent p-0.5 focus:border-erp-gold">
                                <input type="text" value="<?= htmlspecialchars((string) ($configuracao['cor_primaria_dark'] ?? '#0F172A')) ?>" class="form-input w-full" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Caminho do Logotipo</label>
                            <input type="text" name="logo_path" placeholder="Ex: /assets/tenants/loja/logo.png" value="<?= htmlspecialchars((string) ($configuracao['logo_path'] ?? '')) ?>" class="form-input w-full">
                            <p class="form-hint mt-2">Deixe em branco para usar o logotipo padrão resolvido pelo slug.</p>
                        </div>
                    </div>
                </section>

                <section class="card depth-1 p-7">
                    <div class="card-header border-b border-white/5 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Potência e estrutura</div>
                        <h2 class="mt-2 text-2xl font-black leading-tight text-white">Vínculos institucionais</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div class="xl:col-span-2">
                            <label class="form-label">Nome da Potência</label>
                            <input type="text" name="potencia_nome" value="<?= htmlspecialchars((string) ($configuracao['potencia_nome'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Sigla da Potência</label>
                            <input type="text" name="potencia_sigla" value="<?= htmlspecialchars((string) ($configuracao['potencia_sigla'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div class="md:col-span-2 xl:col-span-3">
                            <label class="form-label">Grande Secretaria / Delegacia / órgão correlato</label>
                            <input type="text" name="grande_secretaria" value="<?= htmlspecialchars((string) ($configuracao['grande_secretaria'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Região / simpósios</label>
                            <input type="text" name="regiao_simposios" value="<?= htmlspecialchars((string) ($configuracao['regiao_simposios'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Rito</label>
                            <input type="text" name="rito" value="<?= htmlspecialchars((string) ($configuracao['rito'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Obediência do templo</label>
                            <input type="text" name="obediencia_templo" value="<?= htmlspecialchars((string) ($configuracao['obediencia_templo'] ?? '')) ?>" class="form-input w-full">
                        </div>
                    </div>
                </section>

                <section class="card depth-1 p-7">
                    <div class="card-header border-b border-white/5 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Templo e reuniões</div>
                        <h2 class="mt-2 text-2xl font-black leading-tight text-white">Operação ritual da Loja</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div class="md:col-span-2 xl:col-span-3">
                            <label class="form-label">Endereço do templo</label>
                            <input type="text" name="templo_endereco" value="<?= htmlspecialchars((string) ($configuracao['templo_endereco'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Proprietário / locatário</label>
                            <input type="text" name="proprietario_locatario" value="<?= htmlspecialchars((string) ($configuracao['proprietario_locatario'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Nome do templo</label>
                            <input type="text" name="nome_templo" value="<?= htmlspecialchars((string) ($configuracao['nome_templo'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Data de sagração</label>
                            <input type="date" name="data_sagracao_templo" value="<?= htmlspecialchars((string) ($configuracao['data_sagracao_templo'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Dia da semana</label>
                            <input type="text" name="dia_semana_reuniao" value="<?= htmlspecialchars((string) ($configuracao['dia_semana_reuniao'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Horário</label>
                            <input type="text" name="horario_reuniao" value="<?= htmlspecialchars((string) ($configuracao['horario_reuniao'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Periodicidade</label>
                            <input type="text" name="periodicidade_reuniao" value="<?= htmlspecialchars((string) ($configuracao['periodicidade_reuniao'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div class="md:col-span-2 xl:col-span-3 flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">
                            <input type="checkbox" name="trabalha_palacio_maconico" id="trabalha_palacio_maconico" value="1" <?= !empty($configuracao['trabalha_palacio_maconico']) ? 'checked' : '' ?> class="form-checkbox">
                            <label for="trabalha_palacio_maconico" class="form-check-label text-sm text-slate-300">Loja trabalha no Palácio Maçônico</label>
                        </div>
                    </div>
                </section>

                <section class="card depth-1 p-7">
                    <div class="card-header border-b border-white/5 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Contato e memória</div>
                        <h2 class="mt-2 text-2xl font-black leading-tight text-white">Uso transversal e história</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="form-label">E-mail oficial</label>
                            <input type="email" name="email_oficial" value="<?= htmlspecialchars((string) ($configuracao['email_oficial'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Telefone oficial</label>
                            <input type="text" name="telefone_oficial" value="<?= htmlspecialchars((string) ($configuracao['telefone_oficial'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">CNPJ</label>
                            <input type="text" name="cnpj" value="<?= htmlspecialchars((string) ($configuracao['cnpj'] ?? '')) ?>" class="form-input w-full">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Observação institucional para relatórios</label>
                            <textarea name="observacao_relatorios" rows="3" class="form-textarea w-full"><?= htmlspecialchars((string) ($configuracao['observacao_relatorios'] ?? '')) ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">História da Loja</label>
                            <textarea name="historia_loja" rows="14" class="form-textarea w-full leading-6"><?= htmlspecialchars((string) ($configuracao['historia_loja'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </section>

                <section class="card depth-1 p-7">
                    <div class="card-header border-b border-white/5 pb-4">
                        <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Tesouraria e parâmetros</div>
                        <h2 class="mt-2 text-2xl font-black leading-tight text-white">Contribuição mensal e regra institucional</h2>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <div>
                            <label class="form-label">Contribuição mensal padrão</label>
                            <input type="number" step="0.01" min="0" name="mensalidade_valor_padrao" value="<?= htmlspecialchars((string) ($configuracao['mensalidade_valor_padrao'] ?? '150.00')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Dia sugerido para pagamento</label>
                            <input type="number" min="1" max="31" name="mensalidade_dia_sugerido" value="<?= htmlspecialchars((string) ($configuracao['mensalidade_dia_sugerido'] ?? '10')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Regra de atraso</label>
                            <select name="mensalidade_regra_atraso" class="form-select w-full">
                                <option value="primeiro_dia_util_mes_seguinte" <?= (($configuracao['mensalidade_regra_atraso'] ?? '') === 'primeiro_dia_util_mes_seguinte') ? 'selected' : '' ?>>Primeiro dia útil do mês seguinte</option>
                                <option value="dia_sugerido" <?= (($configuracao['mensalidade_regra_atraso'] ?? '') === 'dia_sugerido') ? 'selected' : '' ?>>No próprio dia sugerido</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Biblioteca por contribuinte</label>
                            <input type="number" step="0.01" min="0" name="contribuicao_biblioteca_valor_padrao" value="<?= htmlspecialchars((string) ($configuracao['contribuicao_biblioteca_valor_padrao'] ?? '44.00')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Contribuintes por mês</label>
                            <input type="number" min="1" max="12" name="contribuicao_biblioteca_quantidade_mensal" value="<?= htmlspecialchars((string) ($configuracao['contribuicao_biblioteca_quantidade_mensal'] ?? '2')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Tipo da chave PIX</label>
                            <input type="text" name="pix_chave_tipo" value="<?= htmlspecialchars((string) ($configuracao['pix_chave_tipo'] ?? 'CNPJ')) ?>" class="form-input w-full">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Chave PIX da Loja</label>
                            <input type="text" name="pix_chave_valor" value="<?= htmlspecialchars((string) ($configuracao['pix_chave_valor'] ?? '31.274.071/0001-06')) ?>" class="form-input w-full">
                        </div>
                        <div>
                            <label class="form-label">Beneficiário PIX</label>
                            <input type="text" name="pix_beneficiario" value="<?= htmlspecialchars((string) ($configuracao['pix_beneficiario'] ?? 'Nome da Loja')) ?>" class="form-input w-full">
                        </div>
                        <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-warning/20 bg-warning/5 px-4 py-4 text-sm leading-6 text-warning">
                            Configure aqui as regras financeiras padrão da Loja atual. O valor continua editável no admin.
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-primary">
                        Salvar parâmetros da Loja
                    </button>
                    <a href="/admin/cargos" class="btn border border-white/10 text-slate-300 hover:bg-white/5">
                        Voltar para a nominata
                    </a>
                </div>
            </form>

            <aside class="space-y-6 xl:sticky xl:top-8 xl:col-span-4 2xl:col-span-3">
                <section class="card depth-1 p-7">
                    <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Leitura rápida</div>
                    <h2 class="mt-2 text-2xl font-black leading-tight text-white">
                        <?= htmlspecialchars(trim((string) (($configuracao['nome_loja'] ?? '') . ' Nº ' . ($configuracao['numero_loja'] ?? '')), " Nº")) ?>
                    </h2>
                    <div class="mt-5 space-y-3 text-sm text-slate-300">
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Potência: <strong class="text-white"><?= htmlspecialchars((string) (($configuracao['potencia_sigla'] ?? '') !== '' ? $configuracao['potencia_sigla'] : ($configuracao['potencia_nome'] ?? '-'))) ?></strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Rito: <strong class="text-white"><?= htmlspecialchars((string) ($configuracao['rito'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Oriente: <strong class="text-white"><?= htmlspecialchars((string) ($configuracao['oriente'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Templo: <strong class="text-white"><?= htmlspecialchars((string) ($configuracao['nome_templo'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Sessões: <strong class="text-white"><?= htmlspecialchars(trim((string) (($configuracao['dia_semana_reuniao'] ?? '') . ' • ' . ($configuracao['horario_reuniao'] ?? '') . ' • ' . ($configuracao['periodicidade_reuniao'] ?? '')), ' •')) ?></strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Contribuição mensal: <strong class="text-white">R$ <?= htmlspecialchars(number_format((float) ($configuracao['mensalidade_valor_padrao'] ?? 150), 2, ',', '.')) ?></strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Biblioteca: <strong class="text-white">R$ <?= htmlspecialchars(number_format((float) ($configuracao['contribuicao_biblioteca_valor_padrao'] ?? 44), 2, ',', '.')) ?> • <?= htmlspecialchars((string) ($configuracao['contribuicao_biblioteca_quantidade_mensal'] ?? 2)) ?> irmãos/mês</strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">PIX: <strong class="text-white"><?= htmlspecialchars((string) ($configuracao['pix_chave_tipo'] ?? 'CNPJ')) ?> <?= htmlspecialchars((string) ($configuracao['pix_chave_valor'] ?? '-')) ?></strong></div>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3">Atraso: <strong class="text-white"><?= htmlspecialchars((string) (($configuracao['mensalidade_regra_atraso'] ?? '') === 'primeiro_dia_util_mes_seguinte' ? 'Primeiro dia útil do mês seguinte' : 'Dia sugerido')) ?></strong></div>
                    </div>
                </section>

                <section class="card depth-2 border-warning/20 bg-warning/5 p-7">
                    <div class="text-[0.72rem] uppercase tracking-[0.32em] text-erp-gold font-bold">Memória institucional</div>
                    <h2 class="mt-2 text-2xl font-black leading-tight text-warning">Prévia da história</h2>
                    <p class="mt-4 text-sm leading-7 text-slate-300 whitespace-pre-line text-justify">
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

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
