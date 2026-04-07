<?php

namespace App\Controllers;

use App\Models\Cargo;
use App\Models\Obreiro;
use App\Models\TrilhaAprendiz;

class PrimeiroVigilanteController
{
    public function index(): void
    {
        $obreiroModel = new Obreiro();
        $cargoModel = new Cargo();
        $trilhaModel = new TrilhaAprendiz();

        $aprendizes = array_values(array_filter(
            $obreiroModel->getAllAtivos(),
            static fn (array $obreiro): bool => strtolower(trim((string) ($obreiro['grau'] ?? ''))) === 'aprendiz'
        ));

        usort($aprendizes, static function (array $a, array $b): int {
            $nomeA = (string) ($a['nome_historico'] ?? $a['nome'] ?? '');
            $nomeB = (string) ($b['nome_historico'] ?? $b['nome'] ?? '');
            return strcasecmp($nomeA, $nomeB);
        });

        $aprendizIds = array_values(array_filter(array_map(
            static fn (array $aprendiz): string => (string) ($aprendiz['id'] ?? ''),
            $aprendizes
        )));

        $trilhaDisponivel = $trilhaModel->trilhaDisponivel();
        $avisoInfra = null;
        if ($trilhaDisponivel) {
            $trilhaModel->garantirTrilhaBaseParaAprendizes($aprendizIds);
            $resumoAtualPorAprendiz = $trilhaModel->listarResumoAtualPorAprendizIds($aprendizIds);
        } else {
            $resumoAtualPorAprendiz = [];
            $avisoInfra = 'A trilha individual ainda não foi criada no banco. Execute: php scripts/run_migration_009.php e php scripts/run_migration_010.php.';
        }

        foreach ($aprendizes as &$aprendiz) {
            $resumoAtual = $resumoAtualPorAprendiz[(string) ($aprendiz['id'] ?? '')] ?? null;
            $aprendiz['trilha_etapa_atual'] = (int) ($resumoAtual['etapa_ordem'] ?? 1);
            $aprendiz['trilha_status_atual'] = (string) ($resumoAtual['status'] ?? 'nao_iniciado');
            $aprendiz['trilha_titulo_atual'] = (string) ($resumoAtual['titulo_etapa'] ?? TrilhaAprendiz::etapas()[1]);
            $aprendiz['trilha_proxima_acao'] = $this->resolverProximaAcao(
                $aprendiz['trilha_etapa_atual'],
                $aprendiz['trilha_status_atual']
            );
        }
        unset($aprendiz);

        $nominata = $cargoModel->listarResumoCargos();
        $titularCargo = null;
        foreach ($nominata as $item) {
            if ((string) ($item['codigo'] ?? '') === 'PRIMEIRO_VIGILANTE') {
                $titularCargo = $item;
                break;
            }
        }

        $resumo = [
            'aprendizes_ativos' => count($aprendizes),
            'etapa_inicial' => count(array_filter($aprendizes, static fn (array $aprendiz): bool => (int) ($aprendiz['trilha_etapa_atual'] ?? 0) === 1)),
            'trabalhos_aguardando_recebimento' => $trilhaDisponivel ? $trilhaModel->contarPorStatus($aprendizIds, 'aguardando_entrega') : 0,
            'aptos_certificado' => $trilhaDisponivel ? $trilhaModel->contarPorStatus($aprendizIds, 'apto_para_certificado') : 0,
        ];

        $trilhaEstudo = TrilhaAprendiz::etapas();

        require_once __DIR__ . '/../Views/primeiro_vigilante/index.php';
    }

    public function aprendiz(?string $aprendizId = null, bool $somenteProprio = false): void
    {
        $aprendizId = trim((string) $aprendizId);
        if ($aprendizId === '') {
            http_response_code(404);
            echo 'Aprendiz não informado.';
            return;
        }

        $obreiroModel = new Obreiro();
        $trilhaModel = new TrilhaAprendiz();

        $aprendiz = $obreiroModel->findById($aprendizId);
        if (!$aprendiz || strtolower(trim((string) ($aprendiz['grau'] ?? ''))) !== 'aprendiz') {
            http_response_code(404);
            echo 'Aprendiz não encontrado.';
            return;
        }

        $trilhaDisponivel = $trilhaModel->trilhaDisponivel();
        $avisoInfra = null;

        if ($trilhaDisponivel) {
            $trilhaModel->garantirTrilhaBaseParaAprendizes([$aprendizId]);
            $resumoTrilha = $trilhaModel->obterResumoDoAprendiz($aprendizId);
        } else {
            $resumoTrilha = $this->montarResumoFallback();
            $avisoInfra = 'A trilha individual ainda não foi criada no banco. Execute: php scripts/run_migration_009.php e php scripts/run_migration_010.php.';
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
        ];
        $acoesRapidasPorEtapa = [];
        foreach ($etapas as $etapaItem) {
            $ordem = (int) ($etapaItem['etapa_ordem'] ?? 0);
            $statusEtapa = (string) ($etapaItem['status'] ?? 'nao_iniciado');
            $acoesRapidasPorEtapa[$ordem] = $this->montarAcoesRapidas($statusEtapa, $ordem);
        }

        $tituloPagina = $somenteProprio ? 'Meu acompanhamento' : 'Acompanhamento do Aprendiz';

        require_once __DIR__ . '/../Views/primeiro_vigilante/aprendiz.php';
    }

    public function atualizarEtapa(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /primeiro-vigilante');
            exit;
        }

