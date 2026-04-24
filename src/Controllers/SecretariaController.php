<?php

namespace App\Controllers;

use App\Models\Obreiro;
use App\Models\Balaustre;
use App\Models\Cargo;
use App\Models\ConfiguracaoLoja;
use App\Models\Presenca;
use App\Models\PublicacaoSessao;
use App\Models\PublicacaoSecretaria;
use App\Models\RelatorioSecretariaAnual;
use App\Models\Sessao;
use App\Models\TrabalhoSessao;

class SecretariaController
{
    private const LOJAS_VISITANTES_FREQUENTES = [
        'Fraternidade 1234',
        'Luz e Verdade 0001',
        'Uniao e Trabalho 0002',
        'Ordem e Progresso 0003',
        'Estrela do Oriente 0004',
        'Cavaleiros da Arte Real 0005',
    ];

    private function resolveRedirectDestino(string $padrao = '/secretaria'): string
    {
        $destino = trim((string) ($_POST['return_to'] ?? $_GET['return_to'] ?? ''));
        if ($destino === '' || $destino[0] !== '/') {
            return $padrao;
        }
        return $destino;
    }

    private function normalizarTextoComparacao(?string $valor): string
    {
        $valor = strtolower(trim((string) $valor));
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $valor = preg_replace('/[^a-z0-9]+/', ' ', $valor) ?? '';
        return trim($valor);
    }

    private function carregarNominataOficial(): array
    {
        $nominataMap = [];
        try {
            $cargoModel = new Cargo();
            foreach ($cargoModel->listarResumoCargos() as $cargoResumo) {
                $codigo = strtoupper((string) ($cargoResumo['codigo'] ?? ''));
                if ($codigo === '') {
                    continue;
                }
                $nominataMap[$codigo] = trim((string) ($cargoResumo['titular_nome'] ?? ''));
            }
        } catch (\Throwable $e) {
            $nominataMap = [];
        }

        return $nominataMap;
    }

    private function obterCargosSessaoBase(array $nominataMap): array
    {
        $base = [
            ['codigo' => 'VENERAVEL', 'label' => 'Veneravel Mestre'],
            ['codigo' => 'PRIMEIRO_VIGILANTE', 'label' => '1 Vigilante'],
            ['codigo' => 'SEGUNDO_VIGILANTE', 'label' => '2 Vigilante'],
            ['codigo' => 'ORADOR', 'label' => 'Orador'],
            ['codigo' => 'GUARDA_DA_LEI', 'label' => 'Guarda da Lei'],
            ['codigo' => 'SECRETARIO', 'label' => 'Secretario'],
            ['codigo' => 'TESOUREIRO', 'label' => 'Tesoureiro'],
            ['codigo' => 'CHANCELER', 'label' => 'Chanceler'],
            ['codigo' => 'MESTRE_BANQUETES', 'label' => 'Mestre de Banquetes'],
            ['codigo' => 'MESTRE_DE_CERIMONIAS', 'label' => 'Mestre de Cerimonias'],
            ['codigo' => 'GUARDA_DO_TEMPLO', 'label' => 'Guarda do Templo'],
            ['codigo' => 'HOSPITALEIRO', 'label' => 'Hospitaleiro'],
            ['codigo' => 'PRIMEIRO_DIACONO', 'label' => '1 Diacono'],
            ['codigo' => 'SEGUNDO_DIACONO', 'label' => '2 Diacono'],
            ['codigo' => 'ARQUITETO', 'label' => 'Arquiteto'],
            ['codigo' => 'MESTRE_DE_HARMONIA', 'label' => 'Mestre de Harmonia'],
            ['codigo' => 'PORTA_BANDEIRA', 'label' => 'Porta Bandeira'],
            ['codigo' => 'PORTA_ESPADA', 'label' => 'Porta Espada'],
            ['codigo' => 'COBRIDOR_INTERNO', 'label' => 'Cobridor Interno'],
            ['codigo' => 'COBRIDOR_EXTERNO', 'label' => 'Cobridor Externo'],
        ];

        $vistos = [];
        foreach ($base as &$item) {
            $codigo = strtoupper((string) ($item['codigo'] ?? ''));
            $item['titular_oficial'] = (string) ($nominataMap[$codigo] ?? '');
            $vistos[$codigo] = true;
        }
        unset($item);

        foreach ($nominataMap as $codigo => $titular) {
            if (isset($vistos[$codigo])) {
                continue;
            }
            $base[] = [
                'codigo' => $codigo,
                'label' => ucwords(strtolower(str_replace('_', ' ', $codigo))),
                'titular_oficial' => (string) $titular,
            ];
        }

        return $base;
    }

