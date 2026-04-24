<?php

namespace App\Controllers;

use App\Models\Acervo;
use App\Models\Cargo;
use App\Models\Obreiro;
use App\Models\SegundoVigilanteAcompanhamento;
use App\Models\TrilhaCompanheiro;

class SegundoVigilanteController
{
    public function index(): void
    {
        $obreiroModel = new Obreiro();
        $cargoModel = new Cargo();
        $trilhaModel = new TrilhaCompanheiro();
        $acompanhamentoModel = new SegundoVigilanteAcompanhamento();

        $companheiros = array_values(array_filter(
            $obreiroModel->getAllAtivos(),
            static fn (array $obreiro): bool => strtolower(trim((string) ($obreiro['grau'] ?? ''))) === 'companheiro'
        ));

        usort($companheiros, static function (array $a, array $b): int {
            $nomeA = (string) ($a['nome_historico'] ?? $a['nome'] ?? '');
            $nomeB = (string) ($b['nome_historico'] ?? $b['nome'] ?? '');
            return strcasecmp($nomeA, $nomeB);
        });

        $companheiroIds = array_values(array_filter(array_map(
            static fn (array $companheiro): string => (string) ($companheiro['id'] ?? ''),
            $companheiros
        )));

        $trilhaDisponivel = $trilhaModel->trilhaDisponivel();
        $avisoInfra = null;
        if ($trilhaDisponivel) {
            $trilhaModel->garantirTrilhaBaseParaCompanheiros($companheiroIds);
            $resumoAtualPorCompanheiro = $trilhaModel->listarResumoAtualPorCompanheiroIds($companheiroIds);
        } else {
            $resumoAtualPorCompanheiro = [];
            $avisoInfra = 'A trilha individual dos Companheiros ainda não foi criada no banco. Execute: php scripts/run_migration_012.php e php scripts/run_migration_013.php.';
        }

        foreach ($companheiros as &$companheiro) {
            $resumoAtual = $resumoAtualPorCompanheiro[(string) ($companheiro['id'] ?? '')] ?? null;
            $companheiro['trilha_etapa_atual'] = (int) ($resumoAtual['etapa_ordem'] ?? 1);
            $companheiro['trilha_status_atual'] = (string) ($resumoAtual['status'] ?? 'nao_iniciado');
            $companheiro['trilha_titulo_atual'] = (string) ($resumoAtual['titulo_etapa'] ?? TrilhaCompanheiro::etapas()[1]);
            $companheiro['trilha_proxima_acao'] = $this->resolverProximaAcao(
                $companheiro['trilha_etapa_atual'],
                $companheiro['trilha_status_atual']
            );
        }
        unset($companheiro);

        $nominata = $cargoModel->listarResumoCargos();
        $titularCargo = null;
        foreach ($nominata as $item) {
            if ((string) ($item['codigo'] ?? '') === 'SEGUNDO_VIGILANTE') {
                $titularCargo = $item;
                break;
            }
        }

        $resumo = [
            'companheiros_ativos' => count($companheiros),
            'etapa_inicial' => count(array_filter($companheiros, static fn (array $companheiro): bool => (int) ($companheiro['trilha_etapa_atual'] ?? 0) === 1)),
            'trabalhos_aguardando_recebimento' => $trilhaDisponivel ? $trilhaModel->contarPorStatus($companheiroIds, 'aguardando_entrega') : 0,
            'aptos_docencia' => $trilhaDisponivel ? $trilhaModel->contarPorStatus($companheiroIds, 'apto_para_certificado') : 0,
            'aptos_exaltacao' => $trilhaDisponivel ? $trilhaModel->contarPorStatus($companheiroIds, 'apto_para_exaltacao') : 0,
            'leituras_sugeridas' => count(array_filter($companheiroIds, static function (string $companheiroId) use ($acompanhamentoModel): bool {
                $acompanhamento = $acompanhamentoModel->obterPorCompanheiro($companheiroId);
                return !empty($acompanhamento['leitura_acervo_id']) || !empty($acompanhamento['leitura_observacao']);
            })),
        ];

        $trilhaEstudo = TrilhaCompanheiro::etapas();

        require_once __DIR__ . '/../Views/segundo_vigilante/index.php';
    }

