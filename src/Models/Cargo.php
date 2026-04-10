<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Cargo
{
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

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getCodigosAtivosDoObreiro(string $obreiroId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.codigo
             FROM public.atribuicoes_cargo ac
             JOIN public.cargos c ON c.id = ac.cargo_id
             LEFT JOIN public.gestoes g ON g.id = ac.gestao_id
             WHERE ac.obreiro_id = :obreiro_id
               AND ac.fim_em IS NULL
               AND (g.id IS NULL OR g.status = 'aberta')
               AND c.ativo = TRUE
             ORDER BY c.nome_exibicao ASC"
        );
        $stmt->execute(['obreiro_id' => $obreiroId]);

        return array_values(array_unique(array_filter(array_map(
            static fn ($row) => strtoupper((string) ($row['codigo'] ?? '')),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        ))));
    }

    public function obreiroTemCargo(string $obreiroId, string $cargoCodigo): bool
    {
        $stmt = $this->db->prepare(
            "SELECT public.tem_cargo(:obreiro_id, :cargo_codigo) AS possui"
        );
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'cargo_codigo' => strtoupper(trim($cargoCodigo)),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (bool) ($row['possui'] ?? false);
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
             FROM public.cargos c
             LEFT JOIN public.atribuicoes_cargo a
               ON a.cargo_id = c.id
              AND a.fim_em IS NULL
             LEFT JOIN public.gestoes g
               ON g.id = a.gestao_id
             LEFT JOIN public.obreiros o
               ON o.id = a.obreiro_id
             WHERE c.ativo = TRUE";

        if ($gestaoId !== null) {
            $sql .= " AND (g.id = :gestao_id OR (g.id IS NULL AND :gestao_id = 0))";
        } else {
            $sql .= " AND (g.status = 'aberta' OR g.id IS NULL)";
        }

        $sql .= " ORDER BY c.nome_exibicao ASC";

        $stmt = $this->db->prepare($sql);
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
                FROM public.atribuicoes_cargo ac
                JOIN public.cargos c ON c.id = ac.cargo_id
                LEFT JOIN public.gestoes g ON g.id = ac.gestao_id
                JOIN public.obreiros o ON o.id = ac.obreiro_id";

        $params = [];
        $where = [];
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
            "SELECT public.atribuir_cargo(:cargo_codigo, :obreiro_id, :gestao_id, :inicio_em, :observacao)"
        );
        $stmt->execute([
            'cargo_codigo' => strtoupper(trim($cargoCodigo)),
            'obreiro_id' => $obreiroId,
            'gestao_id' => $gestaoId,
            'inicio_em' => $inicioEm !== null && trim($inicioEm) !== '' ? $inicioEm : date('c'),
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
        ]);
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
}