    public function index(): void
    {
        $sessaoModel = new Sessao();
        $obreiroModel = new Obreiro();
        $trabalhoModel = new TrabalhoSessao();
        $publicacaoModel = new PublicacaoSecretaria();
        $balaustreModel = new Balaustre();
        $presencaModel = new Presenca();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $obreiros = $obreiroModel->getAllAtivos();
        $resumoCadastros = $obreiroModel->obterResumoSecretaria();
        $trabalhos = $trabalhoModel->listarRecentes(8);
        $publicacoes = $publicacaoModel->listarRecentes(8);
        $balaustres = $balaustreModel->listarRecentes(8);
        $configuracaoLoja = (new ConfiguracaoLoja())->obter();
        $sessaoRascunho = $_SESSION['secretaria_sessao_rascunho'] ?? null;
        $sessaoEdicao = null;
        $sessaoEdicaoId = (int) ($_GET['editar_sessao'] ?? 0);
        $sessaoHistorico = null;
        $historicoSessao = [];
        $sessaoHistoricoId = (int) ($_GET['historico_sessao'] ?? 0);
        if ($sessaoEdicaoId > 0) {
            $sessaoEdicao = $sessaoModel->findById($sessaoEdicaoId);
            if (!$sessaoEdicao) {
                $_SESSION['mensagem_erro'] = 'Sessão informada para edição não foi encontrada.';
                header('Location: /secretaria');
                exit;
            }
        }
        if ($sessaoHistoricoId > 0) {
            $sessaoHistorico = $sessaoModel->findById($sessaoHistoricoId);
            if ($sessaoHistorico) {
                $historicoSessao = $sessaoModel->listarHistorico($sessaoHistoricoId, 20);
            }
        }
        $sessaoResumo = null;
        $confirmadosSessaoResumo = [];
        $participantesAgapeResumo = [];
        $sessaoResumoId = (int) ($_GET['sessao_resumo'] ?? 0);
        if ($sessaoResumoId <= 0 && !empty($proximaSessao['id'])) {
            $sessaoResumoId = (int) $proximaSessao['id'];
        }
        if ($sessaoResumoId > 0) {
            $sessaoResumo = $sessaoModel->findById($sessaoResumoId);
            if ($sessaoResumo) {
                $confirmadosSessaoResumo = $presencaModel->listarConfirmadosPorSessao($sessaoResumoId);
                $participantesAgapeResumo = $presencaModel->listarParticipantesAgapePorSessao($sessaoResumoId);
            }
        }
        $sessaoDuplicada = null;
        $resumoRascunhoSessao = null;
        $acoesConfirmacaoRascunho = [];
        if (is_array($sessaoRascunho)) {
            $sessaoDuplicada = $sessaoModel->buscarDuplicidade($sessaoRascunho);
            $resumoRascunhoSessao = $sessaoModel->comporResumoPublicacao($sessaoRascunho, $configuracaoLoja);
            $acoesConfirmacaoRascunho = $sessaoModel->obterAcoesConfirmacao($sessaoRascunho);
        }
        $nominataOficialMap = $this->carregarNominataOficial();
        $cargosSessaoBase = $this->obterCargosSessaoBase($nominataOficialMap);
        $lojasVisitantesFrequentes = self::LOJAS_VISITANTES_FREQUENTES;

        foreach ($balaustres as &$balaustre) {
            $capturado = $balaustre['dados_capturados'] ?? null;
            if (is_string($capturado)) {
                $decoded = json_decode($capturado, true);
                $capturado = is_array($decoded) ? $decoded : null;
            }

            $palavras = is_array($capturado) ? ($capturado['palavra_bem_ordem']['visitantes'] ?? []) : [];
            $cargos = is_array($capturado) ? ($capturado['cargos_sessao'] ?? []) : [];
            $adHoc = 0;
            foreach ($cargos as $cargoSessao) {
                if (($cargoSessao['tipo_ocupacao'] ?? '') === 'ad_hoc') {
                    $adHoc++;
                }
            }

            $balaustre['resumo_palavra_bem_ordem'] = is_array($palavras) ? count($palavras) : 0;
            $balaustre['resumo_cargos_ad_hoc'] = $adHoc;
        }
        unset($balaustre);
        $usuarioId = (string) ($_SESSION['usuario_id'] ?? '');
        $elegibilidadeVoto = $balaustreModel->listarElegibilidadeDoObreiroNosBalaustres(
            $usuarioId,
            array_map(static fn ($row) => (int) ($row['id'] ?? 0), $balaustres)
        );
        $roles = array_values(array_unique(array_map(
            static fn ($role) => strtolower((string) $role),
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        )));
        $podeOperarSecretaria = in_array('secretario', $roles, true) || in_array('admin', $roles, true);
        $podeAbrirVotacao = in_array('veneravel', $roles, true) || in_array('admin', $roles, true);

        $resumo = [
            'obreiros_ativos' => count($obreiros),
            'sessoes_futuras' => count($sessoes),
            'trabalhos_pendentes' => count(array_filter($trabalhos, static fn ($item) => ($item['status_envio_potencia'] ?? '') === 'pendente')),
            'publicacoes_rascunho' => count(array_filter($publicacoes, static fn ($item) => ($item['status_publicacao'] ?? '') === 'rascunho')),
            'balaustres_aptos' => count(array_filter($balaustres, static fn ($item) => ($item['status'] ?? '') === 'apto_votacao')),
        ];

        $dashboard = [
            'title' => 'Dashboard operacional da Secretaria',
            'subtitle' => 'Execução diária, documental e centralizadora.',
            'meta' => [
                'Perfil: operacional',
                'Sem analytics complexos',
                'Foco em pendencias e estados',
            ],
            'actions' => [
                ['label' => 'Nova sessão / salvar sessão', 'href' => '/secretaria'],
                ['label' => 'Publicar rascunho', 'href' => '/secretaria'],
                ['label' => 'Publicar sessão', 'href' => '/secretaria'],
                ['label' => 'Salvar trabalho', 'href' => '/secretaria'],
                ['label' => 'Salvar publicação', 'href' => '/secretaria'],
                ['label' => 'Atualizar obreiro', 'href' => '/obreiros'],
                ['label' => 'Gerar convite', 'href' => '/admin/convites'],
            ],
            'blocks' => [
                [
                    'title' => 'Sessões',
                    'subtitle' => 'Agenda e operação de sessões.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Futuras', 'value' => (string) count($sessoes)],
                        ['label' => 'Rascunhos', 'value' => (string) $resumo['publicacoes_rascunho']],
                    ],
                    'list' => array_map(static fn (array $sessao): array => [
                        'item' => (string) ($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))),
                        'meta' => (string) ($sessao['data_hora_inicio'] ?? ''),
                        'status' => (string) ($sessao['status'] ?? '-'),
                    ], array_slice($sessoes, 0, 5)),
                ],
                [
                    'title' => 'Balaustres',
                    'subtitle' => 'Pendências e estado operacional.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Aptos', 'value' => (string) $resumo['balaustres_aptos']],
                        ['label' => 'Recentes', 'value' => (string) count($balaustres)],
                    ],
                    'list' => array_map(static fn (array $item): array => [
                        'item' => (string) ($item['numero_balaustre'] ?: 'Sem número'),
                        'meta' => (string) ($item['sessao_titulo'] ?? ''),
                        'status' => (string) ($item['status'] ?? '-'),
                    ], array_slice($balaustres, 0, 5)),
                ],
                [
                    'title' => 'Publicações e trabalhos',
                    'subtitle' => 'Controle de envio e rascunho.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Trabalhos pendentes', 'value' => (string) $resumo['trabalhos_pendentes']],
                        ['label' => 'Publicações rascunho', 'value' => (string) $resumo['publicacoes_rascunho']],
                    ],
                    'list' => array_map(static fn (array $item): array => [
                        'item' => (string) ($item['titulo'] ?? 'Publicação'),
                        'meta' => (string) ($item['tipo_publicacao'] ?? 'Secretaria'),
                        'status' => (string) ($item['status_publicacao'] ?? '-'),
                    ], array_slice($publicacoes, 0, 4)),
                ],
                [
                    'title' => 'Obreiros e acessos',
                    'subtitle' => 'Cadastro, convites e integridade.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Obreiros ativos', 'value' => (string) $resumo['obreiros_ativos']],
                        ['label' => 'Com alerta', 'value' => (string) ($resumoCadastros['com_alerta'] ?? 0)],
                        ['label' => 'Com bot', 'value' => (string) ($resumoCadastros['com_telegram'] ?? 0)],
                    ],
                    'list' => [
                        ['item' => 'Central de obreiros', 'meta' => 'Atualizações cadastrais', 'status' => 'Operacional'],
                        ['item' => 'Convites de acesso', 'meta' => 'Controle de entrada', 'status' => 'Ativo'],
                        ['item' => 'Acessos do sistema', 'meta' => 'Permissões e auditoria', 'status' => 'Ativo'],
                    ],
                ],
            ],
            'alerts' => [
                ['title' => 'Pendências de sessão', 'text' => $resumo['sessoes_futuras'] > 0 ? 'Existem sessões para revisão/publicação.' : 'Nenhuma sessão pendente.', 'tone' => $resumo['sessoes_futuras'] > 0 ? 'warning' : 'success'],
                ['title' => 'Balaustres aptos', 'text' => $resumo['balaustres_aptos'] . ' balaustre(s) apto(s) para encaminhamento.', 'tone' => $resumo['balaustres_aptos'] > 0 ? 'warning' : 'success'],
                ['title' => 'Abertura/encerramento de votação', 'text' => $podeAbrirVotacao ? 'Permitido para este usuário.' : 'Somente Venerável/Admin podem abrir ou encerrar votação.', 'tone' => $podeAbrirVotacao ? 'success' : 'warning'],
            ],
            'activity' => array_merge(
                array_map(static fn (array $sessao): array => [
                    'item' => 'Sessao: ' . (string) ($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))),
                    'meta' => 'Status: ' . (string) ($sessao['status'] ?? '-'),
                ], array_slice($sessoes, 0, 3)),
                array_map(static fn (array $b): array => [
                    'item' => 'Balaustre: ' . (string) ($b['numero_balaustre'] ?: 'Sem numero'),
                    'meta' => 'Status: ' . (string) ($b['status'] ?? '-'),
                ], array_slice($balaustres, 0, 3))
            ),
            'links' => [
                ['label' => 'Balaustres / votação', 'href' => '/secretaria/votacao'],
                ['label' => 'Relatorio anual', 'href' => '/secretaria/relatorio-anual'],
                ['label' => 'Central de obreiros', 'href' => '/obreiros'],
                ['label' => 'Convites', 'href' => '/admin/convites'],
            ],
        ];

        require_once __DIR__ . '/../Views/secretaria/index.php';
    }

    public function votacao(): void
    {
        $balaustreModel = new Balaustre();
        $usuarioId = (string) ($_SESSION['usuario_id'] ?? '');
        $roles = array_values(array_unique(array_map(
            static fn ($role) => strtolower((string) $role),
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        )));

        $podeAcompanharTodas = in_array('secretario', $roles, true)
            || in_array('veneravel', $roles, true)
            || in_array('admin', $roles, true);

        $votacoesAbertas = $balaustreModel->listarAbertosParaObreiro($usuarioId, $podeAcompanharTodas);
        $elegibilidadeVoto = $balaustreModel->listarElegibilidadeDoObreiroNosBalaustres(
            $usuarioId,
            array_map(static fn ($row) => (int) ($row['id'] ?? 0), $votacoesAbertas)
        );

        require_once __DIR__ . '/../Views/secretaria/votacao.php';
    }

    public function relatorioAnual(): void
    {
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        if ($ano < 2000 || $ano > 2100) {
            $ano = (int) date('Y');
        }

        $relatorio = (new RelatorioSecretariaAnual())->montar($ano);
        $anosDisponiveis = [];
        $anoAtual = (int) date('Y');
        for ($i = $anoAtual; $i >= max(2000, $anoAtual - 8); $i--) {
            $anosDisponiveis[] = $i;
        }

        require_once __DIR__ . '/../Views/secretaria/relatorio_anual.php';
    }

    public function salvarSessao(): void
    {
        $this->revisarSessao();
    }

    public function revisarSessao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $sessaoModel = new Sessao();
        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $payload = [
            'data_hora_inicio' => trim((string) ($_POST['data_hora_inicio'] ?? '')),
            'data_hora_fim' => trim((string) ($_POST['data_hora_fim'] ?? '')),
            'grau_sessao' => trim((string) ($_POST['grau_sessao'] ?? '')),
            'grau_personalizado' => trim((string) ($_POST['grau_personalizado'] ?? '')),
            'tipo_sessao_principal' => trim((string) ($_POST['tipo_sessao_principal'] ?? '')),
            'tipo_sessao_subtipo' => trim((string) ($_POST['tipo_sessao_subtipo'] ?? '')),
            'tipo_sessao_personalizado' => trim((string) ($_POST['tipo_sessao_personalizado'] ?? '')),
            'traje_tipo' => trim((string) ($_POST['traje_tipo'] ?? '')),
            'traje_personalizado' => trim((string) ($_POST['traje_personalizado'] ?? '')),
            'agape_modalidade' => trim((string) ($_POST['agape_modalidade'] ?? 'nao_havera')),
            'agape_modelo_financeiro' => trim((string) ($_POST['agape_modelo_financeiro'] ?? 'oficial_loja')),
            'agape_valor' => trim((string) ($_POST['agape_valor'] ?? '')),
            'gestao_referencia' => trim((string) ($_POST['gestao_referencia'] ?? '')),
            'natureza_sessao' => trim((string) ($_POST['natureza_sessao'] ?? '')),
            'formato_sessao' => trim((string) ($_POST['formato_sessao'] ?? '')),
            'finalidade_ritual' => trim((string) ($_POST['finalidade_ritual'] ?? '')),
            'templo_local' => trim((string) ($_POST['templo_local'] ?? '')),
            'sessao_branca' => isset($_POST['sessao_branca']),
            'sessao_a_campo' => isset($_POST['sessao_a_campo']),
            'conta_relatorio_potencia' => isset($_POST['conta_relatorio_potencia']),
            'observacao_relatorio' => trim((string) ($_POST['observacao_relatorio'] ?? '')),
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'ordem_dia' => trim((string) ($_POST['ordem_dia'] ?? '')),
            'observacao_interna' => trim((string) ($_POST['observacao_interna'] ?? '')),
        ];
        $payload = $this->normalizarRascunhoSessao($payload);
        if ($sessaoId > 0) {
            $payload['id'] = $sessaoId;
        }
        $_SESSION['secretaria_sessao_rascunho'] = $payload;
        $_SESSION['mensagem_sucesso'] = $sessaoId > 0
            ? 'Sessão preparada para revisão final da edição. Confira o resumo antes de confirmar a atualização.'
            : 'Sessão preparada para revisão final. Confira o resumo antes de publicar.';

        header('Location: /secretaria');
        exit;
    }

    public function publicarSessaoRascunho(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $rascunho = $_SESSION['secretaria_sessao_rascunho'] ?? null;
        if (!is_array($rascunho)) {
            $_SESSION['mensagem_erro'] = 'Não existe rascunho de sessão aguardando revisão.';
            header('Location: /secretaria');
            exit;
        }

        $sessaoModel = new Sessao();
        $configuracaoLoja = (new ConfiguracaoLoja())->obter();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $autorId = $autorId !== '' ? $autorId : null;
        $sessaoIdExistente = (int) ($rascunho['id'] ?? 0);

        if ($sessaoIdExistente > 0) {
            $ok = $sessaoModel->atualizar(
                $sessaoIdExistente,
                $rascunho,
                $autorId,
                'Atualização confirmada pela Secretaria após revisão final.'
            );
            if (!$ok) {
                $_SESSION['mensagem_erro'] = 'Não foi possível atualizar a sessão revisada.';
                header('Location: /secretaria');
                exit;
            }
            $sessaoId = $sessaoIdExistente;
        } else {
            $sessaoId = $sessaoModel->criar($rascunho, $autorId);

            if (!$sessaoId) {
                $_SESSION['mensagem_erro'] = 'Não foi possível persistir a sessão revisada.';
                header('Location: /secretaria');
                exit;
            }

            $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicação confirmada pela Secretaria após revisão final.');
        }

        $sessaoCriada = $sessaoModel->findById($sessaoId) ?? $rascunho;
        $conteudo = $sessaoModel->comporResumoPublicacao($sessaoCriada, $configuracaoLoja);
        (new PublicacaoSessao())->registrar($sessaoId, 'resumo_proxima_sessao', 'erp', $conteudo, $autorId);

        unset($_SESSION['secretaria_sessao_rascunho']);
        $_SESSION['mensagem_sucesso'] = $sessaoIdExistente > 0
            ? 'Sessão atualizada com sucesso e histórico registrado.'
            : 'Sessão publicada com sucesso e pronta para confirmações.';
        header('Location: /secretaria');
        exit;
    }

    public function cancelarRascunhoSessao(): void
    {
        unset($_SESSION['secretaria_sessao_rascunho']);
        $_SESSION['mensagem_sucesso'] = 'Rascunho da sessão descartado.';
        header('Location: /secretaria');
        exit;
    }

    public function cancelarSessao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessão inválida para cancelamento.';
            header('Location: /secretaria');
            exit;
        }

        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $ok = (new Sessao())->cancelar(
            $sessaoId,
            $autorId !== '' ? $autorId : null,
            'Cancelamento operacional realizado pela Secretaria.'
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Sessão cancelada com sucesso pela Secretaria.'
            : 'Não foi possível cancelar a sessão.';

        header('Location: /secretaria');
        exit;
    }

    public function reabrirSessao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessão inválida para reabertura.';
            header('Location: /secretaria');
            exit;
        }

        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $ok = (new Sessao())->reabrir(
            $sessaoId,
            $autorId !== '' ? $autorId : null,
            'Reabertura operacional realizada pela Secretaria.'
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Sessão reaberta com sucesso pela Secretaria.'
            : 'Não foi possível reabrir a sessão.';

        header('Location: /secretaria');
        exit;
    }

    public function publicarSessao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessão inválida para publicação.';
            header('Location: /secretaria');
            exit;
        }

        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $autorId = $autorId !== '' ? $autorId : null;

        $ok = (new Sessao())->marcarPublicada(
            $sessaoId,
            $autorId,
            'Publicação operacional realizada pela Secretaria.'
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Sessão publicada com sucesso pela Secretaria.'
            : 'Não foi possível publicar a sessão.';

        header('Location: /secretaria');
        exit;
    }

    public function salvarTrabalho(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $trabalhoModel = new TrabalhoSessao();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $trabalhoModel->criar($_POST, $autorId !== '' ? $autorId : null);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Trabalho registrado com sucesso.'
            : 'Não foi possível registrar o trabalho.';

        header('Location: /secretaria');
        exit;
    }

    public function salvarPublicacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $publicacaoModel = new PublicacaoSecretaria();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $publicacaoModel->criar($_POST, $autorId !== '' ? $autorId : null);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Publicação registrada com sucesso.'
            : 'Não foi possível registrar a publicação.';

        header('Location: /secretaria');
        exit;
    }

    public function salvarBalaustre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Selecione a sessão para salvar o balaustre.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $model->salvarPorSessao($sessaoId, $_POST, $autorId !== '' ? $autorId : null);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Balaustre salvo em rascunho.'
            : 'Não foi possível salvar o balaustre.';

        header('Location: /secretaria');
        exit;
    }

    public function marcarBalaustreApto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Balaustre inválido para marcar como apto.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $ok = $model->marcarAptoVotacao($balaustreId, $autorId !== '' ? $autorId : null);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Balaustre apto para votação. O Venerável Mestre já pode abrir a votação.'
            : 'Não foi possível marcar o balaustre como apto.';

        header('Location: /secretaria');
        exit;
    }

    public function abrirVotacaoBalaustre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Balaustre inválido para abrir votação.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $resultado = $model->abrirVotacao($balaustreId, $autorId !== '' ? $autorId : null);
        if (($resultado['ok'] ?? false) === true) {
            $_SESSION['mensagem_sucesso'] = 'Votação aberta com sucesso pelo Venerável Mestre. Votantes aptos: ' . (int) ($resultado['total_votantes'] ?? 0) . '.';
        } else {
            $_SESSION['mensagem_erro'] = (string) ($resultado['erro'] ?? 'Não foi possível abrir votação.');
        }

        header('Location: /secretaria');
        exit;
    }

    public function votarBalaustre(): void
    {
        $destino = $this->resolveRedirectDestino('/secretaria');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $destino);
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        $voto = trim((string) ($_POST['voto'] ?? ''));
        $justificativa = trim((string) ($_POST['justificativa'] ?? ''));
        $obreiroId = (string) ($_SESSION['usuario_id'] ?? '');

        if ($balaustreId <= 0 || $obreiroId === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para registrar voto.';
            header('Location: ' . $destino);
            exit;
        }

        $model = new Balaustre();
        $resultado = $model->registrarVotoPorBalaustre(
            $balaustreId,
            $obreiroId,
            $voto,
            $justificativa !== '' ? $justificativa : null
        );
        if (($resultado['ok'] ?? false) === true) {
            $_SESSION['mensagem_sucesso'] = 'Voto registrado com sucesso.';
        } else {
            $_SESSION['mensagem_erro'] = (string) ($resultado['erro'] ?? 'Não foi possível registrar voto.');
        }

        header('Location: ' . $destino);
        exit;
    }

    public function encerrarVotacaoBalaustre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /secretaria');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Balaustre inválido para encerrar votação.';
            header('Location: /secretaria');
            exit;
        }

        $model = new Balaustre();
        $resultado = $model->encerrarVotacaoPorBalaustre($balaustreId);
        if (($resultado['ok'] ?? false) === true) {
            $_SESSION['mensagem_sucesso'] = 'Votação encerrada. Resultado do balaustre: ' . (string) ($resultado['status'] ?? 'indefinido') . '.';
        } else {
            $_SESSION['mensagem_erro'] = (string) ($resultado['erro'] ?? 'Não foi possível encerrar votação.');
        }

        header('Location: /secretaria');
        exit;
    }

    private function normalizarRascunhoSessao(array $payload): array
    {
        $configLoja = (new ConfiguracaoLoja())->obter();
        $payload['templo_local'] = trim((string) ($payload['templo_local'] ?? '')) !== ''
            ? trim((string) $payload['templo_local'])
            : trim((string) ($configLoja['nome_templo'] ?? ''));
        $payload['natureza_sessao'] = $payload['natureza_sessao'] !== '' ? $payload['natureza_sessao'] : 'ordinaria';
        $payload['formato_sessao'] = $payload['formato_sessao'] !== '' ? $payload['formato_sessao'] : 'templo';
        $payload['finalidade_ritual'] = $payload['finalidade_ritual'] !== '' ? $payload['finalidade_ritual'] : 'economica';
        $payload['gestao_referencia'] = $payload['gestao_referencia'] !== '' ? $payload['gestao_referencia'] : date('Y');
        $payload['sessao_branca'] = (bool) ($payload['sessao_branca'] ?? false);
        $payload['agape_modelo_financeiro'] = in_array(
            $payload['agape_modelo_financeiro'] ?? '',
            Sessao::AGAPE_MODELOS_FINANCEIROS,
            true
        ) ? $payload['agape_modelo_financeiro'] : 'oficial_loja';
        $payload['agape_ativo'] = in_array($payload['agape_modalidade'], ['gratuito', 'pago'], true);
        return $payload;
    }

    public function montarPayloadMiniapp(?int $sessaoId = null): array
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $obreiroModel = new Obreiro();
        $trabalhoModel = new TrabalhoSessao();
        $balaustreModel = new Balaustre();
        $configuracaoLoja = (new ConfiguracaoLoja())->obter();
        $relatorioAnual = (new RelatorioSecretariaAnual())->montar((int) date('Y'));

        $sessoes = $sessaoModel->listarFuturas(8);
        $resumoCadastros = $obreiroModel->obterResumoSecretaria();
        $proximaSessao = $sessaoModel->obterProximaSessao();
        $trabalhosRecentes = $trabalhoModel->listarRecentes(5);
        $balaustresRecentes = $balaustreModel->listarRecentes(5);

        $sessaoFoco = null;
        if ($sessaoId !== null && $sessaoId > 0) {
            $sessaoFoco = $sessaoModel->findById($sessaoId);
        }
        if (!$sessaoFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $confirmados = [];
        $participantesAgape = [];
        $historico = [];
        $balaustreFoco = null;
        if ($sessaoFoco && !empty($sessaoFoco['id'])) {
            $confirmados = $presencaModel->listarConfirmadosPorSessao((int) $sessaoFoco['id']);
            $participantesAgape = $presencaModel->listarParticipantesAgapePorSessao((int) $sessaoFoco['id']);
            $historico = $sessaoModel->listarHistorico((int) $sessaoFoco['id'], 10);
            $balaustreFoco = $balaustreModel->buscarPorSessao((int) $sessaoFoco['id']);
        }

        return [
            'loja' => [
                'nome' => trim((string) ($configuracaoLoja['nome_loja'] ?? 'Loja')),
                'numero' => trim((string) ($configuracaoLoja['numero_loja'] ?? '')),
            ],
            'resumo_cadastros' => $resumoCadastros,
            'proxima_sessao' => $proximaSessao ? $this->mapearSessaoMiniapp($proximaSessao, $sessaoModel) : null,
            'sessao_foco' => $sessaoFoco ? $this->mapearSessaoMiniapp($sessaoFoco, $sessaoModel) : null,
            'sessoes' => array_map(fn (array $sessao): array => $this->mapearSessaoMiniapp($sessao, $sessaoModel), $sessoes),
            'confirmados' => array_map(static function (array $item): array {
                return [
                    'nome' => (string) ($item['nome'] ?? 'Irmao'),
                    'cim' => (string) ($item['cim'] ?? ''),
                ];
            }, $confirmados),
            'participantes_agape' => array_map(static function (array $item): array {
                return [
                    'nome' => (string) ($item['nome'] ?? 'Irmao'),
                    'cim' => (string) ($item['cim'] ?? ''),
                ];
            }, $participantesAgape),
            'trabalhos_recentes' => array_map(static function (array $item): array {
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'sessao_id' => (int) ($item['sessao_id'] ?? 0),
                    'titulo' => (string) ($item['titulo'] ?? ''),
                    'sessao_titulo' => (string) ($item['sessao_titulo'] ?? ''),
                    'status_envio_potencia' => (string) ($item['status_envio_potencia'] ?? ''),
                ];
            }, $trabalhosRecentes),
            'balaustres_recentes' => array_map(static function (array $item): array {
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'sessao_id' => (int) ($item['sessao_id'] ?? 0),
                    'numero_balaustre' => (string) ($item['numero_balaustre'] ?? ''),
                    'sessao_titulo' => (string) ($item['sessao_titulo'] ?? ''),
                    'status' => (string) ($item['status'] ?? ''),
                ];
            }, $balaustresRecentes),
            'balaustre_foco' => $balaustreFoco ? [
                'id' => (int) ($balaustreFoco['id'] ?? 0),
                'sessao_id' => (int) ($balaustreFoco['sessao_id'] ?? 0),
                'numero_balaustre' => (string) ($balaustreFoco['numero_balaustre'] ?? ''),
                'status' => (string) ($balaustreFoco['status'] ?? ''),
            ] : null,
            'relatorio_anual' => [
                'ano' => (int) ($relatorioAnual['ano'] ?? date('Y')),
                'visitantes' => (int) ($relatorioAnual['visitantes']['total'] ?? 0),
                'visitas_externas' => (int) ($relatorioAnual['visitas_externas']['total'] ?? 0),
                'congressos' => (int) ($relatorioAnual['congressos']['total'] ?? 0),
                'palestras' => (int) ($relatorioAnual['palestras']['total'] ?? 0),
                'sessoes' => (int) ($relatorioAnual['sessoes_por_grau']['total'] ?? 0),
            ],
            'historico' => array_map(static function (array $item): array {
                return [
                    'acao' => (string) ($item['acao'] ?? ''),
                    'autor_nome' => (string) ($item['autor_nome'] ?? 'Sistema'),
                    'observacao' => (string) ($item['observacao'] ?? ''),
                    'created_at' => (string) ($item['created_at'] ?? ''),
                ];
            }, $historico),
        ];
    }

    public function salvarSessaoMiniapp(array $input, ?string $autorId = null): array
    {
        $sessaoId = (int) ($input['sessao_id'] ?? 0);
        $payload = [
            'data_hora_inicio' => trim((string) ($input['data_hora_inicio'] ?? '')),
            'data_hora_fim' => trim((string) ($input['data_hora_fim'] ?? '')),
            'grau_sessao' => trim((string) ($input['grau_sessao'] ?? '')),
            'tipo_sessao_principal' => trim((string) ($input['tipo_sessao_principal'] ?? 'economica')),
            'tipo_sessao_subtipo' => trim((string) ($input['tipo_sessao_subtipo'] ?? 'economica_1')),
            'traje_tipo' => trim((string) ($input['traje_tipo'] ?? 'maconico')),
            'agape_modalidade' => trim((string) ($input['agape_modalidade'] ?? 'nao_havera')),
            'agape_modelo_financeiro' => trim((string) ($input['agape_modelo_financeiro'] ?? 'oficial_loja')),
            'agape_valor' => trim((string) ($input['agape_valor'] ?? '')),
            'natureza_sessao' => trim((string) ($input['natureza_sessao'] ?? 'ordinaria')),
            'formato_sessao' => trim((string) ($input['formato_sessao'] ?? 'templo')),
            'finalidade_ritual' => trim((string) ($input['finalidade_ritual'] ?? 'economica')),
            'templo_local' => trim((string) ($input['templo_local'] ?? '')),
            'titulo' => trim((string) ($input['titulo'] ?? '')),
            'ordem_dia' => trim((string) ($input['ordem_dia'] ?? '')),
            'observacao_interna' => trim((string) ($input['observacao_interna'] ?? '')),
            'sessao_branca' => !empty($input['sessao_branca']),
            'sessao_a_campo' => !empty($input['sessao_a_campo']),
            'conta_relatorio_potencia' => array_key_exists('conta_relatorio_potencia', $input)
                ? !empty($input['conta_relatorio_potencia'])
                : true,
            'gestao_referencia' => trim((string) ($input['gestao_referencia'] ?? date('Y'))),
        ];

        if ($payload['titulo'] === '' || $payload['data_hora_inicio'] === '') {
            return ['ok' => false, 'erro' => 'Título e data/hora de início são obrigatórios.'];
        }

        $payload = $this->normalizarRascunhoSessao($payload);
        $sessaoModel = new Sessao();

        if ($sessaoId > 0) {
            $ok = $sessaoModel->atualizar($sessaoId, $payload, $autorId, 'Atualizacao realizada pela Secretaria no miniapp.');
            return ['ok' => $ok, 'sessao_id' => $sessaoId, 'erro' => $ok ? null : 'Não foi possível atualizar a sessão.'];
        }

        $novoId = $sessaoModel->criar($payload, $autorId);
        return ['ok' => $novoId !== null, 'sessao_id' => $novoId, 'erro' => $novoId !== null ? null : 'Não foi possível criar a sessão.'];
    }

    private function mapearSessaoMiniapp(array $sessao, Sessao $sessaoModel): array
    {
        return [
            'id' => (int) ($sessao['id'] ?? 0),
            'titulo' => (string) ($sessao['titulo'] ?? ''),
            'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
            'status' => (string) ($sessao['status'] ?? ''),
            'tipo_descricao' => $sessaoModel->obterDescricaoTipoSessao($sessao),
            'agape_descricao' => $sessaoModel->obterDescricaoAgape($sessao),
            'total_confirmados' => (int) ($sessao['total_confirmados'] ?? 0),
            'total_ausentes' => (int) ($sessao['total_ausentes'] ?? 0),
            'total_agape' => (int) ($sessao['total_agape'] ?? 0),
        ];
    }

    public function salvarTrabalhoMiniapp(array $input, ?string $autorId = null): array
    {
        $payload = [
            'sessao_id' => (int) ($input['sessao_id'] ?? 0),
            'tipo_trabalho' => trim((string) ($input['tipo_trabalho'] ?? 'peca_arquitetura')),
            'titulo' => trim((string) ($input['titulo'] ?? '')),
            'autor_nome_livre' => trim((string) ($input['autor_nome_livre'] ?? '')),
            'status_envio_potencia' => trim((string) ($input['status_envio_potencia'] ?? 'pendente')),
            'observacao' => trim((string) ($input['observacao'] ?? '')),
        ];

        if ($payload['sessao_id'] <= 0 || $payload['titulo'] === '') {
            return ['ok' => false, 'erro' => 'Sessão e título são obrigatórios para registrar o trabalho.'];
        }

        $ok = (new TrabalhoSessao())->criar($payload, $autorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar o trabalho.'];
    }

    public function salvarBalaustreMiniapp(array $input, ?string $autorId = null): array
    {
        $sessaoId = (int) ($input['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            return ['ok' => false, 'erro' => 'Selecione a sessão para registrar o balaustre.'];
        }

        $payload = [
            'numero_balaustre' => trim((string) ($input['numero_balaustre'] ?? '')),
            'texto_final' => trim((string) ($input['texto_final'] ?? '')),
            'observacoes_secretaria' => trim((string) ($input['observacoes_secretaria'] ?? '')),
            'dados_capturados' => trim((string) ($input['dados_capturados'] ?? '')),
        ];

        $ok = (new Balaustre())->salvarPorSessao($sessaoId, $payload, $autorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível salvar o balaustre.'];
    }
}