    public function companheiro(?string $companheiroId = null, bool $somenteProprio = false): void
    {
        $companheiroId = trim((string) $companheiroId);
        if ($companheiroId === '') {
            http_response_code(404);
            echo 'Companheiro não informado.';
            return;
        }

        $obreiroModel = new Obreiro();
        $trilhaModel = new TrilhaCompanheiro();
        $acervoModel = new Acervo();
        $acompanhamentoModel = new SegundoVigilanteAcompanhamento();

        $companheiro = $obreiroModel->findById($companheiroId);
        if (!$companheiro || strtolower(trim((string) ($companheiro['grau'] ?? ''))) !== 'companheiro') {
            http_response_code(404);
            echo 'Companheiro não encontrado.';
            return;
        }

        $trilhaDisponivel = $trilhaModel->trilhaDisponivel();
        $avisoInfra = null;

        if ($trilhaDisponivel) {
            $trilhaModel->garantirTrilhaBaseParaCompanheiros([$companheiroId]);
            $resumoTrilha = $trilhaModel->obterResumoDoCompanheiro($companheiroId);
        } else {
            $resumoTrilha = $this->montarResumoFallback();
            $avisoInfra = 'A trilha individual dos Companheiros ainda não foi criada no banco. Execute: php scripts/run_migration_012.php e php scripts/run_migration_013.php.';
        }

        if ($resumoTrilha === null) {
            $resumoTrilha = $this->montarResumoFallback();
        }

        $etapas = $resumoTrilha['etapas'] ?? [];
        $etapaAtual = $resumoTrilha['etapa_atual'] ?? null;
        $statusDisponiveis = [
            'nao_iniciado' => 'Não iniciado',
            'disponibilizado' => 'Disponibilizado',
            'aguardando_entrega' => 'Aguardando entrega',
            'recebido' => 'Recebido',
            'revisado' => 'Revisado',
            'concluido' => 'Concluído',
            'apto_para_certificado' => 'Apto para certificado',
            'certificado_solicitado' => 'Certificado solicitado',
            'apto_para_exaltacao' => 'Apto para exaltação',
            'exaltacao_recomendada' => 'Exaltação recomendada',
        ];

        $acoesRapidasPorEtapa = [];
        foreach ($etapas as $etapaItem) {
            $ordem = (int) ($etapaItem['etapa_ordem'] ?? 0);
            $statusEtapa = (string) ($etapaItem['status'] ?? 'nao_iniciado');
            $acoesRapidasPorEtapa[$ordem] = $this->montarAcoesRapidas($statusEtapa, $ordem);
        }
        $acompanhamento = $acompanhamentoModel->obterPorCompanheiro($companheiroId);
        $historicoFormativo = $acompanhamentoModel->listarHistoricoFormativo($companheiroId);
        $leiturasDisponiveis = array_values(array_filter(
            $acervoModel->listarTodos(),
            static fn (array $item): bool => in_array((string) ($item['grau_recomendado'] ?? 'Livre'), ['Livre', 'Companheiro', 'Mestre'], true)
        ));

        $tituloPagina = $somenteProprio ? 'Meu acompanhamento' : 'Acompanhamento do Companheiro';

        require_once __DIR__ . '/../Views/segundo_vigilante/companheiro.php';
    }

    public function atualizarEtapa(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /segundo-vigilante');
            exit;
        }

