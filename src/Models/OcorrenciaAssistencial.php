<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class OcorrenciaAssistencial
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $payload): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO public.ocorrencias_assistenciais (
                loja_id,
                tipo_ocorrencia,
                status,
                prioridade,
                obreiro_id,
                nome_familiar,
                parentesco,
                descricao,
                necessita_visita,
                necessita_apoio_financeiro,
                valor_solicitado,
                valor_aprovado,
                encaminhar_para,
                data_ocorrencia,
                data_proxima_acao,
                origem_registro,
                created_by,
                updated_by
            ) VALUES (
                :loja_id,
                :tipo_ocorrencia,
                :status,
                :prioridade,
                :obreiro_id,
                :nome_familiar,
                :parentesco,
                :descricao,
                :necessita_visita,
                :necessita_apoio_financeiro,
                :valor_solicitado,
                :valor_aprovado,
                :encaminhar_para,
                :data_ocorrencia,
                :data_proxima_acao,
                :origem_registro,
                :created_by,
                :updated_by
            )
        ");

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'tipo_ocorrencia' => $this->normalizarTipo((string) ($payload['tipo_ocorrencia'] ?? 'assistencia_geral')),
            'status' => $this->normalizarStatus((string) ($payload['status'] ?? 'aberta')),
            'prioridade' => $this->normalizarPrioridade((string) ($payload['prioridade'] ?? 'media')),
            'obreiro_id' => $this->limparTexto($payload['obreiro_id'] ?? null),
            'nome_familiar' => $this->limparTexto($payload['nome_familiar'] ?? null),
            'parentesco' => $this->limparTexto($payload['parentesco'] ?? null),
            'descricao' => $this->limparTexto($payload['descricao'] ?? null),
            'necessita_visita' => !empty($payload['necessita_visita']),
            'necessita_apoio_financeiro' => !empty($payload['necessita_apoio_financeiro']),
            'valor_solicitado' => $this->normalizarValor($payload['valor_solicitado'] ?? null),
            'valor_aprovado' => $this->normalizarValor($payload['valor_aprovado'] ?? null),
            'encaminhar_para' => $this->normalizarEncaminhamento((string) ($payload['encaminhar_para'] ?? 'nenhum')),
            'data_ocorrencia' => $this->normalizarData($payload['data_ocorrencia'] ?? null),
            'data_proxima_acao' => $this->normalizarData($payload['data_proxima_acao'] ?? null),
            'origem_registro' => $this->limparTexto($payload['origem_registro'] ?? 'manual') ?? 'manual',
            'created_by' => $this->limparTexto($payload['created_by'] ?? null),
            'updated_by' => $this->limparTexto($payload['updated_by'] ?? null),
        ]);
    }

    public function listarRecentes(int $limite = 60): array
    {
        $limite = max(1, min($limite, 300));

        $stmt = $this->db->prepare("
            SELECT
                oa.*,
                COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                o.cim AS obreiro_cim,
                COALESCE(criador.nome_historico, criador.nome) AS criado_por_nome
            FROM public.ocorrencias_assistenciais oa
            LEFT JOIN public.obreiros o ON o.id = oa.obreiro_id
            LEFT JOIN public.obreiros criador ON criador.id = oa.created_by
            WHERE oa.loja_id = :loja_id
            ORDER BY oa.created_at DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarResumo(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = 'aberta') AS abertas,
                COUNT(*) FILTER (WHERE status = 'em_acompanhamento') AS em_acompanhamento,
                COUNT(*) FILTER (WHERE status = 'concluida') AS concluidas,
                COUNT(*) FILTER (WHERE necessita_apoio_financeiro = TRUE) AS com_apoio_financeiro
            FROM public.ocorrencias_assistenciais
            WHERE loja_id = :loja_id
        ");
        $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'abertas' => (int) ($row['abertas'] ?? 0),
            'em_acompanhamento' => (int) ($row['em_acompanhamento'] ?? 0),
            'concluidas' => (int) ($row['concluidas'] ?? 0),
            'com_apoio_financeiro' => (int) ($row['com_apoio_financeiro'] ?? 0),
        ];
    }

    public function atualizarStatus(int $id, string $status, ?string $autorId = null, ?string $observacao = null): bool
    {
        if ($id <= 0) {
            return false;
        }

        $status = $this->normalizarStatus($status);
        $observacao = $this->limparTexto($observacao);

        $stmt = $this->db->prepare("
            UPDATE public.ocorrencias_assistenciais
               SET status = :status,
                   observacao_status = CASE
                       WHEN :observacao IS NULL OR :observacao = '' THEN observacao_status
                       ELSE :observacao
                   END,
                   updated_by = :updated_by,
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");

        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'observacao' => $observacao,
            'updated_by' => $this->limparTexto($autorId),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function registrarVisita(int $id, ?string $autorId = null, ?string $observacao = null, ?string $dataProximaAcao = null): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE public.ocorrencias_assistenciais
               SET status = 'em_acompanhamento',
                   necessita_visita = FALSE,
                   observacao_status = CASE
                       WHEN :observacao IS NULL OR :observacao = '' THEN observacao_status
                       ELSE :observacao
                   END,
                   data_proxima_acao = COALESCE(:data_proxima_acao, data_proxima_acao),
                   updated_by = :updated_by,
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");

        return $stmt->execute([
            'id' => $id,
            'observacao' => $this->limparTexto($observacao),
            'data_proxima_acao' => $this->normalizarData($dataProximaAcao),
            'updated_by' => $this->limparTexto($autorId),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function listarPendentesVisita(int $limite = 40): array
    {
        $limite = max(1, min($limite, 200));

        $stmt = $this->db->prepare("
            SELECT
                oa.*,
                COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                o.cim AS obreiro_cim
            FROM public.ocorrencias_assistenciais oa
            LEFT JOIN public.obreiros o ON o.id = oa.obreiro_id
            WHERE oa.loja_id = :loja_id
              AND (
                  oa.necessita_visita = TRUE
                  OR oa.status = 'em_acompanhamento'
              )
            ORDER BY COALESCE(oa.data_proxima_acao, oa.data_ocorrencia, CURRENT_DATE) ASC, oa.updated_at DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));
        $validos = ['saude', 'nascimento', 'falecimento', 'assistencia_geral', 'solidariedade'];
        return in_array($tipo, $validos, true) ? $tipo : 'assistencia_geral';
    }

    private function normalizarStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $validos = ['aberta', 'em_acompanhamento', 'concluida', 'cancelada'];
        return in_array($status, $validos, true) ? $status : 'aberta';
    }

    private function normalizarPrioridade(string $prioridade): string
    {
        $prioridade = strtolower(trim($prioridade));
        $validas = ['baixa', 'media', 'alta', 'urgente'];
        return in_array($prioridade, $validas, true) ? $prioridade : 'media';
    }

    private function normalizarEncaminhamento(string $encaminharPara): string
    {
        $encaminharPara = strtolower(trim($encaminharPara));
        $validos = ['nenhum', 'veneravel', 'tesoureiro', 'ambos'];
        return in_array($encaminharPara, $validos, true) ? $encaminharPara : 'nenhum';
    }

    private function normalizarData(mixed $data): ?string
    {
        $data = trim((string) $data);
        return $data !== '' ? $data : null;
    }

    private function normalizarValor(mixed $valor): ?float
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        $texto = str_replace(' ', '', $texto);
        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (str_contains($texto, ',')) {
            $texto = str_replace(',', '.', $texto);
        }

        if (!is_numeric($texto)) {
            return null;
        }

        return (float) $texto;
    }

    private function limparTexto(mixed $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
