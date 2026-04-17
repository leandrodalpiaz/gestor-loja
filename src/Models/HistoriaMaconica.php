<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use App\Services\HistoricoEventos;
use PDO;

class HistoriaMaconica
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
        $this->migrarLegado();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS historias_maconicas (
                id SERIAL PRIMARY KEY,
                loja_id INTEGER NULL,
                titulo VARCHAR(255) NOT NULL,
                dia SMALLINT NOT NULL,
                mes SMALLINT NOT NULL,
                ano_ref INT NULL,
                texto TEXT NOT NULL,
                fonte VARCHAR(255) NULL,
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_historias_maconicas_unico
            ON historias_maconicas (loja_id, mes, dia, titulo)
        ");
    }

    private function migrarLegado(): void
    {
        $lojaId = $this->obterLojaAtualId();
        $fixos = HistoricoEventos::getFixos();
        foreach ($fixos as $diaMes => $evento) {
            [$mes, $dia] = array_map('intval', explode('-', $diaMes));
            $titulo = trim((string) ($evento['titulo'] ?? 'Evento historico'));
            $texto = trim((string) ($evento['texto'] ?? ''));
            $anoRef = isset($evento['ano_ref']) && $evento['ano_ref'] !== null ? (int) $evento['ano_ref'] : null;
            $fonte = $this->extrairFonte($texto);
            $textoLimpo = $this->removerFonteDoTexto($texto);

            if ($titulo === '' || $textoLimpo === '') {
                continue;
            }

            $stmt = $this->db->prepare("
                INSERT INTO historias_maconicas (loja_id, titulo, dia, mes, ano_ref, texto, fonte, ativo, created_by, updated_at)
                VALUES (:loja_id, :titulo, :dia, :mes, :ano_ref, :texto, :fonte, true, null, CURRENT_TIMESTAMP)
                ON CONFLICT (loja_id, mes, dia, titulo) DO UPDATE SET
                    ano_ref = EXCLUDED.ano_ref,
                    texto = EXCLUDED.texto,
                    fonte = EXCLUDED.fonte,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                'loja_id' => $lojaId,
                'titulo' => $titulo,
                'dia' => $dia,
                'mes' => $mes,
                'ano_ref' => $anoRef,
                'texto' => $textoLimpo,
                'fonte' => $fonte,
            ]);
        }

        $registros = $this->db->query("
            SELECT *
            FROM efemerides_registros
            WHERE ativo = true
            ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($registros as $registro) {
            $tipo = $this->normalizarTexto((string) ($registro['tipo'] ?? ''));
            if ($tipo !== 'historia') {
                continue;
            }

            $dataEvento = trim((string) ($registro['data_evento'] ?? ''));
            $data = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento);
            if ($data === false) {
                continue;
            }

            $titulo = trim((string) ($registro['nome'] ?? 'Evento historico'));
            $texto = trim((string) ($registro['mensagem_custom'] ?? ''));
            if ($titulo === '') {
                $titulo = 'Evento historico';
            }
            if ($texto === '') {
                $texto = $titulo;
            }

            $stmt = $this->db->prepare("
                INSERT INTO historias_maconicas (loja_id, titulo, dia, mes, ano_ref, texto, fonte, ativo, created_by, updated_at)
                VALUES (:loja_id, :titulo, :dia, :mes, :ano_ref, :texto, :fonte, :ativo, :created_by, CURRENT_TIMESTAMP)
                ON CONFLICT (loja_id, mes, dia, titulo) DO UPDATE SET
                    ano_ref = EXCLUDED.ano_ref,
                    texto = EXCLUDED.texto,
                    fonte = EXCLUDED.fonte,
                    ativo = EXCLUDED.ativo,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                'loja_id' => $lojaId,
                'titulo' => $titulo,
                'dia' => (int) $data->format('d'),
                'mes' => (int) $data->format('m'),
                'ano_ref' => (int) $data->format('Y'),
                'texto' => $texto,
                'fonte' => trim((string) ($registro['local'] ?? '')) ?: null,
                'ativo' => !empty($registro['ativo']),
                'created_by' => !empty($registro['created_by']) ? (int) $registro['created_by'] : null,
            ]);
        }

        $this->db->exec("
            DELETE FROM efemerides_registros
            WHERE " . $this->sqlNormalizarTipo('tipo') . " = 'historia'
        ");
    }

    public function listar(array $filtros = [], int $limit = 300): array
    {
        $limit = max(1, min($limit, 500));
        $where = ["loja_id = :loja_id"];
        $params = ['loja_id' => $this->obterLojaAtualId()];

        $termo = trim((string) ($filtros['termo'] ?? ''));
        if ($termo !== '') {
            $where[] = "(titulo ILIKE :termo OR texto ILIKE :termo OR COALESCE(fonte, '') ILIKE :termo)";
            $params['termo'] = '%' . $termo . '%';
        }

        $ativo = strtolower(trim((string) ($filtros['ativo'] ?? '1')));
        if ($ativo === '1' || $ativo === 'true' || $ativo === 'ativos') {
            $where[] = "ativo = true";
        } elseif ($ativo === '0' || $ativo === 'false' || $ativo === 'inativos') {
            $where[] = "ativo = false";
        }

        $dataIni = trim((string) ($filtros['data_ini'] ?? ''));
        if ($dataIni !== '') {
            $where[] = "MAKE_DATE(COALESCE(ano_ref, 2000), mes, dia) >= :data_ini";
            $params['data_ini'] = $dataIni;
        }

        $dataFim = trim((string) ($filtros['data_fim'] ?? ''));
        if ($dataFim !== '') {
            $where[] = "MAKE_DATE(COALESCE(ano_ref, 2000), mes, dia) <= :data_fim";
            $params['data_fim'] = $dataFim;
        }

        $sql = "SELECT *, MAKE_DATE(COALESCE(ano_ref, 2000), mes, dia) AS data_evento
                FROM historias_maconicas
                WHERE " . implode(' AND ', $where) . "
                ORDER BY mes ASC, dia ASC, titulo ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorDiaMes(int $dia, int $mes, bool $somenteAtivos = true): array
    {
        $sql = "SELECT * FROM historias_maconicas WHERE loja_id = :loja_id AND dia = :dia AND mes = :mes";
        if ($somenteAtivos) {
            $sql .= " AND ativo = true";
        }
        $sql .= " ORDER BY titulo ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'dia' => $dia,
            'mes' => $mes,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data, ?int $createdBy): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO historias_maconicas (loja_id, titulo, dia, mes, ano_ref, texto, fonte, ativo, created_by)
            VALUES (:loja_id, :titulo, :dia, :mes, :ano_ref, :texto, :fonte, :ativo, :created_by)
        ");

        return $stmt->execute($this->normalizarPayload($data, $createdBy));
    }

    public function atualizar(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE historias_maconicas
            SET titulo = :titulo,
                dia = :dia,
                mes = :mes,
                ano_ref = :ano_ref,
                texto = :texto,
                fonte = :fonte,
                ativo = :ativo,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND loja_id = :loja_id
        ");

        $payload = $this->normalizarPayload($data, null);
        $payload['id'] = $id;
        return $stmt->execute($payload);
    }

    public function toggleAtivo(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE historias_maconicas
            SET ativo = NOT ativo,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND loja_id = :loja_id
        ");
        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM historias_maconicas WHERE id = :id AND loja_id = :loja_id");
        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    private function normalizarPayload(array $data, ?int $createdBy): array
    {
        $dia = max(1, min(31, (int) ($data['dia'] ?? 0)));
        $mes = max(1, min(12, (int) ($data['mes'] ?? 0)));
        $anoRef = trim((string) ($data['ano_ref'] ?? ''));

        return [
            'loja_id' => $this->obterLojaAtualId(),
            'titulo' => trim((string) ($data['titulo'] ?? '')),
            'dia' => $dia,
            'mes' => $mes,
            'ano_ref' => $anoRef !== '' ? (int) $anoRef : null,
            'texto' => trim((string) ($data['texto'] ?? '')),
            'fonte' => trim((string) ($data['fonte'] ?? '')) ?: null,
            'ativo' => array_key_exists('ativo', $data) ? (filter_var($data['ativo'], FILTER_VALIDATE_BOOL) ? 'true' : 'false') : 'true',
            'created_by' => $createdBy,
        ];
    }

    private function extrairFonte(string $texto): ?string
    {
        if (preg_match('/<i>\s*Fonte:\s*(.*?)\s*<\/i>/iu', $texto, $matches)) {
            return trim((string) ($matches[1] ?? '')) ?: null;
        }

        return null;
    }

    private function removerFonteDoTexto(string $texto): string
    {
        return trim((string) preg_replace('/\s*<i>\s*Fonte:\s*.*?<\/i>\s*$/iu', '', $texto));
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = trim($texto);
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtolower($texto);
        return preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';
    }

    private function sqlNormalizarTipo(string $coluna): string
    {
        return "REGEXP_REPLACE(LOWER(TRANSLATE(COALESCE({$coluna}, ''), 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCc', 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCc')), '[^a-z0-9]+', '', 'g')";
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
