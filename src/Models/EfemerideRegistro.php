<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class EfemerideRegistro
{
    use ResolvesStoreTenant;

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
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS efemerides_registros (
                id SERIAL PRIMARY KEY,
                loja_id INTEGER NULL,
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
        ");
    }

    public function getRegistrosDoDia(): array
    {
        $timezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
        try {
            $hoje = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            $hoje = new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM efemerides_registros
            WHERE loja_id = :loja_id
              AND ativo = true
              AND TO_CHAR(data_evento, 'DD/MM') = :dia_mes
            ORDER BY tipo, nome
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'dia_mes' => $hoje->format('d/m'),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRegistrosPorDiaMes(string $diaMes): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM efemerides_registros
            WHERE loja_id = :loja_id
              AND ativo = true
              AND TO_CHAR(data_evento, 'DD/MM') = :dia_mes
            ORDER BY tipo, nome
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'dia_mes' => $diaMes,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentes(int $limit = 80): array
    {
        $limit = max(1, min($limit, 300));
        $stmt = $this->db->prepare("
            SELECT *
            FROM efemerides_registros
            WHERE loja_id = :loja_id
            ORDER BY EXTRACT(MONTH FROM data_evento) ASC, EXTRACT(DAY FROM data_evento) ASC, nome ASC, id ASC
            LIMIT :limit
        ");
        $stmt->bindValue('loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarComFiltros(array $filtros, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));
        $where = ['loja_id = :loja_id'];
        $params = ['loja_id' => $this->obterLojaAtualId()];

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

        $sql = "SELECT * FROM efemerides_registros WHERE " . implode(' AND ', $where) . "
                ORDER BY EXTRACT(MONTH FROM data_evento) ASC, EXTRACT(DAY FROM data_evento) ASC, nome ASC, id ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM efemerides_registros
            WHERE id = :id AND loja_id = :loja_id
        ");
        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function create(array $data, ?int $createdBy): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO efemerides_registros (
                loja_id,
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
                :loja_id,
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
        ");

        $vinculo = trim((string) ($data['vinculo'] ?? ''));
        $codVinculo = $this->resolverCodVinculo($vinculo, $data['cod_vinculo'] ?? null);

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
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
        $sql = "SELECT * FROM efemerides_registros WHERE id = :id AND loja_id = :loja_id";
        if (!$includeInactive) {
            $sql .= " AND ativo = true";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function atualizar(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
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
              AND loja_id = :loja_id
        ");

        $vinculo = trim((string) ($data['vinculo'] ?? ''));
        $codVinculo = $this->resolverCodVinculo($vinculo, $data['cod_vinculo'] ?? null);

        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
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
        $stmt = $this->db->prepare("
            UPDATE efemerides_registros
            SET ativo = false,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND loja_id = :loja_id
        ");
        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function listarPorTipo(string $tipo): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nome, tipo, data_evento, ativo
            FROM efemerides_registros
            WHERE loja_id = :loja_id
              AND tipo = :tipo
            ORDER BY EXTRACT(MONTH FROM data_evento) ASC, EXTRACT(DAY FROM data_evento) ASC, nome ASC, id ASC
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'tipo' => $tipo,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorData(string $data): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM efemerides_registros
            WHERE loja_id = :loja_id
              AND TO_CHAR(data_evento, 'MM-DD') = :data
            ORDER BY id DESC
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'data' => $data,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVinculosPadrao(): array
    {
        return self::VINCULOS_PADRAO;
    }

    private function resolverCodVinculo(string $vinculo, mixed $codVinculoInformado): ?int
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
        return preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
