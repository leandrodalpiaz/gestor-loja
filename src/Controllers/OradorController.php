<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Sessao;
use App\Models\Obreiro;
use App\Models\TrilhaAprendiz;
use App\Models\TrilhaCompanheiro;
use App\Models\ReconhecimentoSimbolico;
use PDO;

class OradorController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $sessaoSelecionadaId = (int) ($_GET['sessao_id'] ?? 0);
        $sessaoEmFoco = null;

        if ($sessaoSelecionadaId > 0) {
            $sessaoEmFoco = $sessaoModel->findById($sessaoSelecionadaId);
        }
        if (!$sessaoEmFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoEmFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $visitantesResumo = [];
        $cargosSessao = [];
        $eventosSessao = [];
        $lembretes = [];

        if ($sessaoEmFoco && !empty($sessaoEmFoco['id'])) {
            $visitantesResumo = $balaustreModel->obterResumoVisitantesPorSessao((int) $sessaoEmFoco['id']);
            $dadosBalaustre = $this->obterDadosBalaustre($balaustreModel, (int) $sessaoEmFoco['id']);
            $cargosSessao = $dadosBalaustre['cargos_sessao'] ?? [];
            $eventosSessao = $this->montarEventosSessao($dadosBalaustre);
            $lembretes = $this->montarLembretes($sessaoEmFoco, $visitantesResumo, $cargosSessao, $eventosSessao);
        }

        require_once __DIR__ . '/../Views/orador/index.php';
    }

    public function montarPayloadMiniapp(?int $sessaoId = null): array
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();
        $obreiroModel = new Obreiro();

        $sessoes = $sessaoModel->listarFuturas(8);
        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessaoFoco = null;

        if ($sessaoId !== null && $sessaoId > 0) {
            $sessaoFoco = $sessaoModel->findById($sessaoId);
        }
        if (!$sessaoFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $visitantes = [];
        $cargosSessao = [];
        $eventosSessao = [];
        $lembretes = [];
        $retornosFraternos = [];
        $lojaId = (int) ($_SESSION['tenant_id'] ?? 0);

        if ($sessaoFoco && !empty($sessaoFoco['id'])) {
            $visitantes = $balaustreModel->obterResumoVisitantesPorSessao((int) $sessaoFoco['id']);
            $dadosBalaustre = $this->obterDadosBalaustre($balaustreModel, (int) $sessaoFoco['id']);
            $cargosSessao = $dadosBalaustre['cargos_sessao'] ?? [];
            $eventosSessao = $this->montarEventosSessao($dadosBalaustre);
            $lembretes = $this->montarLembretes($sessaoFoco, $visitantes, $cargosSessao, $eventosSessao);
            $retornosFraternos = $this->obterRetornosFraternos($lojaId, (int) $sessaoFoco['id']);
        }

        // Obtém membros ativos e progresso
        $ativos = $obreiroModel->getAllAtivos();
        
        $aprendizesRaw = array_values(array_filter(
            $ativos,
            static fn (array $o): bool => strtolower(trim((string) ($o['grau'] ?? ''))) === 'aprendiz'
        ));
        $companheirosRaw = array_values(array_filter(
            $ativos,
            static fn (array $o): bool => strtolower(trim((string) ($o['grau'] ?? ''))) === 'companheiro'
        ));

        $aprendizIds = array_map(static fn ($o) => (string) $o['id'], $aprendizesRaw);
        $companheiroIds = array_map(static fn ($o) => (string) $o['id'], $companheirosRaw);

        $trilhaAprendizModel = new TrilhaAprendiz();
        $trilhaCompanheiroModel = new TrilhaCompanheiro();

        $progressoAprendiz = $this->obterProgressoTrilhas('trilha_aprendiz', 'aprendiz_id', $aprendizIds);
        $progressoCompanheiro = $this->obterProgressoTrilhas('trilha_companheiro', 'companheiro_id', $companheiroIds);

        $resumosAprendiz = $trilhaAprendizModel->trilhaDisponivel() ? $trilhaAprendizModel->listarResumoAtualPorAprendizIds($aprendizIds) : [];
        $resumosCompanheiro = $trilhaCompanheiroModel->trilhaDisponivel() ? $trilhaCompanheiroModel->listarResumoAtualPorCompanheiroIds($companheiroIds) : [];

        $aprendizes = array_map(function (array $o) use ($progressoAprendiz, $resumosAprendiz): array {
            $id = (string) $o['id'];
            $concluidos = $progressoAprendiz[$id] ?? 0;
            $resumo = $resumosAprendiz[$id] ?? null;
            $nomeHist = trim((string) ($o['nome_historico'] ?? ''));
            return [
                'id' => $id,
                'nome' => $nomeHist !== '' ? $nomeHist : (string) ($o['nome'] ?? ''),
                'grau' => 1,
                'etapa_atual' => (int) ($resumo['etapa_ordem'] ?? 1),
                'titulo_etapa_atual' => (string) ($resumo['titulo_etapa'] ?? 'Não iniciada'),
                'etapas_concluidas' => $concluidos,
                'total_etapas' => 13,
                'percentual' => (int) round(($concluidos / 13) * 100),
            ];
        }, $aprendizesRaw);

        $companheiros = array_map(function (array $o) use ($progressoCompanheiro, $resumosCompanheiro): array {
            $id = (string) $o['id'];
            $concluidos = $progressoCompanheiro[$id] ?? 0;
            $resumo = $resumosCompanheiro[$id] ?? null;
            $nomeHist = trim((string) ($o['nome_historico'] ?? ''));
            return [
                'id' => $id,
                'nome' => $nomeHist !== '' ? $nomeHist : (string) ($o['nome'] ?? ''),
                'grau' => 2,
                'etapa_atual' => (int) ($resumo['etapa_ordem'] ?? 1),
                'titulo_etapa_atual' => (string) ($resumo['titulo_etapa'] ?? 'Não iniciada'),
                'etapas_concluidas' => $concluidos,
                'total_etapas' => 10,
                'percentual' => (int) round(($concluidos / 10) * 100),
            ];
        }, $companheirosRaw);

        // Ordenar listas por nome
        usort($aprendizes, static fn ($a, $b) => strcasecmp($a['nome'], $b['nome']));
        usort($companheiros, static fn ($a, $b) => strcasecmp($a['nome'], $b['nome']));

        return [
            'proxima_sessao' => $proximaSessao ? $this->mapearSessao($proximaSessao) : null,
            'sessao_foco' => $sessaoFoco ? $this->mapearSessao($sessaoFoco) : null,
            'sessoes' => array_map(fn (array $sessao): array => $this->mapearSessao($sessao), $sessoes),
            'visitantes' => $visitantes,
            'cargos_sessao' => array_map(static function (array $item): array {
                return [
                    'cargo_nome' => (string) ($item['cargo_nome'] ?? $item['codigo'] ?? 'Cargo'),
                    'ocupante_nome' => (string) ($item['ocupante_nome'] ?? ''),
                    'tipo_ocupacao' => (string) ($item['tipo_ocupacao'] ?? 'regular'),
                ];
            }, $cargosSessao),
            'eventos_sessao' => $eventosSessao,
            'lembretes' => $lembretes,
            'retornos_fraternos' => $retornosFraternos,
            'efemerides_mes' => $this->obterEfemeridesMes($lojaId),
            'aprendizes' => $aprendizes,
            'companheiros' => $companheiros,
            'obreiros_ativos' => array_map(static function (array $o): array {
                $nomeHist = trim((string) ($o['nome_historico'] ?? ''));
                return [
                    'id' => (string) $o['id'],
                    'nome' => $nomeHist !== '' ? $nomeHist : (string) ($o['nome'] ?? ''),
                ];
            }, $ativos),
        ];
    }

    private function mapearSessao(array $sessao): array
    {
        return [
            'id' => (int) ($sessao['id'] ?? 0),
            'titulo' => (string) ($sessao['titulo'] ?? ''),
            'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
            'status' => (string) ($sessao['status'] ?? ''),
            'tipo_sessao' => (string) ($sessao['tipo_sessao'] ?? ''),
            'grau_sessao' => (string) ($sessao['grau_sessao'] ?? ''),
            'ordem_dia' => (string) ($sessao['ordem_dia'] ?? ''),
            'resumo_publico' => (string) ($sessao['resumo_publico'] ?? ''),
        ];
    }

    private function obterDadosBalaustre(Balaustre $balaustreModel, int $sessaoId): array
    {
        $balaustre = $balaustreModel->buscarPorSessao($sessaoId);
        if (!$balaustre) {
            return [];
        }

        $dados = $balaustre['dados_capturados'] ?? null;
        if (is_string($dados)) {
            $dados = json_decode($dados, true);
        }

        return is_array($dados) ? $dados : [];
    }

    private function montarEventosSessao(array $dadosBalaustre): array
    {
        $eventos = [];
        foreach ((array) ($dadosBalaustre['eventos_realizados']['congressos'] ?? []) as $item) {
            $eventos[] = [
                'tipo' => 'congresso',
                'titulo' => (string) ($item['titulo'] ?? 'Congresso'),
                'linha' => trim((string) (($item['promotor'] ?? '') . ' ' . ($item['data'] ?? ''))),
            ];
        }
        foreach ((array) ($dadosBalaustre['eventos_realizados']['palestras'] ?? []) as $item) {
            $eventos[] = [
                'tipo' => 'palestra',
                'titulo' => (string) ($item['titulo'] ?? 'Palestra'),
                'linha' => trim((string) (($item['palestrante'] ?? '') . ' ' . ($item['data'] ?? ''))),
            ];
        }
        return $eventos;
    }

    private function montarLembretes(array $sessao, array $visitantes, array $cargosSessao, array $eventosSessao): array
    {
        $lembretes = [];
        $resumo = trim((string) ($sessao['ordem_dia'] ?? $sessao['resumo_publico'] ?? ''));
        if ($resumo !== '') {
            $lembretes[] = 'Revisar a pauta resumida antes da abertura da palavra a bem da ordem.';
        }
        if ($visitantes !== []) {
            $lembretes[] = 'Conferir a nominata dos visitantes para agradecer nominalmente.';
        }
        if (array_filter($cargosSessao, static fn (array $item): bool => (string) ($item['tipo_ocupacao'] ?? '') === 'ad_hoc')) {
            $lembretes[] = 'Validar cargos exercidos ad-hoc para leitura coerente em Loja.';
        }
        if ($eventosSessao !== []) {
            $lembretes[] = 'Separar menções a congressos, palestras e atividades registradas no balaústre.';
        }
        if ($lembretes === []) {
            $lembretes[] = 'Manter a leitura ritual alinhada com a sessão em foco e os registros oficiais.';
        }
        return $lembretes;
    }

    private function obterProgressoTrilhas(string $tabela, string $colunaId, array $obreiroIds): array
    {
        if ($obreiroIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($obreiroIds), '?'));
        
        $statusConcluidos = $tabela === 'trilha_aprendiz'
            ? ['concluido', 'certificado_solicitado']
            : ['concluido', 'certificado_solicitado', 'apto_para_exaltacao', 'exaltacao_recomendada'];
            
        $statusPlaceholders = implode(', ', array_fill(0, count($statusConcluidos), '?'));
        
        $sql = "
            SELECT {$colunaId} AS obreiro_id, COUNT(*) AS concluidos
            FROM public.{$tabela}
            WHERE {$colunaId} IN ($placeholders)
              AND status IN ($statusPlaceholders)
            GROUP BY {$colunaId}
        ";

        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([...$obreiroIds, ...$statusConcluidos]);
        
        $resultado = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $resultado[(string) $row['obreiro_id']] = (int) $row['concluidos'];
        }

        return $resultado;
    }

    private function obterRetornosFraternos(int $lojaId, ?int $sessaoFocoId): array
    {
        if (!$sessaoFocoId) {
            return [];
        }

        $db = \App\Config\Database::getConnection();

        $stmtSessoes = $db->prepare("
            SELECT id 
            FROM sessoes 
            WHERE loja_id = :loja_id 
              AND status = 'realizada' 
              AND id < :sessao_foco_id 
            ORDER BY data_hora_inicio DESC 
            LIMIT 3
        ");
        $stmtSessoes->execute([
            'loja_id' => $lojaId,
            'sessao_foco_id' => $sessaoFocoId
        ]);
        $ultimasSessoesIds = $stmtSessoes->fetchAll(PDO::FETCH_COLUMN) ?: [];

        if (count($ultimasSessoesIds) < 3) {
            return [];
        }

        $stmtPresentes = $db->prepare("
            SELECT obreiro_id 
            FROM presencas_sessao 
            WHERE sessao_id = :sessao_id 
              AND presente = true
        ");
        $stmtPresentes->execute(['sessao_id' => $sessaoFocoId]);
        $obreiroIds = $stmtPresentes->fetchAll(PDO::FETCH_COLUMN) ?: [];

        if ($obreiroIds === []) {
            $stmtConfirmados = $db->prepare("
                SELECT obreiro_id 
                FROM confirmacoes_sessao 
                WHERE sessao_id = :sessao_id 
                  AND status_confirmacao = 'confirmado'
            ");
            $stmtConfirmados->execute(['sessao_id' => $sessaoFocoId]);
            $obreiroIds = $stmtConfirmados->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        if ($obreiroIds === []) {
            return [];
        }

        $retornos = [];
        $placeholders = implode(', ', array_fill(0, count($ultimasSessoesIds), '?'));
        
        $stmtCheck = $db->prepare("
            SELECT COUNT(*) 
            FROM presencas_sessao 
            WHERE obreiro_id = ? 
              AND sessao_id IN ($placeholders) 
              AND presente = true
        ");

        foreach ($obreiroIds as $obreiroId) {
            $stmtCheck->execute([$obreiroId, ...$ultimasSessoesIds]);
            $presencasCount = (int) $stmtCheck->fetchColumn();

            if ($presencasCount === 0) {
                $stmtNome = $db->prepare("
                    SELECT id, nome, nome_historico, grau 
                    FROM obreiros 
                    WHERE id = :id 
                    LIMIT 1
                ");
                $stmtNome->execute(['id' => $obreiroId]);
                $o = $stmtNome->fetch(PDO::FETCH_ASSOC);
                if ($o) {
                    $retornos[] = [
                        'id' => $o['id'],
                        'nome' => $o['nome_historico'] ?: $o['nome'],
                        'grau' => $o['grau']
                    ];
                }
            }
        }

        return $retornos;
    }

    private function obterEfemeridesMes(int $lojaId): array
    {
        $db = \App\Config\Database::getConnection();
        $hoje = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        $mes = (int) $hoje->format('m');

        $stmtCheck = $db->query("SELECT to_regclass('efemerides_registros')");
        $tabelaExiste = $stmtCheck ? (string) $stmtCheck->fetchColumn() !== '' : false;

        if (!$tabelaExiste) {
            return [];
        }

        $stmt = $db->prepare("
            SELECT nome, tipo, data_evento 
            FROM efemerides_registros 
            WHERE loja_id = :loja_id 
              AND ativo = true 
              AND EXTRACT(MONTH FROM data_evento) = :mes 
            ORDER BY EXTRACT(DAY FROM data_evento) ASC, nome ASC
        ");
        $stmt->execute(['loja_id' => $lojaId, 'mes' => $mes]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $item): array {
            $data = $item['data_evento'] ? new \DateTimeImmutable($item['data_evento']) : null;
            return [
                'nome' => (string) ($item['nome'] ?? ''),
                'tipo' => (string) ($item['tipo'] ?? ''),
                'dia_mes' => $data ? $data->format('d/m') : '',
            ];
        }, $registros);
    }

    public function listarReconhecimentos(): array
    {
        $model = new ReconhecimentoSimbolico();
        return ['ok' => true, 'dados' => $model->listarPorLoja()];
    }

    public function salvarReconhecimento(array $body, string $oradorId): array
    {
        if ($oradorId === '') {
            return ['ok' => false, 'erro' => 'Orador não identificado.'];
        }
        $model = new ReconhecimentoSimbolico();
        $ok = $model->criar($body, $oradorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Falha ao salvar o reconhecimento.'];
    }

    public function deletarReconhecimento(string $id, string $oradorId): array
    {
        if ($oradorId === '') {
            return ['ok' => false, 'erro' => 'Orador não autorizado.'];
        }
        $model = new ReconhecimentoSimbolico();
        $ok = $model->deletar($id, $oradorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Falha ao deletar o reconhecimento.'];
    }
}
