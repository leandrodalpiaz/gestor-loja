<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class Cargo
{
    use ResolvesStoreTenant;

    private const ROTULOS_OFICIAIS = [
        'ADMINISTRADOR' => 'Administrador',
        'ARQUITETO' => 'Arquiteto',
        'BIBLIOTECARIO' => 'Bibliotecário',
        'CHANCELER' => 'Chanceler',
        'COBRIDOR' => 'Cobridor',
        'GUARDA_DA_LEI' => 'Guarda da Lei',
        'GUARDA_DO_TEMPLO' => 'Guarda do Templo',
        'HOSPITALEIRO' => 'Hospitaleiro',
        'MESTRE_BANQUETES' => 'Mestre de Banquetes',
        'MESTRE_DE_CERIMONIAS' => 'Mestre de Cerimônias',
        'MESTRE_DE_HARMONIA' => 'Mestre de Harmonia',
        'ORADOR' => 'Orador',
        'PORTA_ESPADA' => 'Porta-Espada',
        'PORTA_ESTANDARTE' => 'Porta-Estandarte',
        'PRIMEIRO_DIACONO' => '1º Diácono',
        'PRIMEIRO_EXPERTO' => '1º Experto',
        'PRIMEIRO_VIGILANTE' => '1º Vigilante',
        'SECRETARIO' => 'Secretário',
        'SEGUNDO_DIACONO' => '2º Diácono',
        'SEGUNDO_EXPERTO' => '2º Experto',
        'SEGUNDO_VIGILANTE' => '2º Vigilante',
        'TESOUREIRO' => 'Tesoureiro',
        'VENERAVEL' => 'Venerável Mestre',
    ];

    private PDO $db;
    private AuditoriaAdministrativa $auditoria;
    private ?bool $suportaEscopoLoja = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->auditoria = new AuditoriaAdministrativa();
        $this->garantirEscopoLoja();
    }

    public function getCodigosAtivosDoObreiro(string $obreiroId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.codigo
             FROM atribuicoes_cargo ac
             JOIN cargos c
               ON c.id = ac.cargo_id
              AND c.loja_id = :loja_id
             LEFT JOIN gestoes g
               ON g.id = ac.gestao_id
              AND g.loja_id = :loja_id
             WHERE ac.obreiro_id = :obreiro_id
               AND ac.loja_id = :loja_id
               AND ac.fim_em IS NULL
               AND (g.id IS NULL OR g.status = 'aberta')
               AND c.ativo = TRUE
             ORDER BY c.nome_exibicao ASC"
        );
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return array_values(array_unique(array_filter(array_map(
            static fn ($row) => strtoupper((string) ($row['codigo'] ?? '')),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        ))));
    }

    public function obreiroTemCargo(string $obreiroId, string $cargoCodigo): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM atribuicoes_cargo ac
             JOIN cargos c
               ON c.id = ac.cargo_id
              AND c.loja_id = :loja_id
             LEFT JOIN gestoes g
               ON g.id = ac.gestao_id
              AND g.loja_id = :loja_id
             WHERE ac.obreiro_id = :obreiro_id
               AND ac.loja_id = :loja_id
               AND ac.fim_em IS NULL
               AND c.ativo = TRUE
               AND c.codigo = :cargo_codigo
               AND (g.id IS NULL OR g.status = 'aberta')
             LIMIT 1"
        );
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'cargo_codigo' => strtoupper(trim($cargoCodigo)),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function listarResumoCargos(?int $gestaoId = null): array
    {
        $sql = "SELECT
                c.codigo,
                c.nome_exibicao,
                c.ativo,
                a.obreiro_id AS titular_id,
                COALESCE(o.nome_historico, o.nome) AS titular_nome,
                o.cim AS titular_cim,
                g.id AS gestao_id,
                g.titulo AS gestao_titulo,
                g.inicio_em AS gestao_inicio_em,
                g.encerrada_em AS gestao_encerrada_em,
                a.inicio_em,
                a.observacao
             FROM cargos c
             LEFT JOIN atribuicoes_cargo a
               ON a.cargo_id = c.id
              AND a.loja_id = :loja_id
              AND a.fim_em IS NULL
             LEFT JOIN gestoes g
               ON g.id = a.gestao_id
              AND g.loja_id = :loja_id
             LEFT JOIN obreiros o
               ON o.id = a.obreiro_id
             WHERE c.ativo = TRUE
               AND c.loja_id = :loja_id";

        if ($gestaoId !== null) {
            $sql .= " AND (g.id = :gestao_id OR (g.id IS NULL AND :gestao_id = 0))";
        } else {
            $sql .= " AND (g.status = 'aberta' OR g.id IS NULL)";
        }

        $sql .= " ORDER BY c.nome_exibicao ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        if ($gestaoId !== null) {
            $stmt->bindValue(':gestao_id', $gestaoId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarHistorico(int $limite = 50, ?string $cargoCodigo = null, ?int $gestaoId = null): array
    {
        $sql = "SELECT
                    c.codigo,
                    c.nome_exibicao,
                    g.titulo AS gestao_titulo,
                    ac.obreiro_id,
                    COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                    o.cim,
                    ac.inicio_em,
                    ac.fim_em,
                    ac.observacao
                FROM atribuicoes_cargo ac
                JOIN cargos c
                  ON c.id = ac.cargo_id
                 AND c.loja_id = :loja_id
                LEFT JOIN gestoes g
                  ON g.id = ac.gestao_id
                 AND g.loja_id = :loja_id
                JOIN obreiros o ON o.id = ac.obreiro_id";

        $params = ['loja_id' => $this->obterLojaAtualId()];
        $where = ['ac.loja_id = :loja_id'];
        if ($cargoCodigo !== null && trim($cargoCodigo) !== '') {
            $where[] = "c.codigo = :cargo_codigo";
            $params['cargo_codigo'] = strtoupper(trim($cargoCodigo));
        }
        if ($gestaoId !== null) {
            $where[] = "g.id = :gestao_id";
            $params['gestao_id'] = $gestaoId;
        }
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY ac.inicio_em DESC LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limite', max(1, $limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atribuirPorCodigo(string $cargoCodigo, string $obreiroId, ?string $observacao = null, ?int $gestaoId = null, ?string $inicioEm = null): void
    {
        $stmt = $this->db->prepare(
            "SELECT atribuir_cargo(:loja_id, :cargo_codigo, :obreiro_id, :gestao_id, :inicio_em, :observacao)"
        );
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'cargo_codigo' => strtoupper(trim($cargoCodigo)),
            'obreiro_id' => $obreiroId,
            'gestao_id' => $gestaoId,
            'inicio_em' => $inicioEm !== null && trim($inicioEm) !== '' ? $inicioEm : date('c'),
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
        ]);

        $this->auditoria->registrar(
            'admin',
            'cargo',
            strtoupper(trim($cargoCodigo)),
            'atribuicao',
            'Titularidade de cargo atualizada',
            [
                'cargo_codigo' => strtoupper(trim($cargoCodigo)),
                'obreiro_id' => $obreiroId,
                'gestao_id' => $gestaoId,
                'inicio_em' => $inicioEm !== null && trim($inicioEm) !== '' ? $inicioEm : date('c'),
                'observacao' => $observacao,
                'loja_id' => $this->obterLojaAtualId(),
            ],
            isset($_SESSION['usuario_id']) ? (string) $_SESSION['usuario_id'] : null
        );
    }

    public static function rotuloOficial(string $cargoCodigo, ?string $fallback = null): string
    {
        $codigo = strtoupper(trim($cargoCodigo));
        if (isset(self::ROTULOS_OFICIAIS[$codigo])) {
            return self::ROTULOS_OFICIAIS[$codigo];
        }

        $fallback = trim((string) $fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        return $codigo;
    }

    public static function codigoParaSlug(string $cargoCodigo): ?string
    {
        return match (strtoupper(trim($cargoCodigo))) {
            'ADMINISTRADOR' => 'admin',
            'CHANCELER' => 'chanceler',
            'HOSPITALEIRO', 'MESTRE_HOSPITALEIRO' => 'hospitaleiro',
            'PRIMEIRO_VIGILANTE' => 'primeiro_vigilante',
            'SEGUNDO_VIGILANTE' => 'segundo_vigilante',
            'SECRETARIO' => 'secretario',
            'TESOUREIRO' => 'tesoureiro',
            'BIBLIOTECARIO' => 'bibliotecario',
            'VENERAVEL' => 'veneravel',
            'MESTRE_BANQUETES' => 'mestre_banquetes',
            'ORADOR' => 'orador',
            default => null,
        };
    }

    public static function slugsFromCodigos(array $codigos): array
    {
        $slugs = [];
        foreach ($codigos as $codigo) {
            $slug = self::codigoParaSlug((string) $codigo);
            if ($slug !== null) {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    public static function resolverCargoPrincipal(array $slugs, ?string $fallback = null): string
    {
        $prioridade = ['admin', 'chanceler', 'hospitaleiro', 'primeiro_vigilante', 'segundo_vigilante', 'secretario', 'tesoureiro', 'bibliotecario', 'veneravel', 'mestre_banquetes', 'orador'];
        foreach ($prioridade as $cargo) {
            if (in_array($cargo, $slugs, true)) {
                return $cargo;
            }
        }

        $fallbackNormalizado = strtolower(trim((string) $fallback));
        if ($fallbackNormalizado !== '') {
            return $fallbackNormalizado;
        }

        return $slugs[0] ?? '';
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function garantirEscopoLoja(): void
    {
        if ($this->suportaEscopoLoja()) {
            return;
        }

        throw new \RuntimeException('Escopo por loja ausente em cargos/atribuicoes_cargo/gestoes. Execute a migration de isolamento por loja.');
    }

    private function suportaEscopoLoja(): bool
    {
        if ($this->suportaEscopoLoja !== null) {
            return $this->suportaEscopoLoja;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND column_name = 'loja_id'
               AND table_name IN ('cargos', 'atribuicoes_cargo', 'gestoes')"
        );
        $stmt->execute();
        $this->suportaEscopoLoja = ((int) $stmt->fetchColumn()) === 3;

        return $this->suportaEscopoLoja;
    }
}
