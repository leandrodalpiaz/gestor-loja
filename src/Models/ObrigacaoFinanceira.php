<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use DateInterval;
use DateTimeImmutable;
use PDO;
use Throwable;

class ObrigacaoFinanceira
{
    use ResolvesStoreTenant;

    private PDO $db;
    private ?array $parametrosFinanceiros = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $dados, $usuarioId = null): bool
    {
        $parametros = $this->obterParametrosFinanceiros();
        $obreiroId = trim((string) ($dados['obreiro_id'] ?? ''));
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $tipoObrigacao = trim((string) ($dados['tipo_obrigacao'] ?? 'outra'));
        $recorrencia = trim((string) ($dados['recorrencia'] ?? 'avulsa'));
        $valorBase = (float) ($dados['valor_base'] ?? 0);
        if ($tipoObrigacao === 'mensalidade' && $valorBase <= 0) {
            $valorBase = (float) ($parametros['mensalidade_valor_padrao'] ?? 150);
        }
        $parcelasTotal = max(1, (int) ($dados['parcelas_total'] ?? 1));
        $inicio = $this->parseDate($dados['inicio_competencia'] ?? null) ?? new DateTimeImmutable('today');
        $fimInformado = $this->parseDate($dados['fim_competencia'] ?? null);
        $fim = $this->resolverFimCompetencia($inicio, $fimInformado, $recorrencia, $parcelasTotal);

        if ($obreiroId === '' || $titulo === '' || $valorBase <= 0) {
            return false;
        }

        $categoriaId = (int) ($dados['categoria_id'] ?? 0);
        if ($categoriaId <= 0) {
            $categoriaId = null;
        }

        $diaPadrao = (int) ($parametros['mensalidade_dia_sugerido'] ?? 10);
        $diaVencimento = max(1, min(31, (int) ($dados['dia_vencimento'] ?? ($tipoObrigacao === 'mensalidade' ? $diaPadrao : 10))));
        $mesReferenciaFixa = (int) ($dados['mes_referencia_fixa'] ?? 0);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO obrigacoes_financeiras (
                    loja_id, obreiro_id, categoria_id, titulo, tipo_obrigacao, recorrencia, status,
                    valor_base, parcelas_total, permite_parcelamento, forma_cobranca,
                    somar_a_mensalidade, aceita_pagamento_previo, aceita_pagamento_posterior,
                    dia_vencimento, mes_referencia_fixa, inicio_competencia, fim_competencia,
                    instrucoes_pagamento, observacao, criado_por
                ) VALUES (
                    :loja_id, :obreiro_id, :categoria_id, :titulo, :tipo_obrigacao, :recorrencia, 'ativa',
                    :valor_base, :parcelas_total, :permite_parcelamento, :forma_cobranca,
                    :somar_a_mensalidade, :aceita_pagamento_previo, :aceita_pagamento_posterior,
                    :dia_vencimento, :mes_referencia_fixa, :inicio_competencia, :fim_competencia,
                    :instrucoes_pagamento, :observacao, :criado_por
                )
            ");

            $stmt->execute([
                'loja_id' => $this->obterLojaAtualId(),
                'obreiro_id' => $obreiroId,
                'categoria_id' => $categoriaId,
                'titulo' => $titulo,
                'tipo_obrigacao' => $tipoObrigacao,
                'recorrencia' => $recorrencia,
                'valor_base' => $valorBase,
                'parcelas_total' => $parcelasTotal,
                'permite_parcelamento' => !empty($dados['permite_parcelamento']) || $recorrencia === 'parcelado',
                'forma_cobranca' => trim((string) ($dados['forma_cobranca'] ?? 'livre')) ?: 'livre',
                'somar_a_mensalidade' => !empty($dados['somar_a_mensalidade']),
                'aceita_pagamento_previo' => !empty($dados['aceita_pagamento_previo']),
                'aceita_pagamento_posterior' => !array_key_exists('aceita_pagamento_posterior', $dados) || !empty($dados['aceita_pagamento_posterior']),
                'dia_vencimento' => $diaVencimento,
                'mes_referencia_fixa' => $mesReferenciaFixa > 0 ? $mesReferenciaFixa : null,
                'inicio_competencia' => $inicio->format('Y-m-d'),
                'fim_competencia' => $fim?->format('Y-m-d'),
                'instrucoes_pagamento' => trim((string) ($dados['instrucoes_pagamento'] ?? '')) ?: null,
                'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
                'criado_por' => $usuarioId ?: null,
            ]);

            $obrigacaoId = (int) $this->db->lastInsertId();
            $parcelas = $this->gerarParcelas($obrigacaoId, [
                'categoria_id' => $categoriaId,
                'recorrencia' => $recorrencia,
                'valor_base' => $valorBase,
                'parcelas_total' => $parcelasTotal,
                'dia_vencimento' => $diaVencimento,
                'mes_referencia_fixa' => $mesReferenciaFixa,
                'inicio_competencia' => $inicio,
                'fim_competencia' => $fim,
            ]);

            $insertParcela = $this->db->prepare("
                INSERT INTO obrigacao_financeira_parcelas (
                    obrigacao_id, numero_parcela, competencia_label, competencia_mes, competencia_ano,
                    vencimento, valor_previsto, status, categoria_id, observacao
                ) VALUES (
                    :obrigacao_id, :numero_parcela, :competencia_label, :competencia_mes, :competencia_ano,
                    :vencimento, :valor_previsto, 'pendente', :categoria_id, :observacao
                )
            ");

            foreach ($parcelas as $parcela) {
                $insertParcela->execute($parcela);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Falha ao criar obrigacao financeira: ' . $e->getMessage());
            return false;
        }
    }

    public function quitarParcela(int $parcelaId, array $dados, $usuarioId = null): bool
    {
        $parcela = $this->obterParcelaPorId($parcelaId);
        if (!$parcela || ($parcela['status'] ?? '') === 'pago') {
            return false;
        }

        $valorPago = (float) ($dados['valor_pago'] ?? $parcela['valor_previsto'] ?? 0);
        if ($valorPago <= 0) {
            return false;
        }

        $dataPagamento = $this->parseDate($dados['pago_em'] ?? null) ?? new DateTimeImmutable('today');
        $categoriaId = (int) ($dados['categoria_id'] ?? ($parcela['categoria_id'] ?? $parcela['obrigacao_categoria_id'] ?? 0));
        $categoriaId = $categoriaId > 0 ? $categoriaId : null;
        $descricao = trim((string) ($dados['descricao'] ?? ''));
        if ($descricao === '') {
            $descricao = sprintf(
                '%s%s',
                (string) ($parcela['titulo'] ?? 'Obrigacao financeira'),
                !empty($parcela['numero_parcela']) ? ' - parcela ' . $parcela['numero_parcela'] : ''
            );
        }

        try {
            $this->db->beginTransaction();

            $lancamentoModel = new LancamentoFinanceiro();
            $lancamentoOk = $lancamentoModel->criar([
                'tipo' => 'entrada',
                'categoria_id' => $categoriaId,
                'valor' => $valorPago,
                'data_lancamento' => $dataPagamento->format('Y-m-d'),
                'descricao' => $descricao,
                'obreiro_id' => $parcela['obreiro_id'] ?? null,
                'mes_ref' => (int) ($parcela['competencia_mes'] ?: $dataPagamento->format('n')),
                'ano_ref' => (int) ($parcela['competencia_ano'] ?: $dataPagamento->format('Y')),
                'created_by' => $usuarioId,
            ]);

            if (!$lancamentoOk) {
                throw new \RuntimeException('Não foi possível registrar o lançamento financeiro.');
            }

            $lancamentoId = (int) $this->db->lastInsertId();
            $stmt = $this->db->prepare("
                UPDATE obrigacao_financeira_parcelas
                SET status = 'pago',
                    pago_em = :pago_em,
                    lancamento_id = :lancamento_id,
                    categoria_id = COALESCE(:categoria_id, categoria_id),
                    quitado_por = :quitado_por,
                    quitado_em = CURRENT_TIMESTAMP,
                    observacao = :observacao,
                    updated_at = NOW()
                WHERE id = :id
                  AND EXISTS (
                      SELECT 1
                      FROM obrigacoes_financeiras ofi
                      WHERE ofi.id = obrigacao_financeira_parcelas.obrigacao_id
                        AND ofi.loja_id = :loja_id
                  )
            ");
            $stmt->execute([
                'pago_em' => $dataPagamento->format('Y-m-d'),
                'lancamento_id' => $lancamentoId,
                'categoria_id' => $categoriaId,
                'quitado_por' => $usuarioId,
                'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
                'id' => $parcelaId,
                'loja_id' => $this->obterLojaAtualId(),
            ]);

            if (strtoupper((string) ($parcela['categoria_codigo'] ?? '')) === 'MENSALIDADE' && !empty($parcela['obreiro_id']) && !empty($parcela['competencia_mes']) && !empty($parcela['competencia_ano'])) {
                (new MensalidadeStatus())->registrar(
                    (string) $parcela['obreiro_id'],
                    (int) $parcela['competencia_mes'],
                    (int) $parcela['competencia_ano'],
                    'pago'
                );
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Falha ao quitar parcela financeira: ' . $e->getMessage());
            return false;
        }
    }

    public function atualizarParcela(int $parcelaId, array $dados): bool
    {
        $parcela = $this->obterParcelaPorId($parcelaId);
        if (!$parcela) {
            return false;
        }

        $valorPrevisto = (float) ($dados['valor_previsto'] ?? ($parcela['valor_previsto'] ?? 0));
        if ($valorPrevisto <= 0) {
            return false;
        }

        $vencimento = $this->parseDate($dados['vencimento'] ?? null);
        $competenciaMes = max(1, min(12, (int) ($dados['competencia_mes'] ?? ($parcela['competencia_mes'] ?? 0))));
        $competenciaAno = max(2000, (int) ($dados['competencia_ano'] ?? ($parcela['competencia_ano'] ?? 0)));
        $competenciaLabel = sprintf('%02d/%04d', $competenciaMes, $competenciaAno);

        if (!$vencimento instanceof DateTimeImmutable) {
            $vencimento = new DateTimeImmutable(sprintf('%04d-%02d-10', $competenciaAno, $competenciaMes));
        }

        $stmt = $this->db->prepare("
            UPDATE obrigacao_financeira_parcelas
            SET competencia_label = :competencia_label,
                competencia_mes = :competencia_mes,
                competencia_ano = :competencia_ano,
                vencimento = :vencimento,
                valor_previsto = :valor_previsto,
                observacao = :observacao,
                updated_at = NOW()
            WHERE id = :id
              AND EXISTS (
                  SELECT 1
                  FROM obrigacoes_financeiras ofi
                  WHERE ofi.id = obrigacao_financeira_parcelas.obrigacao_id
                    AND ofi.loja_id = :loja_id
              )
        ");

        return $stmt->execute([
            'competencia_label' => $competenciaLabel,
            'competencia_mes' => $competenciaMes,
            'competencia_ano' => $competenciaAno,
            'vencimento' => $vencimento->format('Y-m-d'),
            'valor_previsto' => $valorPrevisto,
            'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
            'id' => $parcelaId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function excluirParcela(int $parcelaId): bool
    {
        $parcela = $this->obterParcelaPorId($parcelaId);
        if (!$parcela) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            if (($parcela['status'] ?? '') === 'pago' && !empty($parcela['lancamento_id'])) {
                (new LancamentoFinanceiro())->deletar((int) $parcela['lancamento_id']);
            }

            $stmt = $this->db->prepare("
                DELETE FROM obrigacao_financeira_parcelas
                WHERE id = :id
                  AND EXISTS (
                      SELECT 1
                      FROM obrigacoes_financeiras ofi
                      WHERE ofi.id = obrigacao_financeira_parcelas.obrigacao_id
                        AND ofi.loja_id = :loja_id
                  )
            ");
            $stmt->execute([
                'id' => $parcelaId,
                'loja_id' => $this->obterLojaAtualId(),
            ]);

            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM obrigacao_financeira_parcelas WHERE obrigacao_id = :obrigacao_id");
            $stmtCount->execute(['obrigacao_id' => $parcela['obrigacao_id']]);
            $restantes = (int) $stmtCount->fetchColumn();

            if ($restantes === 0) {
                $stmtDeleteObrigacao = $this->db->prepare("DELETE FROM obrigacoes_financeiras WHERE id = :id AND loja_id = :loja_id");
                $stmtDeleteObrigacao->execute([
                    'id' => $parcela['obrigacao_id'],
                    'loja_id' => $this->obterLojaAtualId(),
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Falha ao excluir parcela financeira: ' . $e->getMessage());
            return false;
        }
    }

    public function gerarMensalidadesAno(int $ano, $usuarioId = null): array
    {
        $parametros = $this->obterParametrosFinanceiros();
        $categoriaId = $this->obterCategoriaIdPorCodigo('MENSALIDADE');
        $obreiros = (new Obreiro())->getAllAtivos();
        $resultado = ['geradas' => 0, 'ignoradas' => 0, 'isentas' => 0];

        foreach ($obreiros as $obreiro) {
            $obreiroId = (string) ($obreiro['id'] ?? '');
            if ($obreiroId === '') {
                continue;
            }

            if ($this->obreiroEstaIsento($obreiroId, 'mensalidade', new DateTimeImmutable(sprintf('%04d-01-01', $ano)))) {
                $resultado['isentas']++;
                continue;
            }

            if ($this->obrigacaoJaExiste($obreiroId, 'Mensalidade ' . $ano)) {
                $resultado['ignoradas']++;
                continue;
            }

            $ok = $this->criar([
                'obreiro_id' => $obreiroId,
                'categoria_id' => $categoriaId,
                'titulo' => 'Mensalidade ' . $ano,
                'tipo_obrigacao' => 'mensalidade',
                'recorrencia' => 'mensal',
                'valor_base' => (float) ($parametros['mensalidade_valor_padrao'] ?? 150),
                'parcelas_total' => 12,
                'dia_vencimento' => (int) ($parametros['mensalidade_dia_sugerido'] ?? 10),
                'inicio_competencia' => sprintf('%04d-01-01', $ano),
                'fim_competencia' => sprintf('%04d-12-01', $ano),
                'forma_cobranca' => 'pix',
                'aceita_pagamento_posterior' => true,
                'instrucoes_pagamento' => 'Mensalidade da Loja com orientacao de pagamento dentro do mes vigente.',
            ], $usuarioId);

            if ($ok) {
                $resultado['geradas']++;
            } else {
                $resultado['ignoradas']++;
            }
        }

        return $resultado;
    }

    public function designarBibliotecaMes(int $mes, int $ano, array $obreirosIds, ?string $observacao, $usuarioId = null): array
    {
        $parametros = $this->obterParametrosFinanceiros();
        $categoriaId = $this->obterCategoriaIdPorCodigo('CONTRIBUICAO_BIBLIOTECA');
        $valor = (float) ($parametros['contribuicao_biblioteca_valor_padrao'] ?? 40);
        $competencia = new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes));
        $resultado = ['geradas' => 0, 'ignoradas' => 0, 'isentas' => 0];
        $obreirosIds = array_values(array_unique(array_filter(array_map('strval', $obreirosIds))));

        foreach ($obreirosIds as $obreiroId) {
            if ($this->obreiroEstaIsento($obreiroId, 'biblioteca', $competencia)) {
                $resultado['isentas']++;
                continue;
            }

            if ($this->bibliotecaJaDesignada($obreiroId, $mes, $ano) || $this->obrigacaoJaExiste($obreiroId, sprintf('Contribuicao Biblioteca %02d/%04d', $mes, $ano))) {
                $resultado['ignoradas']++;
                continue;
            }

            $stmt = $this->db->prepare("
                INSERT INTO biblioteca_contribuintes_mensal (
                    mes_ref, ano_ref, obreiro_id, valor_previsto, observacao, criado_por
                ) VALUES (
                    :mes_ref, :ano_ref, :obreiro_id, :valor_previsto, :observacao, :criado_por
                )
            ");
            $stmt->execute([
                'mes_ref' => $mes,
                'ano_ref' => $ano,
                'obreiro_id' => $obreiroId,
                'valor_previsto' => $valor,
                'observacao' => $observacao ?: null,
                'criado_por' => $usuarioId ?: null,
            ]);

            $ok = $this->criar([
                'obreiro_id' => $obreiroId,
                'categoria_id' => $categoriaId,
                'titulo' => sprintf('Contribuicao Biblioteca %02d/%04d', $mes, $ano),
                'tipo_obrigacao' => 'biblioteca',
                'recorrencia' => 'avulsa',
                'valor_base' => $valor,
                'parcelas_total' => 1,
                'dia_vencimento' => (int) ($parametros['mensalidade_dia_sugerido'] ?? 10),
                'mes_referencia_fixa' => $mes,
                'inicio_competencia' => $competencia->format('Y-m-d'),
                'fim_competencia' => $competencia->format('Y-m-d'),
                'forma_cobranca' => 'mensalidade',
                'somar_a_mensalidade' => true,
                'aceita_pagamento_posterior' => true,
                'instrucoes_pagamento' => 'Contribuicao anual da biblioteca somada a mensalidade do mes designado.',
                'observacao' => $observacao,
            ], $usuarioId);

            if ($ok) {
                $resultado['geradas']++;
            } else {
                $resultado['ignoradas']++;
            }
        }

        return $resultado;
    }

    public function programarBibliotecaRenascencaAno(int $ano, $usuarioId = null): array
    {
        $ano = max(2020, min(2100, $ano));
        $obreirosPorNome = [];
        foreach ((new Obreiro())->getAllAtivos() as $obreiro) {
            $obreiroId = (string) ($obreiro['id'] ?? '');
            $nome = (string) ($obreiro['nome_historico'] ?? $obreiro['nome'] ?? '');
            if ($obreiroId === '' || $nome === '') {
                continue;
            }
            $obreirosPorNome[$this->normalizarNome($nome)] = $obreiroId;
        }

        $resultado = ['geradas' => 0, 'ignoradas' => 0, 'isentas' => 0, 'nao_encontrados' => []];
        foreach ($this->escalaBibliotecaRenascenca() as $mes => $nomes) {
            $ids = [];
            foreach ($nomes as $nome) {
                $chave = $this->normalizarNome($nome);
                if (isset($obreirosPorNome[$chave])) {
                    $ids[] = $obreirosPorNome[$chave];
                } else {
                    $resultado['nao_encontrados'][] = $nome;
                }
            }

            if ($ids === []) {
                continue;
            }

            $parcial = $this->designarBibliotecaMes(
                (int) $mes,
                $ano,
                $ids,
                'Programação anual obrigatória da contribuição da Biblioteca.',
                $usuarioId
            );

            $resultado['geradas'] += (int) ($parcial['geradas'] ?? 0);
            $resultado['ignoradas'] += (int) ($parcial['ignoradas'] ?? 0);
            $resultado['isentas'] += (int) ($parcial['isentas'] ?? 0);
        }

        $resultado['nao_encontrados'] = array_values(array_unique($resultado['nao_encontrados']));
        return $resultado;
    }

    public function registrarIsencao(array $dados, $usuarioId = null): bool
    {
        $obreiroId = trim((string) ($dados['obreiro_id'] ?? ''));
        $tipoObrigacao = trim((string) ($dados['tipo_obrigacao'] ?? ''));
        $inicio = trim((string) ($dados['inicio_competencia'] ?? ''));

        if ($obreiroId === '' || $tipoObrigacao === '' || $inicio === '') {
            return false;
        }

        $categoriaId = (int) ($dados['categoria_id'] ?? 0);
        $categoriaId = $categoriaId > 0 ? $categoriaId : null;

        $stmt = $this->db->prepare("
            INSERT INTO isencoes_financeiras (
                obreiro_id, tipo_obrigacao, categoria_id, inicio_competencia, fim_competencia,
                observacao, ativo, criado_por
            ) VALUES (
                :obreiro_id, :tipo_obrigacao, :categoria_id, :inicio_competencia, :fim_competencia,
                :observacao, TRUE, :criado_por
            )
        ");

        return $stmt->execute([
            'obreiro_id' => $obreiroId,
            'tipo_obrigacao' => $tipoObrigacao,
            'categoria_id' => $categoriaId,
            'inicio_competencia' => $inicio,
            'fim_competencia' => trim((string) ($dados['fim_competencia'] ?? '')) ?: null,
            'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
            'criado_por' => $usuarioId ?: null,
        ]);
    }

    public function listarResumoTesouraria(array $filtros = []): array
    {
        $params = [];
        $where = ["o.ativo = true"];

        $obreiroId = trim((string) ($filtros['obreiro_id'] ?? ''));
        if ($obreiroId !== '') {
            $where[] = "o.id = :obreiro_id";
            $params['obreiro_id'] = $obreiroId;
        }

        $busca = trim((string) ($filtros['busca'] ?? ''));
        if ($busca !== '') {
            $where[] = "(o.nome ILIKE :busca OR COALESCE(o.nome_historico, '') ILIKE :busca)";
            $params['busca'] = '%' . $busca . '%';
        }

        if (!empty($filtros['somente_em_aberto'])) {
            $where[] = "EXISTS (
                SELECT 1
                FROM obrigacoes_financeiras ofi2
                JOIN obrigacao_financeira_parcelas ofp2 ON ofp2.obrigacao_id = ofi2.id
                WHERE ofi2.obreiro_id = o.id
                  AND ofi2.status = 'ativa'
                  AND ofp2.status <> 'pago'
            )";
        }

        $sql = "
            SELECT
                o.id,
                COALESCE(o.nome_historico, o.nome) AS nome,
                COUNT(DISTINCT ofi.id) AS obrigacoes_ativas,
                COUNT(ofp.id) FILTER (WHERE ofp.status <> 'pago') AS parcelas_em_aberto,
                COUNT(ofp.id) FILTER (WHERE ofp.status <> 'pago' AND ofp.vencimento < CURRENT_DATE) AS parcelas_atrasadas,
                COALESCE(SUM(ofp.valor_previsto) FILTER (WHERE ofp.status <> 'pago'), 0) AS saldo_em_aberto,
                MIN(ofp.vencimento) FILTER (WHERE ofp.status <> 'pago') AS proximo_vencimento
            FROM obreiros o
            LEFT JOIN obrigacoes_financeiras ofi
                ON ofi.obreiro_id = o.id
               AND ofi.loja_id = :loja_id
               AND ofi.status = 'ativa'
            LEFT JOIN obrigacao_financeira_parcelas ofp
                ON ofp.obrigacao_id = ofi.id
            WHERE o.loja_id = :loja_id
              AND " . implode(' AND ', $where) . "
            GROUP BY o.id, COALESCE(o.nome_historico, o.nome)
            ORDER BY parcelas_atrasadas DESC, saldo_em_aberto DESC, nome ASC
        ";

        $stmt = $this->db->prepare($sql);
        $params['loja_id'] = $this->obterLojaAtualId();
        $stmt->execute($params);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($registros as &$registro) {
            $resumo = $this->obterResumoObreiro((string) $registro['id']);
            $registro['parcelas_em_aberto'] = $resumo['parcelas_em_aberto'];
            $registro['parcelas_atrasadas'] = $resumo['parcelas_atrasadas'];
            $registro['saldo_em_aberto'] = $resumo['saldo_em_aberto'];
            $registro['proximo_vencimento'] = $resumo['proximo_vencimento'];
        }
        unset($registro);

        usort($registros, function (array $a, array $b): int {
            $comparacaoAtraso = ((int) ($b['parcelas_atrasadas'] ?? 0)) <=> ((int) ($a['parcelas_atrasadas'] ?? 0));
            if ($comparacaoAtraso !== 0) {
                return $comparacaoAtraso;
            }

            $comparacaoSaldo = ((float) ($b['saldo_em_aberto'] ?? 0)) <=> ((float) ($a['saldo_em_aberto'] ?? 0));
            if ($comparacaoSaldo !== 0) {
                return $comparacaoSaldo;
            }

            return strcmp((string) ($a['nome'] ?? ''), (string) ($b['nome'] ?? ''));
        });

        return $registros;
    }

    public function listarPorObreiro(string $obreiroId): array
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $obreiroId)) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT
                ofi.*,
                c.nome AS categoria_nome,
                c.codigo AS categoria_codigo
            FROM obrigacoes_financeiras ofi
            LEFT JOIN categorias_financeiras c ON c.id = ofi.categoria_id
            WHERE ofi.obreiro_id = :obreiro_id
              AND ofi.loja_id = :loja_id
            ORDER BY ofi.created_at DESC, ofi.id DESC
        ");
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $obrigacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($obrigacoes === []) {
            return [];
        }

        $parcelaStmt = $this->db->prepare("
            SELECT
                ofp.*,
                c.nome AS categoria_nome,
                c.codigo AS categoria_codigo
            FROM obrigacao_financeira_parcelas ofp
            LEFT JOIN categorias_financeiras c ON c.id = ofp.categoria_id
            WHERE ofp.obrigacao_id = :obrigacao_id
            ORDER BY ofp.numero_parcela ASC, ofp.vencimento ASC
        ");

        foreach ($obrigacoes as &$obrigacao) {
            $parcelaStmt->execute(['obrigacao_id' => $obrigacao['id']]);
            $obrigacao['parcelas'] = $parcelaStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($obrigacao['parcelas'] as &$parcela) {
                $parcela = $this->aplicarEstadoExibicaoParcela($parcela);
            }
            unset($parcela);
            $obrigacao['resumo'] = $this->resumirObrigacao($obrigacao['parcelas']);
        }
        unset($obrigacao);

        return $obrigacoes;
    }

    public function obterResumoObreiro(string $obreiroId): array
    {
        $resumo = [
            'parcelas_em_aberto' => 0,
            'parcelas_atrasadas' => 0,
            'saldo_em_aberto' => 0,
            'total_pago' => 0,
            'proximo_vencimento' => null,
        ];

        foreach ($this->listarPorObreiro($obreiroId) as $obrigacao) {
            foreach ($obrigacao['parcelas'] as $parcela) {
                if (!empty($parcela['quitado_na_exibicao'])) {
                    $resumo['total_pago'] += (float) ($parcela['valor_previsto'] ?? 0);
                    continue;
                }

                $resumo['parcelas_em_aberto']++;
                $resumo['saldo_em_aberto'] += (float) ($parcela['valor_previsto'] ?? 0);
                if (!empty($parcela['em_atraso'])) {
                    $resumo['parcelas_atrasadas']++;
                }
                if (!empty($parcela['vencimento']) && ($resumo['proximo_vencimento'] === null || $parcela['vencimento'] < $resumo['proximo_vencimento'])) {
                    $resumo['proximo_vencimento'] = $parcela['vencimento'];
                }
            }
        }

        return $resumo;
    }

    public function obterParcelaPorId(int $parcelaId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                ofp.*,
                ofi.obreiro_id,
                ofi.titulo,
                ofi.tipo_obrigacao,
                ofi.categoria_id AS obrigacao_categoria_id,
                c.codigo AS categoria_codigo,
                c.nome AS categoria_nome,
                COALESCE(o.nome_historico, o.nome) AS obreiro_nome
            FROM obrigacao_financeira_parcelas ofp
            JOIN obrigacoes_financeiras ofi ON ofi.id = ofp.obrigacao_id
            LEFT JOIN obreiros o ON o.id = ofi.obreiro_id
            LEFT JOIN categorias_financeiras c
                ON c.id = COALESCE(ofp.categoria_id, ofi.categoria_id)
            WHERE ofp.id = :id
              AND ofi.loja_id = :loja_id
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $parcelaId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $parcela = $stmt->fetch(PDO::FETCH_ASSOC);
        return $parcela ?: null;
    }

    public function listarParcelasEmAbertoObreiro(string $obreiroId): array
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $obreiroId)) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT
                ofp.id,
                ofp.competencia_label,
                ofp.competencia_mes,
                ofp.competencia_ano,
                ofp.valor_previsto,
                ofp.vencimento,
                ofp.status,
                ofp.pago_em,
                ofi.titulo,
                c.nome AS categoria_nome,
                c.codigo AS categoria_codigo
            FROM obrigacao_financeira_parcelas ofp
            JOIN obrigacoes_financeiras ofi ON ofi.id = ofp.obrigacao_id
            LEFT JOIN categorias_financeiras c ON c.id = COALESCE(ofp.categoria_id, ofi.categoria_id)
            WHERE ofi.obreiro_id = :obreiro_id
              AND ofi.loja_id = :loja_id
              AND ofp.status <> 'pago'
            ORDER BY ofp.vencimento ASC, ofp.id ASC
        ");
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($parcelas as &$parcela) {
            $parcela = $this->aplicarEstadoExibicaoParcela($parcela);
        }
        unset($parcela);
        return array_values(array_filter($parcelas, static function (array $parcela): bool {
            return empty($parcela['quitado_na_exibicao']);
        }));
    }

    private function gerarParcelas(int $obrigacaoId, array $dados): array
    {
        $recorrencia = (string) ($dados['recorrencia'] ?? 'avulsa');
        $valorBase = (float) ($dados['valor_base'] ?? 0);
        $parcelasTotal = max(1, (int) ($dados['parcelas_total'] ?? 1));
        $diaVencimento = max(1, min(31, (int) ($dados['dia_vencimento'] ?? 10)));
        $mesReferenciaFixa = (int) ($dados['mes_referencia_fixa'] ?? 0);
        $inicio = $dados['inicio_competencia'];
        $fim = $dados['fim_competencia'] ?? null;
        $categoriaId = $dados['categoria_id'] ?? null;
        $parcelas = [];

        if (!$inicio instanceof DateTimeImmutable) {
            return [];
        }

        if ($recorrencia === 'parcelado') {
            $valorParcela = round($valorBase / $parcelasTotal, 2);
            $acumulado = 0.0;
            for ($i = 1; $i <= $parcelasTotal; $i++) {
                $dataBase = $inicio->modify('+' . ($i - 1) . ' month');
                $valor = $i === $parcelasTotal ? round($valorBase - $acumulado, 2) : $valorParcela;
                $acumulado += $valor;
                $parcelas[] = $this->montarParcela($obrigacaoId, $i, $dataBase, $diaVencimento, $valor, $categoriaId);
            }
            return $parcelas;
        }

        if ($recorrencia === 'mensal') {
            $cursor = $inicio;
            $numero = 1;
            while ($cursor <= $fim) {
                $parcelas[] = $this->montarParcela($obrigacaoId, $numero++, $cursor, $diaVencimento, $valorBase, $categoriaId);
                $cursor = $cursor->modify('+1 month');
            }
            return $parcelas;
        }

        if ($recorrencia === 'anual') {
            $mesBase = $mesReferenciaFixa > 0 ? $mesReferenciaFixa : (int) $inicio->format('n');
            $anoInicial = (int) $inicio->format('Y');
            $anoFinal = (int) (($fim ?: $inicio)->format('Y'));
            $numero = 1;
            for ($ano = $anoInicial; $ano <= $anoFinal; $ano++) {
                $dataBase = new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mesBase));
                $parcelas[] = $this->montarParcela($obrigacaoId, $numero++, $dataBase, $diaVencimento, $valorBase, $categoriaId);
            }
            return $parcelas;
        }

        $parcelas[] = $this->montarParcela($obrigacaoId, 1, $inicio, $diaVencimento, $valorBase, $categoriaId);
        return $parcelas;
    }

    private function montarParcela(int $obrigacaoId, int $numero, DateTimeImmutable $dataBase, int $diaVencimento, float $valor, ?int $categoriaId): array
    {
        $ultimoDia = (int) $dataBase->format('t');
        $dia = min($diaVencimento, $ultimoDia);
        $vencimento = $dataBase->setDate(
            (int) $dataBase->format('Y'),
            (int) $dataBase->format('n'),
            $dia
        );

        return [
            'obrigacao_id' => $obrigacaoId,
            'numero_parcela' => $numero,
            'competencia_label' => $dataBase->format('m/Y'),
            'competencia_mes' => (int) $dataBase->format('n'),
            'competencia_ano' => (int) $dataBase->format('Y'),
            'vencimento' => $vencimento->format('Y-m-d'),
            'valor_previsto' => $valor,
            'categoria_id' => $categoriaId,
            'observacao' => null,
        ];
    }

    private function resumirObrigacao(array $parcelas): array
    {
        $resumo = [
            'em_aberto' => 0,
            'pagas' => 0,
            'pagas_exibicao' => 0,
            'atrasadas' => 0,
            'saldo_em_aberto' => 0.0,
            'total_previsto' => 0.0,
            'total_pago' => 0.0,
        ];

        foreach ($parcelas as $parcela) {
            $resumo['total_previsto'] += (float) ($parcela['valor_previsto'] ?? 0);
            if (!empty($parcela['quitado_na_exibicao'])) {
                $resumo['pagas']++;
                $resumo['pagas_exibicao'] += !empty($parcela['pago_presumido']) ? 1 : 0;
                $resumo['total_pago'] += (float) ($parcela['valor_previsto'] ?? 0);
                continue;
            }
            $resumo['em_aberto']++;
            $resumo['saldo_em_aberto'] += (float) ($parcela['valor_previsto'] ?? 0);
            if (!empty($parcela['em_atraso'])) {
                $resumo['atrasadas']++;
            }
        }

        return $resumo;
    }

    private function parseDate($valor): ?DateTimeImmutable
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($valor);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function resolverFimCompetencia(DateTimeImmutable $inicio, ?DateTimeImmutable $fimInformado, string $recorrencia, int $parcelasTotal): ?DateTimeImmutable
    {
        if ($fimInformado instanceof DateTimeImmutable) {
            return $fimInformado;
        }

        if ($recorrencia === 'mensal') {
            return $inicio->add(new DateInterval('P11M'));
        }

        if ($recorrencia === 'parcelado') {
            return $inicio->add(new DateInterval('P' . max(0, $parcelasTotal - 1) . 'M'));
        }

        return $inicio;
    }

    private function obterParametrosFinanceiros(): array
    {
        if ($this->parametrosFinanceiros !== null) {
            return $this->parametrosFinanceiros;
        }

        $configuracao = (new ConfiguracaoLoja())->obter();
        $this->parametrosFinanceiros = [
            'mensalidade_valor_padrao' => (float) ($configuracao['mensalidade_valor_padrao'] ?? 150),
            'mensalidade_dia_sugerido' => (int) ($configuracao['mensalidade_dia_sugerido'] ?? 10),
            'mensalidade_regra_atraso' => (string) ($configuracao['mensalidade_regra_atraso'] ?? 'primeiro_dia_util_mes_seguinte'),
            'contribuicao_biblioteca_valor_padrao' => (float) ($configuracao['contribuicao_biblioteca_valor_padrao'] ?? 40),
            'contribuicao_biblioteca_quantidade_mensal' => (int) ($configuracao['contribuicao_biblioteca_quantidade_mensal'] ?? 2),
        ];

        return $this->parametrosFinanceiros;
    }

    private function parcelaEstaEmAtraso(array $parcela): bool
    {
        if (!empty($parcela['quitado_na_exibicao']) || ($parcela['status'] ?? '') === 'pago' || empty($parcela['vencimento'])) {
            return false;
        }

        $hoje = new DateTimeImmutable('today');
        $codigo = strtoupper((string) ($parcela['categoria_codigo'] ?? ''));
        if ($codigo === 'MENSALIDADE') {
            $limite = $this->calcularLimiteMensalidade($parcela);
            return $hoje > $limite;
        }

        try {
            $vencimento = new DateTimeImmutable((string) $parcela['vencimento']);
            return $hoje > $vencimento;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function calcularLimiteMensalidade(array $parcela): DateTimeImmutable
    {
        $parametros = $this->obterParametrosFinanceiros();
        $regra = (string) ($parametros['mensalidade_regra_atraso'] ?? 'primeiro_dia_util_mes_seguinte');
        $diaSugerido = max(1, min(31, (int) ($parametros['mensalidade_dia_sugerido'] ?? 10)));
        $mes = max(1, min(12, (int) ($parcela['competencia_mes'] ?? date('n'))));
        $ano = max(2000, (int) ($parcela['competencia_ano'] ?? date('Y')));

        if ($regra === 'dia_sugerido') {
            $ultimoDia = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->format('t');
            $dia = min($diaSugerido, $ultimoDia);
            return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $ano, $mes, $dia));
        }

        $proximoMes = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->modify('first day of next month');
        return $this->primeiroDiaUtil($proximoMes);
    }

    private function primeiroDiaUtil(DateTimeImmutable $data): DateTimeImmutable
    {
        $cursor = $data;
        while (in_array((int) $cursor->format('N'), [6, 7], true)) {
            $cursor = $cursor->modify('+1 day');
        }
        return $cursor;
    }

    private function obterCategoriaIdPorCodigo(string $codigo): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM categorias_financeiras WHERE UPPER(codigo) = :codigo LIMIT 1");
        $stmt->execute(['codigo' => strtoupper($codigo)]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    private function obrigacaoJaExiste(string $obreiroId, string $titulo): bool
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $obreiroId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT 1
            FROM obrigacoes_financeiras
            WHERE obreiro_id = :obreiro_id
              AND loja_id = :loja_id
              AND titulo = :titulo
              AND status = 'ativa'
            LIMIT 1
        ");
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId(),
            'titulo' => $titulo,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function bibliotecaJaDesignada(string $obreiroId, int $mes, int $ano): bool
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $obreiroId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT 1
            FROM biblioteca_contribuintes_mensal
            WHERE obreiro_id = :obreiro_id
              AND mes_ref = :mes
              AND ano_ref = :ano
            LIMIT 1
        ");
        $stmt->execute(['obreiro_id' => $obreiroId, 'mes' => $mes, 'ano' => $ano]);
        return (bool) $stmt->fetchColumn();
    }

    private function escalaBibliotecaRenascenca(): array
    {
        return [
            1 => ['Alexandre Bock', 'André Machado'],
            2 => ['Carlos Ventura', 'Cassiano Mendes'],
            3 => ['Clóvis Bassani', 'Douglas Rodrigues'],
            4 => ['Fabiano Lamb', 'Flavio Zancanaro'],
            5 => ['Francisco Peixouto', 'Gilvan Christello'],
            6 => ['Joel Horn', 'Jucemar Teixeira'],
            7 => ['Leandro Dalpiaz', 'Luiz Lavieja'],
            8 => ['Leandro Ortiz', 'Marcelo Kordyas'],
            9 => ['Mario Picoli', 'Marlon Maciel'],
            10 => ['Munir Mashini', 'Paulo Tedesco'],
            11 => ['Rafael Aislan', 'Rafael Euzébio'],
            12 => ['Tiago Camargo', 'Tiago Irigoyen', 'Vilson Braga'],
        ];
    }

    private function normalizarNome(string $nome): string
    {
        $nome = strtr(mb_strtolower(trim($nome), 'UTF-8'), [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
        return preg_replace('/\s+/', ' ', $nome) ?: $nome;
    }

    private function obreiroEstaIsento(string $obreiroId, string $tipoObrigacao, DateTimeImmutable $competencia): bool
    {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $obreiroId)) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT 1
            FROM isencoes_financeiras
            WHERE obreiro_id = :obreiro_id
              AND tipo_obrigacao = :tipo_obrigacao
              AND ativo = TRUE
              AND inicio_competencia <= :competencia
              AND (fim_competencia IS NULL OR fim_competencia >= :competencia)
            LIMIT 1
        ");
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'tipo_obrigacao' => $tipoObrigacao,
            'competencia' => $competencia->format('Y-m-d'),
        ]);
        return (bool) $stmt->fetchColumn();
    }

    private function aplicarEstadoExibicaoParcela(array $parcela): array
    {
        $pagoPresumido = $this->parcelaContaComoPagaNaExibicao($parcela);
        $statusOriginal = (string) ($parcela['status'] ?? 'pendente');
        $quitadoNaExibicao = $statusOriginal === 'pago' || $pagoPresumido;

        $parcela['pago_presumido'] = $pagoPresumido;
        $parcela['quitado_na_exibicao'] = $quitadoNaExibicao;
        $parcela['status_exibicao'] = $quitadoNaExibicao
            ? ($pagoPresumido ? 'quitado_ajuste' : 'pago')
            : 'pendente';
        $parcela['descricao_status'] = $quitadoNaExibicao
            ? ($pagoPresumido ? 'Considerado pago no ajuste inicial de 2026' : 'Pago')
            : 'Em acompanhamento';
        $parcela['em_atraso'] = $this->parcelaEstaEmAtrasoSemAjuste($parcela);

        if ($quitadoNaExibicao) {
            $parcela['em_atraso'] = false;
        }

        return $parcela;
    }

    private function parcelaContaComoPagaNaExibicao(array $parcela): bool
    {
        if (($parcela['status'] ?? '') === 'pago') {
            return false;
        }

        $ano = (int) ($parcela['competencia_ano'] ?? 0);
        $mes = (int) ($parcela['competencia_mes'] ?? 0);

        return $ano === 2026 && in_array($mes, [1, 2, 3], true);
    }

    private function parcelaEstaEmAtrasoSemAjuste(array $parcela): bool
    {
        if (($parcela['status'] ?? '') === 'pago' || empty($parcela['vencimento'])) {
            return false;
        }

        $hoje = new DateTimeImmutable('today');
        $codigo = strtoupper((string) ($parcela['categoria_codigo'] ?? ''));
        if ($codigo === 'MENSALIDADE') {
            $limite = $this->calcularLimiteMensalidade($parcela);
            return $hoje > $limite;
        }

        try {
            $vencimento = new DateTimeImmutable((string) $parcela['vencimento']);
            return $hoje > $vencimento;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function obterPrevisoesMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT 
                COALESCE(c.tipo, 'entrada') as tipo,
                SUM(p.valor_previsto) as total
            FROM public.obrigacao_financeira_parcelas p
            JOIN public.obrigacoes_financeiras o ON p.obrigacao_id = o.id
            JOIN public.obreiros ob ON o.obreiro_id = ob.id
            LEFT JOIN public.categorias_financeiras c ON o.categoria_id = c.id
            WHERE p.competencia_mes = :mes
              AND p.competencia_ano = :ano
              AND p.status = 'pendente'
              AND o.status = 'ativa'
              AND ob.loja_id = :loja_id
            GROUP BY COALESCE(c.tipo, 'entrada')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'mes' => $mes,
            'ano' => $ano,
            'loja_id' => $this->resolveCurrentStoreId($this->db),
        ]);

        $resumo = ['entrada' => 0.0, 'saida' => 0.0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tipo = $row['tipo'] === 'saida' ? 'saida' : 'entrada';
            $resumo[$tipo] = (float) $row['total'];
        }

        return $resumo;
    }

    public function obterDetalheFinanceiroObreiro(string $obreiroId): ?array
    {
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $obreiroId)) {
            return null;
        }

        // 1. Obter dados cadastrais do Obreiro
        $stmtOb = $this->db->prepare("
            SELECT id, nome, nome_historico, grau, 
                   financeiro_joia_valor, financeiro_joia_formato, financeiro_joia_ativa, financeiro_joia_tipo,
                   financeiro_biblioteca_valor, financeiro_biblioteca_formato, financeiro_biblioteca_mes,
                   data_iniciacao, data_elevacao, data_exaltacao
            FROM public.obreiros
            WHERE id = :id AND loja_id = :loja_id
            LIMIT 1
        ");
        $stmtOb->execute([
            'id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId()
        ]);
        $obreiro = $stmtOb->fetch(PDO::FETCH_ASSOC);
        if (!$obreiro) {
            return null;
        }

        $grau = (string) ($obreiro['grau'] ?? 'Aprendiz');
        $joiaValorConfig = $obreiro['financeiro_joia_valor'] !== null ? (float) $obreiro['financeiro_joia_valor'] : null;
        $joiaFormatoConfig = (string) ($obreiro['financeiro_joia_formato'] ?? 'a_vista');
        $bibValorConfig = $obreiro['financeiro_biblioteca_valor'] !== null ? (float) $obreiro['financeiro_biblioteca_valor'] : null;
        $bibFormatoConfig = (string) ($obreiro['financeiro_biblioteca_formato'] ?? 'mensal');

        // 2. Identificar escala de Biblioteca
        $mesBiblioteca = $obreiro['financeiro_biblioteca_mes'] !== null 
            ? (int) $obreiro['financeiro_biblioteca_mes'] 
            : null;
        
        if ($mesBiblioteca === null) {
            $escala = $this->escalaBibliotecaRenascenca();
            $nomeNormalizado = $this->normalizarNome((string) ($obreiro['nome_historico'] ?: $obreiro['nome']));
            
            foreach ($escala as $mes => $nomes) {
                foreach ($nomes as $nome) {
                    if ($this->normalizarNome($nome) === $nomeNormalizado) {
                        $mesBiblioteca = $mes;
                        break 2;
                    }
                }
            }
        }

        // Determinar dados de Biblioteca
        $valorBiblioteca = $bibValorConfig ?? 44.0;
        $mesesNomes = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        
        $bibliotecaInfo = [
            'mes_programado' => $mesBiblioteca,
            'mes_nome' => $mesBiblioteca ? $mesesNomes[$mesBiblioteca] : 'Não programado',
            'valor' => $valorBiblioteca,
            'formato' => $bibFormatoConfig,
            'status' => 'pendente',
            'alerta_mensagem' => null
        ];

        if ($bibFormatoConfig === 'isento') {
            $bibliotecaInfo['status'] = 'isento';
        } else {
            // Verificar no banco se existe parcela de biblioteca paga para 2026
            $stmtBib = $this->db->prepare("
                SELECT p.status, p.valor_previsto, p.competencia_mes, p.competencia_ano
                FROM public.obrigacao_financeira_parcelas p
                JOIN public.obrigacoes_financeiras o ON p.obrigacao_id = o.id
                LEFT JOIN public.categorias_financeiras c ON o.categoria_id = c.id
                WHERE o.obreiro_id = :obreiro_id
                  AND (o.tipo_obrigacao = 'biblioteca' OR c.codigo = 'CONTRIBUICAO_BIBLIOTECA')
                  AND p.competencia_ano = 2026
                ORDER BY p.id DESC
                LIMIT 1
            ");
            $stmtBib->execute(['obreiro_id' => $obreiroId]);
            $parcelaBib = $stmtBib->fetch(PDO::FETCH_ASSOC);

            if ($parcelaBib) {
                $bibliotecaInfo['status'] = $parcelaBib['status'] === 'pago' ? 'pago' : 'pendente';
                $bibliotecaInfo['valor'] = (float) $parcelaBib['valor_previsto'];
            }

            // Alerta 30 dias antes (mês anterior)
            if ($mesBiblioteca && $bibliotecaInfo['status'] === 'pendente') {
                $hoje = new \DateTimeImmutable('today');
                $mesAtual = (int) $hoje->format('n');
                $anoAtual = (int) $hoje->format('Y');

                $anoProgramado = $anoAtual;
                if ($mesBiblioteca === 1 && $mesAtual === 12) {
                    $anoProgramado = $anoAtual + 1;
                }
                
                $dataProgramada = new \DateTimeImmutable(sprintf('%04d-%02d-01', $anoProgramado, $mesBiblioteca));
                $dataAlertaInicio = $dataProgramada->modify('-1 month'); // Mês anterior
                $mesAlerta = (int) $dataAlertaInicio->format('n');
                $anoAlerta = (int) $dataAlertaInicio->format('Y');

                if ($mesAtual === $mesAlerta && $anoAtual === $anoAlerta) {
                    $bibliotecaInfo['alerta_mensagem'] = "Sua contribuição a biblioteca está programada para " . sprintf('%02d/%04d', $mesBiblioteca, $anoProgramado) . " no valor R$ " . number_format($bibliotecaInfo['valor'], 2, ',', '.') . ".";
                }
            }
        }

        // 3. Obter dados de Joia
        // Buscar lançamentos de entrada avulsos ou vinculados a parcelas do obreiro nas categorias de jóias
        $stmtL = $this->db->prepare("
            SELECT l.id, l.valor, l.data_lancamento, l.descricao
            FROM public.lancamentos_financeiros l
            JOIN public.categorias_financeiras c ON l.categoria_id = c.id
            WHERE l.obreiro_id = :obreiro_id
              AND c.codigo IN ('JOIAS', 'INICIACAO', 'ELEVACAO', 'EXALTACAO')
              AND l.tipo = 'entrada'
            ORDER BY l.data_lancamento ASC, l.id ASC
        ");
        $stmtL->execute(['obreiro_id' => $obreiroId]);
        $lancamentosJoia = $stmtL->fetchAll(PDO::FETCH_ASSOC);

        $totalPagoJoia = 0.0;
        $logsPagamentos = [];
        foreach ($lancamentosJoia as $l) {
            $val = (float) $l['valor'];
            $totalPagoJoia += $val;
            $logsPagamentos[] = [
                'parcela' => count($logsPagamentos) + 1,
                'valor' => $val,
                'pago_em' => $l['data_lancamento'],
                'descricao' => $l['descricao'] ?: 'Abate de Joia'
            ];
        }

        // Determinar a data individual cadastrada no registro do obreiro
        $joiaTipo = $obreiro['financeiro_joia_tipo'] !== null ? $obreiro['financeiro_joia_tipo'] : null;
        
        $dataCerimoniaIndividual = null;
        if ($joiaTipo === 'elevacao') {
            $dataCerimoniaIndividual = !empty($obreiro['data_elevacao']) ? $obreiro['data_elevacao'] : null;
        } elseif ($joiaTipo === 'exaltacao') {
            $dataCerimoniaIndividual = !empty($obreiro['data_exaltacao']) ? $obreiro['data_exaltacao'] : null;
        } elseif ($joiaTipo === 'iniciacao') {
            $dataCerimoniaIndividual = !empty($obreiro['data_iniciacao']) ? $obreiro['data_iniciacao'] : null;
        }

        // Buscar se já existem parcelas de joia no banco
        $stmtJoias = $this->db->prepare("
            SELECT p.id, p.numero_parcela, p.vencimento, p.valor_previsto, p.status, p.pago_em, 
                   o.titulo, o.tipo_obrigacao, c.codigo as categoria_codigo
            FROM public.obrigacao_financeira_parcelas p
            JOIN public.obrigacoes_financeiras o ON p.obrigacao_id = o.id
            LEFT JOIN public.categorias_financeiras c ON o.categoria_id = c.id
            WHERE o.obreiro_id = :obreiro_id
              AND (o.tipo_obrigacao = 'joia' OR c.codigo IN ('JOIAS', 'INICIACAO', 'ELEVACAO', 'EXALTACAO'))
            ORDER BY p.numero_parcela ASC, p.vencimento ASC
        ");
        $stmtJoias->execute(['obreiro_id' => $obreiroId]);
        $parcelasJoia = $stmtJoias->fetchAll(PDO::FETCH_ASSOC);

        // A joia só é considerada ativa se o tesoureiro ativou explicitamente ou se existem parcelas no banco
        $joiaAtiva = ($parcelasJoia !== []) || (
            (bool)($obreiro['financeiro_joia_ativa'] ?? false) && 
            !empty($joiaTipo) && 
            $joiaTipo !== 'nenhuma'
        );

        $joiaInfo = null;
        if ($joiaAtiva) {
            $dataCerimonia = $dataCerimoniaIndividual;

            // Se a data individual estiver vazia, consultar de forma inteligente a agenda de sessões (sessoes)
            if (!$dataCerimonia && !empty($joiaTipo)) {
                $tipoSessao = null;
                if ($joiaTipo === 'elevacao') {
                    $tipoSessao = 'magna_elevacao';
                } elseif ($joiaTipo === 'exaltacao') {
                    $tipoSessao = 'magna_exaltacao';
                } elseif ($joiaTipo === 'iniciacao') {
                    $tipoSessao = 'magna_iniciacao';
                }

                if ($tipoSessao !== null) {
                    $stmtSes = $this->db->prepare("
                        SELECT data_hora_inicio
                        FROM public.sessoes
                        WHERE tipo = :tipo
                          AND loja_id = :loja_id
                          AND data_hora_inicio >= CURRENT_TIMESTAMP
                        ORDER BY data_hora_inicio ASC
                        LIMIT 1
                    ");
                    $stmtSes->execute([
                        'tipo' => $tipoSessao,
                        'loja_id' => $this->obterLojaAtualId()
                    ]);
                    $sessaoAgendada = $stmtSes->fetch(PDO::FETCH_ASSOC);
                    if ($sessaoAgendada) {
                        $dataCerimonia = date('Y-m-d', strtotime($sessaoAgendada['data_hora_inicio']));
                    }
                }
            }

            if ($parcelasJoia !== []) {
                $totalJoia = 0.0;
                $totalParcelas = count($parcelasJoia);
                $pagas = 0;
                $vencimentoLimite = null;
                $tituloJoia = $parcelasJoia[0]['titulo'] ?: 'Joia Maçônica';

                foreach ($parcelasJoia as $p) {
                    $totalJoia += (float) $p['valor_previsto'];
                    if ($p['status'] === 'pago') {
                        $pagas++;
                    } else {
                        if ($vencimentoLimite === null || $p['vencimento'] > $vencimentoLimite) {
                            $vencimentoLimite = $p['vencimento'];
                        }
                    }
                }

                $saldoDevedor = max(0.0, $totalJoia - $totalPagoJoia);
                if ($dataCerimonia) {
                    $vencimentoLimite = $dataCerimonia;
                }

                $joiaInfo = [
                    'possui_registro' => true,
                    'titulo' => $tituloJoia,
                    'valor_total' => $totalJoia,
                    'saldo_devedor' => $saldoDevedor,
                    'total_parcelas' => $totalParcelas,
                    'parcelas_pagas' => $pagas,
                    'formato' => $totalParcelas > 1 ? 'parcelado' : 'a_vista',
                    'prazo_limite' => $vencimentoLimite ?: 'Quitada',
                    'logs' => $logsPagamentos,
                    'status' => $saldoDevedor > 0.01 ? 'em_aberto' : 'pago'
                ];
            } elseif (!empty($joiaTipo) && $joiaTipo !== 'nenhuma') {
                // Estimar joia esperada baseado no tipo ou grau
                $joiaEsperada = 'Iniciação';
                if ($joiaTipo === 'elevacao') {
                    $joiaEsperada = 'Elevação';
                } elseif ($joiaTipo === 'exaltacao') {
                    $joiaEsperada = 'Exaltação';
                }

                $valorEstimado = $joiaValorConfig !== null ? $joiaValorConfig : 1502.0; // Salário mínimo 2026
                $saldoEstimado = $joiaFormatoConfig === 'isento' ? 0.0 : max(0.0, $valorEstimado - $totalPagoJoia);

                $joiaInfo = [
                    'possui_registro' => false,
                    'titulo' => 'Joia de ' . $joiaEsperada . ' (Esperada)',
                    'valor_total' => $valorEstimado,
                    'saldo_devedor' => $saldoEstimado,
                    'total_parcelas' => $joiaFormatoConfig === 'parcelado' ? 10 : ($joiaFormatoConfig === 'isento' ? 0 : 1),
                    'parcelas_pagas' => count($logsPagamentos),
                    'formato' => $joiaFormatoConfig,
                    'prazo_limite' => $dataCerimonia ?: 'Antes da cerimônia',
                    'logs' => $logsPagamentos,
                    'status' => $joiaFormatoConfig === 'isento' ? 'isento' : ($saldoEstimado > 0.01 ? 'pendente' : 'pago')
                ];
            }
        }

        return [
            'obreiro' => [
                'id' => $obreiro['id'],
                'nome' => $obreiro['nome'],
                'nome_historico' => $obreiro['nome_historico'],
                'grau' => $grau
            ],
            'mensalidade' => [
                'valor_regular' => 150.00,
                'dia_vencimento' => 10
            ],
            'biblioteca' => $bibliotecaInfo,
            'joia' => $joiaInfo
        ];
    }
}