        $aprendizId = trim((string) ($_POST['aprendiz_id'] ?? ''));
        $etapaOrdem = (int) ($_POST['etapa_ordem'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_vigilante'] ?? ''));
        $retorno = '/primeiro-vigilante/aprendiz?id=' . urlencode($aprendizId);

        if ($aprendizId === '' || $etapaOrdem <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Informe aprendiz, etapa e status para atualizar a trilha.';
            header('Location: ' . $retorno);
            exit;
        }

        $trilhaModel = new TrilhaAprendiz();
        if (!$trilhaModel->trilhaDisponivel()) {
            $_SESSION['mensagem_erro'] = 'A trilha ainda não foi criada no banco. Execute as migrations 009 e 010.';
            header('Location: ' . $retorno);
            exit;
        }

        $ok = $trilhaModel->atualizarEtapa(
            $aprendizId,
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
            header('Location: /primeiro-vigilante');
            exit;
        }

        $aprendizId = trim((string) ($_POST['aprendiz_id'] ?? ''));
        $etapaOrdem = (int) ($_POST['etapa_ordem'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_vigilante'] ?? ''));
        $retorno = '/primeiro-vigilante/aprendiz?id=' . urlencode($aprendizId);

        if ($aprendizId === '' || $etapaOrdem <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Não foi possível executar a ação rápida da trilha.';
            header('Location: ' . $retorno);
            exit;
        }

        $trilhaModel = new TrilhaAprendiz();
        if (!$trilhaModel->trilhaDisponivel()) {
            $_SESSION['mensagem_erro'] = 'A trilha ainda não foi criada no banco. Execute as migrations 009 e 010.';
            header('Location: ' . $retorno);
            exit;
        }

        $ok = $trilhaModel->atualizarEtapa(
            $aprendizId,
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

    private function resolverProximaAcao(int $etapa, string $status): string
    {
        return match ($status) {
            'nao_iniciado' => 'Passar a etapa ao Aprendiz',
            'disponibilizado' => 'Aguardar início do trabalho',
            'aguardando_entrega' => 'Receber o trabalho do Aprendiz',
            'recebido' => 'Revisar o trabalho recebido',
            'revisado' => 'Concluir a etapa e orientar o próximo passo',
            'concluido' => $etapa >= 8 ? 'Avaliar solicitação do certificado' : 'Liberar a próxima etapa',
            'apto_para_certificado' => 'Solicitar o certificado de conclusão',
            'certificado_solicitado' => 'Aguardar emissão do certificado',
            default => 'Acompanhar o progresso da etapa',
        };
    }

    private function montarAcoesRapidas(string $status, int $etapa): array
    {
        return match ($status) {
            'nao_iniciado' => [
                ['status' => 'disponibilizado', 'label' => 'Passar etapa'],
                ['status' => 'aguardando_entrega', 'label' => 'Aguardar entrega'],
            ],
            'disponibilizado' => [
                ['status' => 'aguardando_entrega', 'label' => 'Marcar aguardando entrega'],
                ['status' => 'recebido', 'label' => 'Marcar recebido'],
            ],
            'aguardando_entrega' => [
                ['status' => 'recebido', 'label' => 'Receber trabalho'],
            ],
            'recebido' => [
                ['status' => 'revisado', 'label' => 'Revisar trabalho'],
            ],
            'revisado' => [
                ['status' => $etapa === 8 ? 'apto_para_certificado' : 'concluido', 'label' => $etapa === 8 ? 'Marcar apto para certificado' : 'Concluir etapa'],
            ],
            'concluido' => $etapa === 8
                ? [['status' => 'apto_para_certificado', 'label' => 'Marcar apto para certificado']]
                : [['status' => 'disponibilizado', 'label' => 'Reabrir etapa']],
            'apto_para_certificado' => [
                ['status' => 'certificado_solicitado', 'label' => 'Solicitar certificado'],
            ],
            'certificado_solicitado' => [],
            default => [],
        };
    }

    public function montarPayloadMiniapp(string $aprendizId): ?array
    {
        $obreiroModel = new Obreiro();
        $trilhaModel = new TrilhaAprendiz();

        $aprendiz = $obreiroModel->findById($aprendizId);
        if (!$aprendiz || strtolower(trim((string) ($aprendiz['grau'] ?? ''))) !== 'aprendiz') {
            return null;
        }

        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->garantirTrilhaBaseParaAprendizes([$aprendizId]);
            $resumoTrilha = $trilhaModel->obterResumoDoAprendiz($aprendizId);
            if ($resumoTrilha === null) {
                $resumoTrilha = $this->montarResumoFallback();
            }
        } else {
            $resumoTrilha = $this->montarResumoFallback();
        }

        $etapaAtual = $resumoTrilha['etapa_atual'] ?? null;

        return [
            'aprendiz' => [
                'id' => (string) ($aprendiz['id'] ?? ''),
                'nome' => (string) ($aprendiz['nome_historico'] ?? $aprendiz['nome'] ?? 'Aprendiz'),
                'cim' => (string) ($aprendiz['cim'] ?? ''),
                'data_iniciacao' => (string) ($aprendiz['data_iniciacao'] ?? ''),
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
        ];
    }

    private function montarResumoFallback(): array
    {
        $etapas = [];
        foreach (TrilhaAprendiz::etapas() as $ordem => $titulo) {
            $etapas[] = [
                'id' => null,
                'aprendiz_id' => null,
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
