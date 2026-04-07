<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Cargo
{
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
             WHERE ac.obreiro_id = :obreiro_id
               AND ac.fim_em IS NULL
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

    public function listarResumoCargos(): array
    {
        $stmt = $this->db->query(
            "SELECT
                c.codigo,
                c.nome_exibicao,
                c.ativo,
                a.obreiro_id AS titular_id,
                COALESCE(o.nome_historico, o.nome) AS titular_nome,
                o.cim AS titular_cim,
                a.inicio_em,
                a.observacao
             FROM public.cargos c
             LEFT JOIN public.atribuicoes_cargo a
               ON a.cargo_id = c.id
              AND a.fim_em IS NULL
             LEFT JOIN public.obreiros o
               ON o.id = a.obreiro_id
             WHERE c.ativo = TRUE
             ORDER BY c.nome_exibicao ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarHistorico(int $limite = 50, ?string $cargoCodigo = null): array
    {
        $sql = "SELECT
                    c.codigo,
                    c.nome_exibicao,
                    ac.obreiro_id,
                    COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                    o.cim,
                    ac.inicio_em,
                    ac.fim_em,
                    ac.observacao
                FROM public.atribuicoes_cargo ac
                JOIN public.cargos c ON c.id = ac.cargo_id
                JOIN public.obreiros o ON o.id = ac.obreiro_id";

        $params = [];
        if ($cargoCodigo !== null && trim($cargoCodigo) !== '') {
            $sql .= " WHERE c.codigo = :cargo_codigo";
            $params['cargo_codigo'] = strtoupper(trim($cargoCodigo));
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

    public function atribuirPorCodigo(string $cargoCodigo, string $obreiroId, ?string $observacao = null): void
    {
        $stmt = $this->db->prepare(
            "SELECT public.atribuir_cargo(:cargo_codigo, :obreiro_id, :observacao)"
        );
        $stmt->execute([
            'cargo_codigo' => strtoupper(trim($cargoCodigo)),
            'obreiro_id' => $obreiroId,
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
        ]);
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
        $prioridade = ['admin', 'chanceler', 'hospitaleiro', 'primeiro_vigilante', 'segundo_vigilante', 'secretario', 'tesoureiro', 'bibliotecario', 'veneravel', 'mestre_banquetes'];
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
