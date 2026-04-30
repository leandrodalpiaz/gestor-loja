<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;
use DateTimeImmutable;
use DateTimeZone;

class Sessao
{
    use ResolvesStoreTenant;

    private PDO $db;

    public const GRAUS_PADRAO = ['Aprendiz', 'Companheiro', 'Mestre', 'Outro'];
    public const TIPOS_PRINCIPAIS = ['economica', 'magna', 'outra'];
    public const SUBTIPOS_ECONOMICA = [
        'economica_1' => 'Economica de 1 Grau',
        'economica_2' => 'Economica de 2 Grau',
        'economica_3' => 'Economica de 3 Grau',
        'outra' => 'Outra',
    ];
    public const SUBTIPOS_MAGNA = [
        'magna_iniciacao' => 'Magna de Iniciacao',
        'magna_elevacao' => 'Magna de Elevacao',
        'magna_exaltacao' => 'Magna de Exaltacao',
        'magna_instalacao' => 'Magna de Instalacao',
        'outra' => 'Outra',
    ];
    public const TRAJES = ['maconico', 'livre', 'outro'];
    public const AGAPES = ['nao_havera', 'gratuito', 'pago'];
    public const AGAPE_MODELOS_FINANCEIROS = ['oficial_loja', 'particular', 'misto'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                s.*,
                COALESCE(confirmados.total_confirmados, 0) AS total_confirmados,
                COALESCE(confirmados.total_ausentes, 0) AS total_ausentes,
                COALESCE(confirmados.total_agape, 0) AS total_agape,
                COALESCE(presentes.total_presentes, 0) AS total_presentes
            FROM sessoes s
            LEFT JOIN (
                SELECT
                    sessao_id,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado') AS total_confirmados,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'ausente') AS total_ausentes,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado' AND participara_agape = TRUE) AS total_agape
                FROM confirmacoes_sessao
                GROUP BY sessao_id
            ) confirmados ON confirmados.sessao_id = s.id
            LEFT JOIN (
                SELECT
                    sessao_id,
                    COUNT(*) FILTER (WHERE presente = TRUE) AS total_presentes
                FROM presencas_sessao
                GROUP BY sessao_id
            ) presentes ON presentes.sessao_id = s.id
            WHERE s.id = :id
              AND s.loja_id = :loja_id
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);

        return $sessao ?: null;
    }

    public function obterProximaSessao(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                data_hora_inicio,
                data_hora_fim,
                tipo_sessao,
                grau_sessao,
                natureza_sessao,
                formato_sessao,
                finalidade_ritual,
                tipo_sessao_principal,
                tipo_sessao_subtipo,
                tipo_sessao_personalizado,
                traje_tipo,
                traje_personalizado,
                agape_modalidade,
                agape_modelo_financeiro,
                agape_valor,
                ordem_dia,
                sessao_branca,
                sessao_a_campo,
                conta_relatorio_potencia,
                titulo,
                resumo_publico,
                status,
                agape_ativo,
                publicada_em
            FROM sessoes
            WHERE data_hora_inicio >= NOW()
              AND loja_id = :loja_id
              AND status IN ('planejada', 'publicada', 'alterada')
            ORDER BY data_hora_inicio ASC
            LIMIT 1
        ");
        $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);

        return $sessao ?: null;
    }

    public function listarFuturas(int $limite = 50): array
    {
        $limite = max(1, min($limite, 300));
        $stmt = $this->db->prepare("
            SELECT
                s.id,
                s.data_hora_inicio,
                s.data_hora_fim,
                s.tipo_sessao,
                s.grau_sessao,
                s.natureza_sessao,
                s.formato_sessao,
                s.finalidade_ritual,
                s.tipo_sessao_principal,
                s.tipo_sessao_subtipo,
                s.tipo_sessao_personalizado,
                s.traje_tipo,
                s.traje_personalizado,
                s.agape_modalidade,
                s.agape_modelo_financeiro,
                s.agape_valor,
                s.ordem_dia,
                s.sessao_branca,
                s.sessao_a_campo,
                s.conta_relatorio_potencia,
                s.titulo,
                s.status,
                s.agape_ativo,
                COALESCE(confirmados.total_confirmados, 0) AS total_confirmados,
                COALESCE(confirmados.total_agape, 0) AS total_agape
            FROM sessoes s
            LEFT JOIN (
                SELECT
                    sessao_id,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado') AS total_confirmados,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado' AND participara_agape = TRUE) AS total_agape
                FROM confirmacoes_sessao
                GROUP BY sessao_id
            ) confirmados ON confirmados.sessao_id = s.id
            WHERE s.data_hora_inicio >= NOW()
              AND s.loja_id = :loja_id
              AND s.status IN ('planejada', 'publicada', 'alterada', 'cancelada')
            ORDER BY s.data_hora_inicio ASC
            LIMIT :limite
        ");
        $stmt->bindValue('loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(array $data, ?string $autorId = null): ?int
    {
        $payload = $this->normalizarPayload($data);
        $sql = "
            INSERT INTO sessoes (
                loja_id,
                data,
                grau,
                tipo,
                pauta,
                data_hora_inicio,
                data_hora_fim,
                tipo_sessao,
                grau_sessao,
                gestao_referencia,
                natureza_sessao,
                formato_sessao,
                finalidade_ritual,
                grau_personalizado,
                tipo_sessao_principal,
                tipo_sessao_subtipo,
                tipo_sessao_personalizado,
                traje_tipo,
                traje_personalizado,
                agape_modalidade,
                agape_modelo_financeiro,
                agape_valor,
                ordem_dia,
                templo_local,
                sessao_branca,
                sessao_a_campo,
                conta_relatorio_potencia,
                observacao_relatorio,
                titulo,
                resumo_publico,
                observacao_interna,
                status,
                agape_ativo,
                criada_por,
                atualizada_por,
                updated_at
            ) VALUES (
                :loja_id,
                :data_legado,
                :grau_legado,
                :tipo_legado,
                :pauta_legado,
                :data_hora_inicio,
                :data_hora_fim,
                :tipo_sessao,
                :grau_sessao,
                :gestao_referencia,
                :natureza_sessao,
                :formato_sessao,
                :finalidade_ritual,
                :grau_personalizado,
                :tipo_sessao_principal,
                :tipo_sessao_subtipo,
                :tipo_sessao_personalizado,
                :traje_tipo,
                :traje_personalizado,
                :agape_modalidade,
                :agape_modelo_financeiro,
                :agape_valor,
                :ordem_dia,
                :templo_local,
                :sessao_branca,
                :sessao_a_campo,
                :conta_relatorio_potencia,
                :observacao_relatorio,
                :titulo,
                :resumo_publico,
                :observacao_interna,
                :status,
                :agape_ativo,
                :criada_por,
                :atualizada_por,
                NOW()
            )
            RETURNING id
        ";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'data_legado' => $payload['data_hora_inicio'],
            'grau_legado' => $payload['grau_sessao'],
            'tipo_legado' => $payload['tipo_sessao'],
            'pauta_legado' => $payload['resumo_publico'],
            'data_hora_inicio' => $payload['data_hora_inicio'],
            'data_hora_fim' => $payload['data_hora_fim'],
            'tipo_sessao' => $payload['tipo_sessao'],
            'grau_sessao' => $payload['grau_sessao'],
            'gestao_referencia' => $payload['gestao_referencia'],
            'natureza_sessao' => $payload['natureza_sessao'],
            'formato_sessao' => $payload['formato_sessao'],
            'finalidade_ritual' => $payload['finalidade_ritual'],
            'grau_personalizado' => $payload['grau_personalizado'],
            'tipo_sessao_principal' => $payload['tipo_sessao_principal'],
            'tipo_sessao_subtipo' => $payload['tipo_sessao_subtipo'],
            'tipo_sessao_personalizado' => $payload['tipo_sessao_personalizado'],
            'traje_tipo' => $payload['traje_tipo'],
            'traje_personalizado' => $payload['traje_personalizado'],
            'agape_modalidade' => $payload['agape_modalidade'],
            'agape_modelo_financeiro' => $payload['agape_modelo_financeiro'],
            'agape_valor' => $payload['agape_valor'],
            'ordem_dia' => $payload['ordem_dia'],
            'templo_local' => $payload['templo_local'],
            'sessao_branca' => $payload['sessao_branca'] ? 'true' : 'false',
            'sessao_a_campo' => $payload['sessao_a_campo'] ? 'true' : 'false',
            'conta_relatorio_potencia' => $payload['conta_relatorio_potencia'] ? 'true' : 'false',
            'observacao_relatorio' => $payload['observacao_relatorio'],
            'titulo' => $payload['titulo'],
            'resumo_publico' => $payload['resumo_publico'],
            'observacao_interna' => $payload['observacao_interna'],
            'status' => $payload['status'],
            'agape_ativo' => $payload['agape_ativo'] ? 'true' : 'false',
            'criada_por' => $autorId,
            'atualizada_por' => $autorId,
        ]);

        if (!$ok) {
            return null;
        }

        $sessaoId = (int) $stmt->fetchColumn();
        $this->registrarHistorico($sessaoId, 'sessao_criada', null, $payload, $autorId, 'Criacao da sessao');

        return $sessaoId;
    }

    public function atualizar(int $id, array $data, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $payload = $this->normalizarPayload($data, $anterior);
        $status = $anterior['status'] === 'publicada' ? 'alterada' : ($payload['status'] ?? $anterior['status']);

        $stmt = $this->db->prepare("
            UPDATE sessoes
               SET data = :data_legado,
                   grau = :grau_legado,
                   tipo = :tipo_legado,
                   pauta = :pauta_legado,
                   data_hora_inicio = :data_hora_inicio,
                   data_hora_fim = :data_hora_fim,
                   tipo_sessao = :tipo_sessao,
                   grau_sessao = :grau_sessao,
                   gestao_referencia = :gestao_referencia,
                   natureza_sessao = :natureza_sessao,
                   formato_sessao = :formato_sessao,
                   finalidade_ritual = :finalidade_ritual,
                   grau_personalizado = :grau_personalizado,
                   tipo_sessao_principal = :tipo_sessao_principal,
                   tipo_sessao_subtipo = :tipo_sessao_subtipo,
                   tipo_sessao_personalizado = :tipo_sessao_personalizado,
                   traje_tipo = :traje_tipo,
                   traje_personalizado = :traje_personalizado,
                   agape_modalidade = :agape_modalidade,
                   agape_modelo_financeiro = :agape_modelo_financeiro,
                   agape_valor = :agape_valor,
                   ordem_dia = :ordem_dia,
                   templo_local = :templo_local,
                   sessao_branca = :sessao_branca,
                   sessao_a_campo = :sessao_a_campo,
                   conta_relatorio_potencia = :conta_relatorio_potencia,
                   observacao_relatorio = :observacao_relatorio,
                   titulo = :titulo,
                   resumo_publico = :resumo_publico,
                   observacao_interna = :observacao_interna,
                   status = :status,
                   agape_ativo = :agape_ativo,
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");

        $ok = $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
            'data_legado' => $payload['data_hora_inicio'],
            'grau_legado' => $payload['grau_sessao'],
            'tipo_legado' => $payload['tipo_sessao'],
            'pauta_legado' => $payload['resumo_publico'],
            'data_hora_inicio' => $payload['data_hora_inicio'],
            'data_hora_fim' => $payload['data_hora_fim'],
            'tipo_sessao' => $payload['tipo_sessao'],
            'grau_sessao' => $payload['grau_sessao'],
            'gestao_referencia' => $payload['gestao_referencia'],
            'natureza_sessao' => $payload['natureza_sessao'],
            'formato_sessao' => $payload['formato_sessao'],
            'finalidade_ritual' => $payload['finalidade_ritual'],
            'grau_personalizado' => $payload['grau_personalizado'],
            'tipo_sessao_principal' => $payload['tipo_sessao_principal'],
            'tipo_sessao_subtipo' => $payload['tipo_sessao_subtipo'],
            'tipo_sessao_personalizado' => $payload['tipo_sessao_personalizado'],
            'traje_tipo' => $payload['traje_tipo'],
            'traje_personalizado' => $payload['traje_personalizado'],
            'agape_modalidade' => $payload['agape_modalidade'],
            'agape_modelo_financeiro' => $payload['agape_modelo_financeiro'],
            'agape_valor' => $payload['agape_valor'],
            'ordem_dia' => $payload['ordem_dia'],
            'templo_local' => $payload['templo_local'],
            'sessao_branca' => $payload['sessao_branca'] ? 'true' : 'false',
            'sessao_a_campo' => $payload['sessao_a_campo'] ? 'true' : 'false',
            'conta_relatorio_potencia' => $payload['conta_relatorio_potencia'] ? 'true' : 'false',
            'observacao_relatorio' => $payload['observacao_relatorio'],
            'titulo' => $payload['titulo'],
            'resumo_publico' => $payload['resumo_publico'],
            'observacao_interna' => $payload['observacao_interna'],
            'status' => $status,
            'agape_ativo' => $payload['agape_ativo'] ? 'true' : 'false',
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $payload['status'] = $status;
            $this->registrarHistorico($id, 'sessao_atualizada', $anterior, $payload, $autorId, $observacao);
        }

        return $ok;
    }

    public function cancelar(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE sessoes
               SET status = 'cancelada',
                   cancelada_em = NOW(),
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_cancelada', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function reabrir(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE sessoes
               SET status = CASE WHEN publicada_em IS NULL THEN 'planejada' ELSE 'alterada' END,
                   cancelada_em = NULL,
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_reaberta', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function marcarPublicada(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE sessoes
               SET status = 'publicada',
                   publicada_em = COALESCE(publicada_em, NOW()),
                   publicado_por = COALESCE(publicado_por, :publicado_por),
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
            'publicado_por' => $autorId,
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_publicada', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function marcarRealizada(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE sessoes
               SET status = 'realizada',
                   realizada_em = NOW(),
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_realizada', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function obterResumoOperacional(int $id): ?array
    {
        $sessao = $this->findById($id);
        if (!$sessao) {
            return null;
        }

        return [
            'sessao' => $sessao,
            'total_confirmados' => (int) ($sessao['total_confirmados'] ?? 0),
            'total_ausentes' => (int) ($sessao['total_ausentes'] ?? 0),
            'total_agape' => (int) ($sessao['total_agape'] ?? 0),
            'total_presentes' => (int) ($sessao['total_presentes'] ?? 0),
        ];
    }

    public function listarHistorico(int $sessaoId, int $limite = 20): array
    {
        $limite = max(1, min($limite, 100));
        $stmt = $this->db->prepare("
            SELECT
                h.*,
                COALESCE(o.nome_historico, o.nome) AS autor_nome
            FROM historico_sessao h
            JOIN sessoes s ON s.id = h.sessao_id
            LEFT JOIN obreiros o ON o.id = h.autor_id
            WHERE h.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
            ORDER BY h.created_at DESC, h.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue('sessao_id', $sessaoId, PDO::PARAM_INT);
        $stmt->bindValue('loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarDuplicidade(array $data): ?array
    {
        $payload = $this->normalizarPayload($data);
        if ($payload['data_hora_inicio'] === null || $payload['titulo'] === null) {
            return null;
        }

        $inicio = $this->parseDateTime($payload['data_hora_inicio']);
        if (!$inicio) {
            return null;
        }

        $inicioDia = $inicio->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d');
        $hora = $inicio->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('H:i');

        $stmt = $this->db->prepare("
            SELECT id, titulo, data_hora_inicio, status
            FROM sessoes
            WHERE COALESCE(status, 'planejada') <> 'cancelada'
              AND loja_id = :loja_id
              AND DATE(data_hora_inicio AT TIME ZONE 'America/Sao_Paulo') = :dia
              AND TO_CHAR(data_hora_inicio AT TIME ZONE 'America/Sao_Paulo', 'HH24:MI') = :hora
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'dia' => $inicioDia,
            'hora' => $hora,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function comporResumoPublicacao(array $sessao, array $configuracaoLoja): string
    {
        $dataHora = '';
        if (!empty($sessao['data_hora_inicio'])) {
            $dt = $this->parseDateTime((string) $sessao['data_hora_inicio']);
            if ($dt) {
                $dt = $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                $dias = ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
                $dataHora = $dt->format('d/m/Y') . ' (' . $dias[(int) $dt->format('w')] . ') - ' . $dt->format('H:i');
            } else {
                $dataHora = 'Data invalida';
            }
        }

        $grau = trim((string) ($sessao['grau_sessao'] ?? ''));
        $nomeLoja = trim((string) ($configuracaoLoja['nome_loja'] ?? ''));
        $numeroLoja = trim((string) ($configuracaoLoja['numero_loja'] ?? ''));
        $linhaLoja = trim($nomeLoja . ($numeroLoja !== '' ? ' nº ' . $numeroLoja : ''));
        $tipo = $this->obterDescricaoTipoSessao($sessao);
        $traje = $this->obterDescricaoTraje($sessao);
        $agape = $this->obterDescricaoAgape($sessao);
        $agapeModelo = $this->obterDescricaoModeloFinanceiroAgape($sessao);
        $ordemDia = trim((string) ($sessao['ordem_dia'] ?? $sessao['resumo_publico'] ?? ''));

        $linhas = [
            'NOVA SESSAO',
            '',
            $dataHora,
            'Grau: ' . ($grau !== '' ? $grau : '-'),
            '',
            $linhaLoja,
            '',
            'Sessao:',
            'Tipo: ' . ($tipo !== '' ? $tipo : '-'),
            'Traje: ' . ($traje !== '' ? $traje : '-'),
            'Ordem do dia: ' . ($ordemDia !== '' ? $ordemDia : '-'),
            'Agape: ' . $agape,
            'Modelo financeiro do agape: ' . $agapeModelo,
        ];

        return implode("\n", $linhas);
    }

    public function obterDescricaoTipoSessao(array $sessao): string
    {
        $principal = strtolower(trim((string) ($sessao['tipo_sessao_principal'] ?? '')));
        $subtipo = strtolower(trim((string) ($sessao['tipo_sessao_subtipo'] ?? '')));
        $personalizado = trim((string) ($sessao['tipo_sessao_personalizado'] ?? ''));

        if ($principal === 'economica') {
            return self::SUBTIPOS_ECONOMICA[$subtipo] ?? ($personalizado !== '' ? $personalizado : 'Economica');
        }
        if ($principal === 'magna') {
            return self::SUBTIPOS_MAGNA[$subtipo] ?? ($personalizado !== '' ? $personalizado : 'Magna');
        }

        return $personalizado !== '' ? $personalizado : trim((string) ($sessao['tipo_sessao'] ?? ''));
    }

    public function obterDescricaoTraje(array $sessao): string
    {
        $traje = strtolower(trim((string) ($sessao['traje_tipo'] ?? '')));
        return match ($traje) {
            'maconico' => 'Maconico',
            'livre' => 'Livre',
            'outro' => trim((string) ($sessao['traje_personalizado'] ?? '')) !== ''
                ? trim((string) ($sessao['traje_personalizado'] ?? ''))
                : 'Outro',
            default => 'Maconico',
        };
    }

    public function obterDescricaoAgape(array $sessao): string
    {
        $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')));
        $valor = $sessao['agape_valor'] ?? null;
        return match ($modalidade) {
            'gratuito' => 'Sim (gratuito)',
            'pago' => $valor !== null && $valor !== ''
                ? 'Sim (pago - referencia R$ ' . number_format((float) $valor, 2, ',', '.') . ')'
                : 'Sim (pago - valor a definir pelo Mestre de Banquetes)',
            default => 'Não haverá',
        };
    }

    public function obterDescricaoModeloFinanceiroAgape(array $sessao): string
    {
        $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')));
        if ($modalidade === 'nao_havera') {
            return 'Não se aplica';
        }

        $modelo = strtolower(trim((string) ($sessao['agape_modelo_financeiro'] ?? 'oficial_loja')));
        return match ($modelo) {
            'particular' => 'Particular entre participantes (fora do caixa oficial)',
            'misto' => 'Misto (parte oficial da Loja e parte particular)',
            default => 'Oficial da Loja',
        };
    }

    public function obterAcoesConfirmacao(array $sessao): array
    {
        $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')));
        $acoes = [];
        if ($modalidade === 'gratuito') {
            $acoes[] = ['acao' => 'confirmar_com_agape', 'label' => 'Participar com agape (gratuito)'];
            $acoes[] = ['acao' => 'confirmar_sem_agape', 'label' => 'Participar sem agape'];
        } elseif ($modalidade === 'pago') {
            $acoes[] = ['acao' => 'confirmar_com_agape', 'label' => 'Participar com agape (pago)'];
            $acoes[] = ['acao' => 'confirmar_sem_agape', 'label' => 'Participar sem agape'];
        } else {
            $acoes[] = ['acao' => 'confirmar_presenca', 'label' => 'Confirmar presenca'];
        }

        $acoes[] = ['acao' => 'cancelar_confirmacao', 'label' => 'Cancelar confirmacao'];
        $acoes[] = ['acao' => 'informar_ausencia', 'label' => 'Informar ausencia'];
        $acoes[] = ['acao' => 'ver_confirmados', 'label' => 'Ver confirmados'];
        return $acoes;
    }

    private function registrarHistorico(
        int $sessaoId,
        string $acao,
        mixed $valorAnterior,
        mixed $valorNovo,
        ?string $autorId = null,
        ?string $observacao = null
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO historico_sessao (
                    sessao_id,
                    acao,
                    valor_anterior,
                    valor_novo,
                    autor_id,
                    observacao
                ) VALUES (
                    :sessao_id,
                    :acao,
                    CAST(:valor_anterior AS jsonb),
                    CAST(:valor_novo AS jsonb),
                    :autor_id,
                    :observacao
                )
            ");
            $stmt->execute([
                'sessao_id' => $sessaoId,
                'acao' => $acao,
                'valor_anterior' => $valorAnterior !== null ? json_encode($valorAnterior, JSON_UNESCAPED_UNICODE) : null,
                'valor_novo' => $valorNovo !== null ? json_encode($valorNovo, JSON_UNESCAPED_UNICODE) : null,
                'autor_id' => $autorId,
                'observacao' => $observacao,
            ]);
        } catch (\Throwable $e) {
            error_log('Falha ao registrar historico de sessao: ' . $e->getMessage());
        }
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function parseDateTime(?string $valor): ?DateTimeImmutable
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($valor);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizarPayload(array $data, ?array $fallback = null): array
    {
        $fallback = $fallback ?? [];

        $grauInformado = trim((string) ($data['grau_sessao'] ?? $data['grau'] ?? $fallback['grau_sessao'] ?? ''));
        $grauPersonalizado = trim((string) ($data['grau_personalizado'] ?? $fallback['grau_personalizado'] ?? ''));
        $grauFinal = $grauInformado === 'Outro' ? ($grauPersonalizado !== '' ? $grauPersonalizado : 'Outro') : ($grauInformado !== '' ? $grauInformado : null);

        $tipoPrincipal = strtolower(trim((string) ($data['tipo_sessao_principal'] ?? $fallback['tipo_sessao_principal'] ?? '')));
        if (!in_array($tipoPrincipal, self::TIPOS_PRINCIPAIS, true)) {
            $tipoPrincipal = 'economica';
        }
        $tipoSubtipo = strtolower(trim((string) ($data['tipo_sessao_subtipo'] ?? $fallback['tipo_sessao_subtipo'] ?? '')));
        $tipoPersonalizado = trim((string) ($data['tipo_sessao_personalizado'] ?? $fallback['tipo_sessao_personalizado'] ?? ''));
        $tipoFinal = $tipoPrincipal === 'outra'
            ? ($tipoPersonalizado !== '' ? $tipoPersonalizado : 'Outra')
            : ($tipoPrincipal === 'economica'
                ? (self::SUBTIPOS_ECONOMICA[$tipoSubtipo] ?? ($tipoPersonalizado !== '' ? $tipoPersonalizado : 'Economica'))
                : (self::SUBTIPOS_MAGNA[$tipoSubtipo] ?? ($tipoPersonalizado !== '' ? $tipoPersonalizado : 'Magna')));

        $trajeTipo = strtolower(trim((string) ($data['traje_tipo'] ?? $fallback['traje_tipo'] ?? 'maconico')));
        if (!in_array($trajeTipo, self::TRAJES, true)) {
            $trajeTipo = 'maconico';
        }
        $trajePersonalizado = trim((string) ($data['traje_personalizado'] ?? $fallback['traje_personalizado'] ?? ''));

        $agapeModalidade = strtolower(trim((string) ($data['agape_modalidade'] ?? $fallback['agape_modalidade'] ?? 'nao_havera')));
        if (!in_array($agapeModalidade, self::AGAPES, true)) {
            $agapeModalidade = filter_var($data['agape_ativo'] ?? $fallback['agape_ativo'] ?? false, FILTER_VALIDATE_BOOL) ? 'gratuito' : 'nao_havera';
        }
        $agapeModeloFinanceiro = strtolower(trim((string) ($data['agape_modelo_financeiro'] ?? $fallback['agape_modelo_financeiro'] ?? 'oficial_loja')));
        if (!in_array($agapeModeloFinanceiro, self::AGAPE_MODELOS_FINANCEIROS, true)) {
            $agapeModeloFinanceiro = 'oficial_loja';
        }
        $agapeValorBruto = str_replace(',', '.', trim((string) ($data['agape_valor'] ?? $fallback['agape_valor'] ?? '')));
        $agapeValor = ($agapeModalidade === 'pago' && is_numeric($agapeValorBruto)) ? round((float) $agapeValorBruto, 2) : null;

        $ordemDia = trim((string) ($data['ordem_dia'] ?? $data['resumo_publico'] ?? $data['pauta'] ?? $fallback['ordem_dia'] ?? $fallback['resumo_publico'] ?? ''));

        return [
            'data_hora_inicio' => $data['data_hora_inicio'] ?? $data['data'] ?? $fallback['data_hora_inicio'] ?? null,
            'data_hora_fim' => $data['data_hora_fim'] ?? $fallback['data_hora_fim'] ?? null,
            'tipo_sessao' => $tipoFinal,
            'grau_sessao' => $grauFinal,
            'gestao_referencia' => trim((string) ($data['gestao_referencia'] ?? $fallback['gestao_referencia'] ?? '')) ?: null,
            'natureza_sessao' => trim((string) ($data['natureza_sessao'] ?? $fallback['natureza_sessao'] ?? '')) ?: null,
            'formato_sessao' => trim((string) ($data['formato_sessao'] ?? $fallback['formato_sessao'] ?? '')) ?: null,
            'finalidade_ritual' => trim((string) ($data['finalidade_ritual'] ?? $fallback['finalidade_ritual'] ?? '')) ?: null,
            'grau_personalizado' => $grauInformado === 'Outro' ? ($grauPersonalizado !== '' ? $grauPersonalizado : null) : null,
            'tipo_sessao_principal' => $tipoPrincipal,
            'tipo_sessao_subtipo' => $tipoSubtipo !== '' ? $tipoSubtipo : null,
            'tipo_sessao_personalizado' => $tipoPersonalizado !== '' ? $tipoPersonalizado : null,
            'traje_tipo' => $trajeTipo,
            'traje_personalizado' => $trajeTipo === 'outro' ? ($trajePersonalizado !== '' ? $trajePersonalizado : null) : null,
            'agape_modalidade' => $agapeModalidade,
            'agape_modelo_financeiro' => $agapeModeloFinanceiro,
            'agape_valor' => $agapeValor,
            'ordem_dia' => $ordemDia !== '' ? $ordemDia : null,
            'templo_local' => trim((string) ($data['templo_local'] ?? $fallback['templo_local'] ?? '')) ?: null,
            'sessao_branca' => filter_var($data['sessao_branca'] ?? $fallback['sessao_branca'] ?? false, FILTER_VALIDATE_BOOL),
            'sessao_a_campo' => filter_var($data['sessao_a_campo'] ?? $fallback['sessao_a_campo'] ?? false, FILTER_VALIDATE_BOOL),
            'conta_relatorio_potencia' => filter_var($data['conta_relatorio_potencia'] ?? $fallback['conta_relatorio_potencia'] ?? true, FILTER_VALIDATE_BOOL),
            'observacao_relatorio' => trim((string) ($data['observacao_relatorio'] ?? $fallback['observacao_relatorio'] ?? '')) ?: null,
            'titulo' => trim((string) ($data['titulo'] ?? $fallback['titulo'] ?? '')) ?: null,
            'resumo_publico' => $ordemDia !== '' ? $ordemDia : null,
            'observacao_interna' => trim((string) ($data['observacao_interna'] ?? $fallback['observacao_interna'] ?? '')) ?: null,
            'status' => trim((string) ($data['status'] ?? $fallback['status'] ?? 'planejada')) ?: 'planejada',
            'agape_ativo' => $agapeModalidade !== 'nao_havera',
        ];
    }
}
