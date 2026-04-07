<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Balaustre
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function salvarPorSessao(int $sessaoId, array $data, ?string $autorId = null): bool
    {
        $atual = $this->buscarPorSessao($sessaoId);
        $dadosJson = $this->montarDadosCapturados($data);

        if ($atual) {
            $stmt = $this->db->prepare("
                UPDATE public.balaustres
                   SET numero_balaustre = :numero_balaustre,
                       template_versao = :template_versao,
                       texto_final = :texto_final,
                       dados_capturados = CAST(:dados_capturados AS jsonb),
                       preparado_por = :preparado_por,
                       preparado_em = NOW(),
                       status = CASE WHEN status = 'em_votacao' THEN status ELSE 'rascunho' END,
                       apto_votacao = CASE WHEN status = 'em_votacao' THEN apto_votacao ELSE FALSE END,
                       apto_votacao_em = CASE WHEN status = 'em_votacao' THEN apto_votacao_em ELSE NULL END,
                       apto_votacao_por = CASE WHEN status = 'em_votacao' THEN apto_votacao_por ELSE NULL END,
                       updated_at = NOW()
                 WHERE sessao_id = :sessao_id
            ");

            return $stmt->execute([
                'sessao_id' => $sessaoId,
                'numero_balaustre' => trim((string) ($data['numero_balaustre'] ?? '')) ?: null,
                'template_versao' => trim((string) ($data['template_versao'] ?? 'oficial-v1')) ?: 'oficial-v1',
                'texto_final' => trim((string) ($data['texto_final'] ?? '')) ?: null,
                'dados_capturados' => $dadosJson !== null ? json_encode($dadosJson, JSON_UNESCAPED_UNICODE) : null,
                'preparado_por' => $autorId,
            ]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.balaustres (
                sessao_id,
                numero_balaustre,
                template_versao,
                texto_final,
                dados_capturados,
                preparado_por,
                preparado_em,
                status,
                updated_at
            ) VALUES (
                :sessao_id,
                :numero_balaustre,
                :template_versao,
                :texto_final,
                CAST(:dados_capturados AS jsonb),
                :preparado_por,
                NOW(),
                'rascunho',
                NOW()
            )
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'numero_balaustre' => trim((string) ($data['numero_balaustre'] ?? '')) ?: null,
            'template_versao' => trim((string) ($data['template_versao'] ?? 'oficial-v1')) ?: 'oficial-v1',
            'texto_final' => trim((string) ($data['texto_final'] ?? '')) ?: null,
            'dados_capturados' => $dadosJson !== null ? json_encode($dadosJson, JSON_UNESCAPED_UNICODE) : null,
            'preparado_por' => $autorId,
        ]);
    }

    private function normalizarComparacao(?string $valor): string
    {
        $valor = strtolower(trim((string) $valor));
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $valor = preg_replace('/[^a-z0-9]+/', ' ', $valor) ?? '';
        return trim($valor);
    }

    private function montarDadosCapturados(array $data): ?array
    {
        $dadosCapturados = trim((string) ($data['dados_capturados'] ?? ''));
        $dadosJson = $dadosCapturados !== '' ? json_decode($dadosCapturados, true) : null;
        if ($dadosCapturados !== '' && !is_array($dadosJson)) {
            $dadosJson = ['rascunho_livre' => $dadosCapturados];
        }
        if (!is_array($dadosJson)) {
            $dadosJson = [];
        }

        $lojasFrequentes = array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            is_array($data['lojas_visitantes_frequentes'] ?? null)
                ? $data['lojas_visitantes_frequentes']
                : preg_split('/\r\n|\r|\n|,/', (string) ($data['lojas_visitantes_frequentes'] ?? ''))
        )));

        $visitanteNomes = is_array($data['palavra_visitante_nome'] ?? null) ? $data['palavra_visitante_nome'] : [];
        $visitanteLojas = is_array($data['palavra_visitante_loja'] ?? null) ? $data['palavra_visitante_loja'] : [];
        $visitanteOrientes = is_array($data['palavra_visitante_oriente'] ?? null) ? $data['palavra_visitante_oriente'] : [];
        $visitantePotencias = is_array($data['palavra_visitante_potencia'] ?? null) ? $data['palavra_visitante_potencia'] : [];
        $visitanteGraus = is_array($data['palavra_visitante_grau'] ?? null) ? $data['palavra_visitante_grau'] : [];
        $visitanteDias = is_array($data['palavra_visitante_dia_reuniao'] ?? null) ? $data['palavra_visitante_dia_reuniao'] : [];
        $visitanteFalhas = is_array($data['palavra_visitante_fala'] ?? null) ? $data['palavra_visitante_fala'] : [];

        $totalVisitantes = max(
            count($visitanteNomes),
            count($visitanteLojas),
            count($visitanteOrientes),
            count($visitantePotencias),
            count($visitanteGraus),
            count($visitanteDias),
            count($visitanteFalhas)
        );
        $visitantes = [];
        for ($i = 0; $i < $totalVisitantes; $i++) {
            $nome = trim((string) ($visitanteNomes[$i] ?? ''));
            $loja = trim((string) ($visitanteLojas[$i] ?? ''));
            $fala = trim((string) ($visitanteFalhas[$i] ?? ''));
            if ($nome === '' && $loja === '' && $fala === '') {
                continue;
            }
            $visitantes[] = [
                'nome' => $nome,
                'loja' => $loja,
                'oriente' => trim((string) ($visitanteOrientes[$i] ?? '')),
                'potencia' => trim((string) ($visitantePotencias[$i] ?? '')),
                'grau' => trim((string) ($visitanteGraus[$i] ?? '')),
                'dia_reuniao' => trim((string) ($visitanteDias[$i] ?? '')),
                'fala_resumida' => $fala,
            ];
        }

        $cargoCodigos = is_array($data['cargo_sessao_codigo'] ?? null) ? $data['cargo_sessao_codigo'] : [];
        $cargoNomes = is_array($data['cargo_sessao_nome'] ?? null) ? $data['cargo_sessao_nome'] : [];
        $cargoTitulares = is_array($data['cargo_sessao_titular_oficial'] ?? null) ? $data['cargo_sessao_titular_oficial'] : [];
        $cargoOcupantes = is_array($data['cargo_sessao_ocupante_nome'] ?? null) ? $data['cargo_sessao_ocupante_nome'] : [];
        $cargoObs = is_array($data['cargo_sessao_observacao'] ?? null) ? $data['cargo_sessao_observacao'] : [];

        $totalCargos = max(
            count($cargoCodigos),
            count($cargoNomes),
            count($cargoTitulares),
            count($cargoOcupantes),
            count($cargoObs)
        );
        $cargosSessao = [];
        for ($i = 0; $i < $totalCargos; $i++) {
            $codigo = strtoupper(trim((string) ($cargoCodigos[$i] ?? '')));
            $cargoNome = trim((string) ($cargoNomes[$i] ?? ''));
            $titularOficial = trim((string) ($cargoTitulares[$i] ?? ''));
            $ocupanteNome = trim((string) ($cargoOcupantes[$i] ?? ''));
            $observacao = trim((string) ($cargoObs[$i] ?? ''));

            if ($codigo === '' && $cargoNome === '' && $ocupanteNome === '' && $titularOficial === '') {
                continue;
            }

            $tipoOcupacao = 'regular';
            if ($ocupanteNome !== '' && $titularOficial !== '') {
                if ($this->normalizarComparacao($ocupanteNome) !== $this->normalizarComparacao($titularOficial)) {
                    $tipoOcupacao = 'ad_hoc';
                }
            }

            $cargosSessao[] = [
                'codigo' => $codigo,
                'cargo_nome' => $cargoNome,
                'titular_oficial' => $titularOficial,
                'ocupante_nome' => $ocupanteNome,
                'tipo_ocupacao' => $tipoOcupacao,
                'observacao' => $observacao,
            ];
        }

        $dadosJson['palavra_bem_ordem'] = [
            'lojas_frequentes' => array_values(array_unique($lojasFrequentes)),
            'visitantes' => $visitantes,
        ];
        $dadosJson['cargos_sessao'] = $cargosSessao;
        $dadosJson['observacoes_secretaria'] = trim((string) ($data['observacoes_secretaria'] ?? ''));

        return $dadosJson;
    }

    public function marcarAptoVotacao(int $balaustreId, ?string $autorId = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE public.balaustres
               SET apto_votacao = TRUE,
                   apto_votacao_em = NOW(),
                   apto_votacao_por = :autor_id,
                   status = 'apto_votacao',
                   updated_at = NOW()
             WHERE id = :id
               AND status <> 'em_votacao'
        ");

        return $stmt->execute([
            'id' => $balaustreId,
            'autor_id' => $autorId,
        ]);
    }

    public function abrirVotacao(int $balaustreId, ?string $abertoPor = null): array
    {
        $balaustre = $this->buscarPorId($balaustreId);
        if (!$balaustre) {
            return ['ok' => false, 'erro' => 'Balaustre nao encontrado.'];
        }
        if (!(bool) $balaustre['apto_votacao']) {
            return ['ok' => false, 'erro' => 'Balaustre ainda nao esta apto para votacao.'];
        }
        if (($balaustre['status'] ?? '') === 'em_votacao') {
            return ['ok' => false, 'erro' => 'A votacao deste balaustre ja esta aberta.'];
        }

        $this->db->beginTransaction();
        try {
            $votacaoStmt = $this->db->prepare("
                INSERT INTO public.balaustre_votacoes (
                    balaustre_id,
                    aberta_por,
                    aberta_em,
                    status
                ) VALUES (
                    :balaustre_id,
                    :aberta_por,
                    NOW(),
                    'aberta'
                )
                RETURNING id
            ");
            $votacaoStmt->execute([
                'balaustre_id' => $balaustreId,
                'aberta_por' => $abertoPor,
            ]);
            $votacaoId = (int) $votacaoStmt->fetchColumn();

            $presentesStmt = $this->db->prepare("
                SELECT obreiro_id
                FROM public.presencas_sessao
                WHERE sessao_id = :sessao_id
                  AND presente = TRUE
            ");
            $presentesStmt->execute(['sessao_id' => (int) $balaustre['sessao_id']]);
            $presentes = $presentesStmt->fetchAll(PDO::FETCH_COLUMN);

            if ($presentes === []) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Nao ha presencas registradas para compor a lista apta.'];
            }

            $votanteStmt = $this->db->prepare("
                INSERT INTO public.balaustre_votantes (
                    votacao_id,
                    obreiro_id,
                    elegivel
                ) VALUES (
                    :votacao_id,
                    :obreiro_id,
                    TRUE
                )
            ");
            foreach ($presentes as $obreiroId) {
                $votanteStmt->execute([
                    'votacao_id' => $votacaoId,
                    'obreiro_id' => (string) $obreiroId,
                ]);
            }

            $this->db->prepare("
                UPDATE public.balaustres
                   SET status = 'em_votacao',
                       updated_at = NOW()
                 WHERE id = :id
            ")->execute(['id' => $balaustreId]);

            $this->db->commit();
            return [
                'ok' => true,
                'votacao_id' => $votacaoId,
                'total_votantes' => count($presentes),
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'erro' => 'Falha ao abrir votacao: ' . $e->getMessage()];
        }
    }

    public function listarRecentes(int $limite = 30): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT
                b.*,
                s.titulo AS sessao_titulo,
                s.data_hora_inicio,
                (
                    SELECT COUNT(*)
                    FROM public.balaustre_votantes bv
                    JOIN public.balaustre_votacoes v ON v.id = bv.votacao_id
                    WHERE v.balaustre_id = b.id
                      AND v.status = 'aberta'
                ) AS total_votantes_abertos
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            ORDER BY b.updated_at DESC, b.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarElegibilidadeDoObreiroNosBalaustres(string $obreiroId, array $balaustreIds): array
    {
        $balaustreIds = array_values(array_filter(array_map('intval', $balaustreIds), static fn ($id) => $id > 0));
        if ($obreiroId === '' || $balaustreIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($balaustreIds), '?'));
        $sql = "
            SELECT
                v.balaustre_id,
                bv.elegivel
            FROM public.balaustre_votacoes v
            JOIN public.balaustre_votantes bv ON bv.votacao_id = v.id
            WHERE v.status = 'aberta'
              AND v.balaustre_id IN ({$placeholders})
              AND bv.obreiro_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $params = array_merge($balaustreIds, [$obreiroId]);
        $stmt->execute($params);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $mapa[(int) ($row['balaustre_id'] ?? 0)] = (bool) ($row['elegivel'] ?? false);
        }
        return $mapa;
    }

    public function listarAbertosParaObreiro(string $obreiroId, bool $incluirSemElegibilidade = false): array
    {
        if ($obreiroId === '' && !$incluirSemElegibilidade) {
            return [];
        }

        $sql = "
            SELECT
                b.id,
                b.numero_balaustre,
                b.status,
                b.updated_at,
                s.titulo AS sessao_titulo,
                s.tipo_sessao,
                s.grau_sessao,
                s.data_hora_inicio,
                v.id AS votacao_id,
                CASE
                    WHEN bv.obreiro_id IS NULL THEN FALSE
                    ELSE COALESCE(bv.elegivel, FALSE)
                END AS elegivel
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            JOIN public.balaustre_votacoes v ON v.balaustre_id = b.id AND v.status = 'aberta'
            LEFT JOIN public.balaustre_votantes bv ON bv.votacao_id = v.id AND bv.obreiro_id = :obreiro_id
            WHERE b.status = 'em_votacao'
        ";

        if (!$incluirSemElegibilidade) {
            $sql .= " AND COALESCE(bv.elegivel, FALSE) = TRUE";
        }

        $sql .= " ORDER BY s.data_hora_inicio DESC, b.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['obreiro_id' => $obreiroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarVotoPorBalaustre(int $balaustreId, string $obreiroId, string $voto, ?string $justificativa = null): array
    {
        $voto = trim($voto);
        if (!in_array($voto, ['aprovar', 'pedir_correcao', 'rejeitar'], true)) {
            return ['ok' => false, 'erro' => 'Voto invalido.'];
        }

        $votacao = $this->buscarVotacaoAbertaPorBalaustre($balaustreId);
        if (!$votacao) {
            return ['ok' => false, 'erro' => 'Nao existe votacao aberta para este balaustre.'];
        }

        $elegivelStmt = $this->db->prepare("
            SELECT elegivel
            FROM public.balaustre_votantes
            WHERE votacao_id = :votacao_id
              AND obreiro_id = :obreiro_id
            LIMIT 1
        ");
        $elegivelStmt->execute([
            'votacao_id' => (int) $votacao['id'],
            'obreiro_id' => $obreiroId,
        ]);
        $elegivel = $elegivelStmt->fetch(PDO::FETCH_ASSOC);
        if (!$elegivel || !(bool) ($elegivel['elegivel'] ?? false)) {
            return ['ok' => false, 'erro' => 'Somente presentes aptos podem votar.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.balaustre_votos (
                votacao_id,
                obreiro_id,
                voto,
                justificativa,
                votado_em
            ) VALUES (
                :votacao_id,
                :obreiro_id,
                :voto,
                :justificativa,
                NOW()
            )
            ON CONFLICT (votacao_id, obreiro_id)
            DO UPDATE SET
                voto = EXCLUDED.voto,
                justificativa = EXCLUDED.justificativa,
                votado_em = NOW()
        ");

        $ok = $stmt->execute([
            'votacao_id' => (int) $votacao['id'],
            'obreiro_id' => $obreiroId,
            'voto' => $voto,
            'justificativa' => $justificativa !== null && trim($justificativa) !== '' ? trim($justificativa) : null,
        ]);

        return $ok ? ['ok' => true] : ['ok' => false, 'erro' => 'Falha ao registrar voto.'];
    }

    public function encerrarVotacaoPorBalaustre(int $balaustreId): array
    {
        $votacao = $this->buscarVotacaoAbertaPorBalaustre($balaustreId);
        if (!$votacao) {
            return ['ok' => false, 'erro' => 'Nao existe votacao aberta para este balaustre.'];
        }

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) FILTER (WHERE voto = 'aprovar') AS total_aprovar,
                COUNT(*) FILTER (WHERE voto = 'pedir_correcao') AS total_correcao,
                COUNT(*) FILTER (WHERE voto = 'rejeitar') AS total_rejeitar
            FROM public.balaustre_votos
            WHERE votacao_id = :votacao_id
        ");
        $stmt->execute(['votacao_id' => (int) $votacao['id']]);
        $totais = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_aprovar' => 0,
            'total_correcao' => 0,
            'total_rejeitar' => 0,
        ];

        $rejeitar = (int) ($totais['total_rejeitar'] ?? 0);
        $correcao = (int) ($totais['total_correcao'] ?? 0);
        $aprovar = (int) ($totais['total_aprovar'] ?? 0);

        if ($rejeitar > 0) {
            $statusBalaustre = 'rejeitado';
        } elseif ($correcao > 0) {
            $statusBalaustre = 'em_correcao';
        } elseif ($aprovar > 0) {
            $statusBalaustre = 'aprovado';
        } else {
            $statusBalaustre = 'em_correcao';
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare("
                UPDATE public.balaustre_votacoes
                   SET status = 'encerrada',
                       encerrada_em = NOW()
                 WHERE id = :id
            ")->execute(['id' => (int) $votacao['id']]);

            $this->db->prepare("
                UPDATE public.balaustres
                   SET status = :status,
                       updated_at = NOW()
                 WHERE id = :id
            ")->execute([
                'status' => $statusBalaustre,
                'id' => $balaustreId,
            ]);

            $this->db->commit();
            return ['ok' => true, 'status' => $statusBalaustre];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'erro' => 'Falha ao encerrar votacao: ' . $e->getMessage()];
        }
    }

    public function buscarPorSessao(int $sessaoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.balaustres
            WHERE sessao_id = :sessao_id
            LIMIT 1
        ");
        $stmt->execute(['sessao_id' => $sessaoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.balaustres
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function buscarVotacaoAbertaPorBalaustre(int $balaustreId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.balaustre_votacoes
            WHERE balaustre_id = :balaustre_id
              AND status = 'aberta'
            ORDER BY aberta_em DESC
            LIMIT 1
        ");
        $stmt->execute(['balaustre_id' => $balaustreId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