        $companheiroId = trim((string) ($_POST['companheiro_id'] ?? ''));
        $etapaOrdem = (int) ($_POST['etapa_ordem'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_vigilante'] ?? ''));
        $retorno = '/segundo-vigilante/companheiro?id=' . urlencode($companheiroId);

        if ($companheiroId === '' || $etapaOrdem <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Informe companheiro, etapa e status para atualizar a trilha.';
            header('Location: ' . $retorno);
            exit;
        }

        $trilhaModel = new TrilhaCompanheiro();
        if (!$trilhaModel->trilhaDisponivel()) {
            $_SESSION['mensagem_erro'] = 'A trilha ainda não foi criada no banco. Execute as migrations 012 e 013.';
            header('Location: ' . $retorno);
            exit;
        }

        $ok = $trilhaModel->atualizarEtapa(
            $companheiroId,
            $etapaOrdem,
            $status,
            $observacao !== '' ? $observacao : null,
            (string) ($_SESSION['usuario_id'] ?? '')
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Etapa atualizada com sucesso.'
            : 'Não foi possível atualizar a etapa da trilha.';

        header('Location: ' . $retorno);
        exit;
    }

    public function acaoRapidaEtapa(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /segundo-vigilante');
            exit;
        }

        $companheiroId = trim((string) ($_POST['companheiro_id'] ?? ''));
        $etapaOrdem = (int) ($_POST['etapa_ordem'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_vigilante'] ?? ''));
        $retorno = '/segundo-vigilante/companheiro?id=' . urlencode($companheiroId);

        if ($companheiroId === '' || $etapaOrdem <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Não foi possível executar a ação rápida da trilha.';
            header('Location: ' . $retorno);
            exit;
        }

        $trilhaModel = new TrilhaCompanheiro();
        if (!$trilhaModel->trilhaDisponivel()) {
            $_SESSION['mensagem_erro'] = 'A trilha ainda não foi criada no banco. Execute as migrations 012 e 013.';
            header('Location: ' . $retorno);
            exit;
        }

        $ok = $trilhaModel->atualizarEtapa(
            $companheiroId,
            $etapaOrdem,
            $status,
            $observacao !== '' ? $observacao : null,
            (string) ($_SESSION['usuario_id'] ?? '')
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Ação rápida aplicada com sucesso.'
            : 'Não foi possível aplicar a ação rápida.';

        header('Location: ' . $retorno . '#etapa-' . $etapaOrdem);
        exit;
    }

    public function salvarLeituraSugerida(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /segundo-vigilante');
            exit;
        }

        $companheiroId = trim((string) ($_POST['companheiro_id'] ?? ''));
        $retorno = '/segundo-vigilante/companheiro?id=' . urlencode($companheiroId);
        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $observacao = trim((string) ($_POST['observacao_leitura'] ?? ''));
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($companheiroId === '') {
            $_SESSION['mensagem_erro'] = 'Companheiro inválido para registrar leitura sugerida.';
            header('Location: /segundo-vigilante');
            exit;
        }

        $ok = (new SegundoVigilanteAcompanhamento())->salvarLeituraSugerida(
            $companheiroId,
            $acervoId > 0 ? $acervoId : null,
            $observacao !== '' ? $observacao : null,
            $autorId !== '' ? $autorId : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Leitura sugerida registrada com sucesso.'
            : 'Não foi possível registrar a leitura sugerida.';
        header('Location: ' . $retorno . '#leitura-sugerida');
        exit;
    }

    public function solicitarCertificado(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /segundo-vigilante');
            exit;
        }

        $companheiroId = trim((string) ($_POST['companheiro_id'] ?? ''));
        $retorno = '/segundo-vigilante/companheiro?id=' . urlencode($companheiroId);
        $observacao = trim((string) ($_POST['observacao_certificado'] ?? ''));
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($companheiroId === '') {
            $_SESSION['mensagem_erro'] = 'Companheiro inválido para solicitar certificado.';
            header('Location: /segundo-vigilante');
            exit;
        }

        $trilhaModel = new TrilhaCompanheiro();
        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->atualizarEtapa($companheiroId, 7, 'certificado_solicitado', $observacao !== '' ? $observacao : null, $autorId !== '' ? $autorId : null);
        }

        $ok = (new SegundoVigilanteAcompanhamento())->solicitarCertificado(
            $companheiroId,
            $observacao !== '' ? $observacao : null,
            $autorId !== '' ? $autorId : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Solicitação formal de certificado registrada.'
            : 'Não foi possível registrar a solicitação do certificado.';
        header('Location: ' . $retorno . '#certificado');
        exit;
    }

    public function recomendarExaltacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /segundo-vigilante');
            exit;
        }

        $companheiroId = trim((string) ($_POST['companheiro_id'] ?? ''));
        $retorno = '/segundo-vigilante/companheiro?id=' . urlencode($companheiroId);
        $observacao = trim((string) ($_POST['observacao_exaltacao'] ?? ''));
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($companheiroId === '') {
            $_SESSION['mensagem_erro'] = 'Companheiro inválido para recomendar exaltação.';
            header('Location: /segundo-vigilante');
            exit;
        }

        $trilhaModel = new TrilhaCompanheiro();
        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->atualizarEtapa($companheiroId, 8, 'exaltacao_recomendada', $observacao !== '' ? $observacao : null, $autorId !== '' ? $autorId : null);
        }

        $ok = (new SegundoVigilanteAcompanhamento())->recomendarExaltacao(
            $companheiroId,
            $observacao !== '' ? $observacao : null,
            $autorId !== '' ? $autorId : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Recomendacao formal de exaltacao registrada.'
            : 'Não foi possível registrar a recomendação de exaltação.';
        header('Location: ' . $retorno . '#exaltacao');
        exit;
    }

    public function montarPayloadMiniapp(string $companheiroId): ?array
    {
        $obreiroModel = new Obreiro();
        $trilhaModel = new TrilhaCompanheiro();
        $acompanhamentoModel = new SegundoVigilanteAcompanhamento();

        $companheiro = $obreiroModel->findById($companheiroId);
        if (!$companheiro || strtolower(trim((string) ($companheiro['grau'] ?? ''))) !== 'companheiro') {
            return null;
        }

        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->garantirTrilhaBaseParaCompanheiros([$companheiroId]);
            $resumoTrilha = $trilhaModel->obterResumoDoCompanheiro($companheiroId);
            if ($resumoTrilha === null) {
                $resumoTrilha = $this->montarResumoFallback();
            }
        } else {
            $resumoTrilha = $this->montarResumoFallback();
        }

        $etapaAtual = $resumoTrilha['etapa_atual'] ?? null;
        $acompanhamento = $acompanhamentoModel->obterPorCompanheiro($companheiroId);
        $historicoFormativo = $acompanhamentoModel->listarHistoricoFormativo($companheiroId);

        return [
            'companheiro' => [
                'id' => (string) ($companheiro['id'] ?? ''),
                'nome' => (string) ($companheiro['nome_historico'] ?? $companheiro['nome'] ?? 'Companheiro'),
                'cim' => (string) ($companheiro['cim'] ?? ''),
                'data_elevacao' => (string) ($companheiro['data_elevacao'] ?? ''),
            ],
            'resumo' => [
                'total_etapas' => (int) ($resumoTrilha['total_etapas'] ?? 0),
                'total_concluidas' => (int) ($resumoTrilha['total_concluidas'] ?? 0),
                'percentual_conclusao' => (int) ($resumoTrilha['percentual_conclusao'] ?? 0),
                'etapa_atual' => $etapaAtual ? [
                    'ordem' => (int) ($etapaAtual['etapa_ordem'] ?? 0),
                    'titulo' => (string) ($etapaAtual['titulo_etapa'] ?? ''),
                    'status' => (string) ($etapaAtual['status'] ?? ''),
                ] : null,
            ],
            'etapas' => array_map(static function (array $etapa): array {
                return [
                    'ordem' => (int) ($etapa['etapa_ordem'] ?? 0),
                    'titulo' => (string) ($etapa['titulo_etapa'] ?? ''),
                    'status' => (string) ($etapa['status'] ?? ''),
                    'data_disponibilizacao' => (string) ($etapa['data_disponibilizacao'] ?? ''),
                    'data_entrega' => (string) ($etapa['data_entrega'] ?? ''),
                    'data_revisao' => (string) ($etapa['data_revisao'] ?? ''),
                    'observacao_vigilante' => (string) ($etapa['observacao_vigilante'] ?? ''),
                ];
            }, $resumoTrilha['etapas'] ?? []),
            'leitura_sugerida' => [
                'acervo_id' => (int) ($acompanhamento['leitura_acervo_id'] ?? 0),
                'titulo' => (string) ($acompanhamento['leitura_titulo'] ?? $acompanhamento['leitura_titulo_snapshot'] ?? ''),
                'autor' => (string) ($acompanhamento['leitura_autor'] ?? ''),
                'observacao' => (string) ($acompanhamento['leitura_observacao'] ?? ''),
            ],
            'certificado' => [
                'status' => (string) ($acompanhamento['certificado_status'] ?? 'nao_solicitado'),
                'observacao' => (string) ($acompanhamento['certificado_observacao'] ?? ''),
                'solicitado_em' => (string) ($acompanhamento['certificado_solicitado_em'] ?? ''),
            ],
            'exaltacao' => [
                'status' => (string) ($acompanhamento['exaltacao_status'] ?? 'nao_recomendada'),
                'observacao' => (string) ($acompanhamento['exaltacao_observacao'] ?? ''),
                'recomendada_em' => (string) ($acompanhamento['exaltacao_recomendada_em'] ?? ''),
            ],
            'historico_formativo' => $historicoFormativo,
        ];
    }

