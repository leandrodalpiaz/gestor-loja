<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class EfemerideRegistro
{
    private PDO $db;
    private const VINCULOS_PADRAO = [
        ['codigo' => 4, 'nome' => 'filho'],
        ['codigo' => 3, 'nome' => 'filha'],
        ['codigo' => 2, 'nome' => 'esposa'],
        ['codigo' => 6, 'nome' => 'enteado'],
        ['codigo' => 5, 'nome' => 'enteada'],
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS efemerides_registros (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                tipo VARCHAR(100) NOT NULL,
                data_evento DATE NOT NULL,
                cod_vinculo INT NULL,
                vinculo VARCHAR(255) NULL,
                parentesco VARCHAR(255) NULL,
                local VARCHAR(255) NULL,
                mensagem_custom TEXT NULL,
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->exec($sql);
    }

    public function getRegistrosDoDia(): array
    {
        $timezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
        try {
            $hoje = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            $hoje = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        }

        $diaMes = $hoje->format('d/m');

        $sql = "
            SELECT *
            FROM efemerides_registros
            WHERE ativo = true
              AND TO_CHAR(data_evento, 'DD/MM') = :dia_mes
            ORDER BY tipo, nome
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dia_mes' => $diaMes]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca registros ativos para um dia/mÃªs especÃ­fico (formato 'd/m').
     * @param string $diaMes Ex: '25/03'
     * @return array
     */
    public function getRegistrosPorDiaMes(string $diaMes): array
    {
        $sql = "SELECT * FROM efemerides_registros WHERE ativo = true AND TO_CHAR(data_evento, 'DD/MM') = :dia_mes ORDER BY tipo, nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dia_mes' => $diaMes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentes(int $limit = 80): array
    {
        $limit = max(1, min($limit, 300));
        $sql = "
            SELECT *
            FROM efemerides_registros
            ORDER BY data_evento DESC, id DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca registros com filtros operacionais para manutencao.
     *
     * Filtros aceitos:
     * - termo: procura em nome, parentesco, vinculo e local
     * - irmao_ref: procura registros relacionados ao irmao selecionado (nome ou parentesco)
     * - tipo: filtro por tipo de evento
     * - ativo: "1" (ativos), "0" (inativos), "all" (todos)
     * - data_ini / data_fim: intervalo em Y-m-d
     */
    public function buscarComFiltros(array $filtros, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));

        $where = [];
        $params = [];

        $termo = trim((string) ($filtros['termo'] ?? ''));
        if ($termo !== '') {
            $where[] = "(nome ILIKE :termo OR COALESCE(parentesco, '') ILIKE :termo OR COALESCE(vinculo, '') ILIKE :termo OR COALESCE(local, '') ILIKE :termo)";
            $params['termo'] = '%' . $termo . '%';
        }

        $irmaoRef = trim((string) ($filtros['irmao_ref'] ?? ''));
        if ($irmaoRef !== '') {
            $where[] = "(nome ILIKE :irmao_ref OR COALESCE(parentesco, '') ILIKE :irmao_ref)";
            $params['irmao_ref'] = '%' . $irmaoRef . '%';
        }

        $tipo = trim((string) ($filtros['tipo'] ?? ''));
        if ($tipo !== '') {
            $where[] = "tipo = :tipo";
            $params['tipo'] = $tipo;
        }

        $vinculo = trim((string) ($filtros['vinculo'] ?? ''));
        if ($vinculo !== '') {
            $where[] = "COALESCE(vinculo, '') ILIKE :vinculo";
            $params['vinculo'] = $vinculo;
        }

        $ativo = strtolower(trim((string) ($filtros['ativo'] ?? '1')));
        if ($ativo === '1' || $ativo === 'true' || $ativo === 'ativos') {
            $where[] = "ativo = true";
        } elseif ($ativo === '0' || $ativo === 'false' || $ativo === 'inativos') {
            $where[] = "ativo = false";
        }

        $dataIni = trim((string) ($filtros['data_ini'] ?? ''));
        if ($dataIni !== '') {
            $where[] = "data_evento >= :data_ini";
            $params['data_ini'] = $dataIni;
        }

        $dataFim = trim((string) ($filtros['data_fim'] ?? ''));
        if ($dataFim !== '') {
            $where[] = "data_evento <= :data_fim";
            $params['data_fim'] = $dataFim;
        }

        $sql = "SELECT * FROM efemerides_registros";
        if ($where !== []) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY data_evento DESC, id DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data, ?int $createdBy): bool
    {
        $sql = "
            INSERT INTO efemerides_registros (
                nome,
                tipo,
                data_evento,
                cod_vinculo,
                vinculo,
                parentesco,
                local,
                mensagem_custom,
                ativo,
                created_by
            ) VALUES (
                :nome,
                :tipo,
                :data_evento,
                :cod_vinculo,
                :vinculo,
                :parentesco,
                :local,
                :mensagem_custom,
                true,
                :created_by
            )
        ";

        $stmt = $this->db->prepare($sql);

        $vinculo = trim((string) ($data['vinculo'] ?? ''));
        $codVinculo = $this->resolverCodVinculo($vinculo, $data['cod_vinculo'] ?? null);

        return $stmt->execute([
            'nome' => trim((string) ($data['nome'] ?? '')),
            'tipo' => trim((string) ($data['tipo'] ?? '')),
            'data_evento' => $data['data_evento'] ?? null,
            'cod_vinculo' => $codVinculo,
            'vinculo' => $vinculo !== '' ? $vinculo : null,
            'parentesco' => trim((string) ($data['parentesco'] ?? '')) ?: null,
            'local' => trim((string) ($data['local'] ?? '')) ?: null,
            'mensagem_custom' => trim((string) ($data['mensagem_custom'] ?? '')) ?: null,
            'created_by' => $createdBy,
        ]);
    }

    public function findById(int $id, bool $includeInactive = true): ?array
    {
        $sql = "SELECT * FROM efemerides_registros WHERE id = :id";
        if (!$includeInactive) {
            $sql .= " AND ativo = true";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function atualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE efemerides_registros
            SET nome = :nome,
                tipo = :tipo,
                data_evento = :data_evento,
                cod_vinculo = :cod_vinculo,
                vinculo = :vinculo,
                parentesco = :parentesco,
                local = :local,
                mensagem_custom = :mensagem_custom,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $vinculo = trim((string) ($data['vinculo'] ?? ''));
        $codVinculo = $this->resolverCodVinculo($vinculo, $data['cod_vinculo'] ?? null);

        return $stmt->execute([
            'id' => $id,
            'nome' => trim((string) ($data['nome'] ?? '')),
            'tipo' => trim((string) ($data['tipo'] ?? '')),
            'data_evento' => $data['data_evento'] ?? null,
            'cod_vinculo' => $codVinculo,
            'vinculo' => $vinculo !== '' ? $vinculo : null,
            'parentesco' => trim((string) ($data['parentesco'] ?? '')) ?: null,
            'local' => trim((string) ($data['local'] ?? '')) ?: null,
            'mensagem_custom' => trim((string) ($data['mensagem_custom'] ?? '')) ?: null,
        ]);
    }

    public function desativar(int $id): bool
    {
        $sql = "
            UPDATE efemerides_registros
            SET ativo = false,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function listarPorTipo(string $tipo): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nome, tipo, data_evento, ativo
            FROM efemerides_registros
            WHERE tipo = :tipo
            ORDER BY data_evento DESC, id DESC
        ");
        $stmt->execute(['tipo' => $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorData(string $data): array
    {
        $sql = "SELECT * FROM efemerides_registros WHERE TO_CHAR(data_evento, 'MM-DD') = :data ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['data' => $data]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVinculosPadrao(): array
    {
        return self::VINCULOS_PADRAO;
    }

    private function resolverCodVinculo(string $vinculo, $codVinculoInformado): ?int
    {
        if ($codVinculoInformado !== null && $codVinculoInformado !== '') {
            return (int) $codVinculoInformado;
        }

        $normalizado = $this->normalizarTexto($vinculo);
        if ($normalizado === '') {
            return null;
        }

        if ($normalizado === 'honrario') {
            $normalizado = 'honorario';
        }
        if ($normalizado === 'enteeado') {
            $normalizado = 'enteado';
        }

        foreach (self::VINCULOS_PADRAO as $item) {
            $nomeItem = $this->normalizarTexto((string) ($item['nome'] ?? ''));
            if ($nomeItem === $normalizado) {
                return (int) $item['codigo'];
            }
        }

        return null;
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';

        return $texto;
    }

}