    public function montarPayloadPainelMiniapp(?string $companheiroId = null): array
    {
        $obreiroModel = new Obreiro();
        $acervoModel = new Acervo();

        $companheiros = array_values(array_filter(
            $obreiroModel->getAllAtivos(),
            static fn (array $obreiro): bool => strtolower(trim((string) ($obreiro['grau'] ?? ''))) === 'companheiro'
        ));
        usort($companheiros, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['nome_historico'] ?? $a['nome'] ?? ''), (string) ($b['nome_historico'] ?? $b['nome'] ?? ''));
        });

        $companheiroFocoId = trim((string) ($companheiroId ?? ''));
        if ($companheiroFocoId === '' && isset($companheiros[0]['id'])) {
            $companheiroFocoId = (string) $companheiros[0]['id'];
        }

        return [
            'companheiros' => array_map(static function (array $companheiro): array {
                return [
                    'id' => (string) ($companheiro['id'] ?? ''),
                    'nome' => (string) ($companheiro['nome_historico'] ?? $companheiro['nome'] ?? 'Companheiro'),
                    'cim' => (string) ($companheiro['cim'] ?? ''),
                ];
            }, $companheiros),
            'companheiro_foco' => $this->montarPayloadMiniapp($companheiroFocoId),
            'leituras_disponiveis' => array_map(static function (array $item): array {
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'titulo' => (string) ($item['titulo'] ?? ''),
                    'autor' => (string) ($item['autor'] ?? ''),
                ];
            }, array_values(array_filter(
                $acervoModel->listarTodos(),
                static fn (array $item): bool => in_array((string) ($item['grau_recomendado'] ?? 'Livre'), ['Livre', 'Companheiro', 'Mestre'], true)
            ))),
        ];
    }

    public function salvarLeituraSugeridaMiniapp(string $companheiroId, ?int $acervoId, ?string $observacao, ?string $autorId = null): array
    {
        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return ['ok' => false, 'erro' => 'Companheiro inválido para registrar leitura sugerida.'];
        }

        $ok = (new SegundoVigilanteAcompanhamento())->salvarLeituraSugerida(
            $companheiroId,
            $acervoId !== null && $acervoId > 0 ? $acervoId : null,
            $observacao,
            $autorId
        );

        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a leitura sugerida.'];
    }

    public function solicitarCertificadoMiniapp(string $companheiroId, ?string $observacao, ?string $autorId = null): array
    {
        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return ['ok' => false, 'erro' => 'Companheiro inválido para solicitar certificado.'];
        }

        $trilhaModel = new TrilhaCompanheiro();
        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->atualizarEtapa($companheiroId, 7, 'certificado_solicitado', $observacao, $autorId);
        }

        $ok = (new SegundoVigilanteAcompanhamento())->solicitarCertificado($companheiroId, $observacao, $autorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a solicitação do certificado.'];
    }

    public function recomendarExaltacaoMiniapp(string $companheiroId, ?string $observacao, ?string $autorId = null): array
    {
        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return ['ok' => false, 'erro' => 'Companheiro inválido para recomendar exaltação.'];
        }

        $trilhaModel = new TrilhaCompanheiro();
        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->atualizarEtapa($companheiroId, 8, 'exaltacao_recomendada', $observacao, $autorId);
        }

        $ok = (new SegundoVigilanteAcompanhamento())->recomendarExaltacao($companheiroId, $observacao, $autorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a recomendação de exaltação.'];
    }

    public function atualizarEtapaMiniapp(string $companheiroId, int $etapaOrdem, string $status, ?string $observacao = null, ?string $autorId = null): array
    {
        $companheiroId = trim($companheiroId);
        if ($companheiroId === '' || $etapaOrdem <= 0 || trim($status) === '') {
            return ['ok' => false, 'erro' => 'Dados insuficientes para atualizar a trilha.'];
        }

        $trilhaModel = new TrilhaCompanheiro();
        if (!$trilhaModel->trilhaDisponivel()) {
            return ['ok' => false, 'erro' => 'A trilha ainda não foi criada no banco.'];
        }

        $ok = $trilhaModel->atualizarEtapa($companheiroId, $etapaOrdem, trim($status), $observacao, $autorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar a etapa da trilha.'];
    }

    private function resolverProximaAcao(int $etapa, string $status): string
    {
        return match ($status) {
            'nao_iniciado' => 'Passar a etapa ao Companheiro',
            'disponibilizado' => 'Aguardar início do trabalho',
            'aguardando_entrega' => 'Receber o trabalho do Companheiro',
            'recebido' => 'Revisar o trabalho recebido',
            'revisado' => 'Concluir a etapa e orientar o próximo passo',
            'concluido' => $etapa >= 8 ? 'Avaliar recomendação de exaltação' : 'Liberar a próxima etapa',
            'apto_para_certificado' => 'Solicitar o certificado de docência',
            'certificado_solicitado' => 'Aguardar emissão do certificado',
            'apto_para_exaltacao' => 'Avaliar recomendação de exaltação',
            'exaltacao_recomendada' => 'Encaminhar ao Venerável Mestre',
            default => 'Acompanhar o progresso da etapa',
        };
    }

    private function montarAcoesRapidas(string $status, int $etapa): array
    {
        if ($status === 'nao_iniciado') {
            return [
                ['status' => 'disponibilizado', 'label' => 'Passar etapa'],
                ['status' => 'aguardando_entrega', 'label' => 'Aguardar entrega'],
            ];
        }
        if ($status === 'disponibilizado') {
            return [
                ['status' => 'aguardando_entrega', 'label' => 'Marcar aguardando entrega'],
                ['status' => 'recebido', 'label' => 'Marcar recebido'],
            ];
        }
        if ($status === 'aguardando_entrega') {
            return [['status' => 'recebido', 'label' => 'Receber trabalho']];
        }
        if ($status === 'recebido') {
            return [['status' => 'revisado', 'label' => 'Revisar trabalho']];
        }
        if ($status === 'revisado') {
            if ($etapa === 7) {
                return [['status' => 'apto_para_certificado', 'label' => 'Marcar apto para certificado']];
            }
            if ($etapa === 8) {
                return [['status' => 'apto_para_exaltacao', 'label' => 'Marcar apto para exaltação']];
            }
            return [['status' => 'concluido', 'label' => 'Concluir etapa']];
        }
        if ($status === 'concluido') {
            if ($etapa === 7) {
                return [['status' => 'apto_para_certificado', 'label' => 'Marcar apto para certificado']];
            }
            if ($etapa === 8) {
                return [['status' => 'apto_para_exaltacao', 'label' => 'Marcar apto para exaltação']];
            }
            return [['status' => 'disponibilizado', 'label' => 'Reabrir etapa']];
        }
        if ($status === 'apto_para_certificado') {
            return [['status' => 'certificado_solicitado', 'label' => 'Solicitar certificado']];
        }
        if ($status === 'apto_para_exaltacao') {
            return [['status' => 'exaltacao_recomendada', 'label' => 'Recomendar exaltação']];
        }
        return [];
    }

    private function montarResumoFallback(): array
    {
        $etapas = [];
        foreach (TrilhaCompanheiro::etapas() as $ordem => $titulo) {
            $etapas[] = [
                'id' => null,
                'companheiro_id' => null,
                'etapa_ordem' => $ordem,
                'titulo_etapa' => $titulo,
                'status' => 'nao_iniciado',
                'data_disponibilizacao' => null,
                'data_entrega' => null,
                'data_revisao' => null,
                'observacao_vigilante' => null,
                'arquivo_entrega' => null,
                'revisado_por' => null,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        return [
            'etapas' => $etapas,
            'etapa_atual' => $etapas[0] ?? null,
            'total_etapas' => count($etapas),
            'total_concluidas' => 0,
            'percentual_conclusao' => 0,
        ];
    }
}
