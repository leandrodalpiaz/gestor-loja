<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Obreiro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Atualiza apenas o cargo legado de um obreiro pelo ID.
     * Mantido por compatibilidade com telas antigas.
     */
    public function atualizarCargo($id, $novoCargo): bool
    {
        $stmt = $this->db->prepare("UPDATE obreiros SET cargo = :cargo WHERE id = :id");
        return $stmt->execute([
            'cargo' => $novoCargo,
            'id' => $id,
        ]);
    }

    public function findByTelegramId(int $telegramId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE telegram_id = :telegram_id AND ativo = true LIMIT 1");
        $stmt->execute(['telegram_id' => $telegramId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function getAllAtivos(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE ativo = true ORDER BY nome ASC");
        $stmt->execute();

        return $this->hidratarListaCargosAtivos($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getEfemeridesDoDia(): array
    {
        $sql = "
            SELECT
                nome_historico,
                nome,
                grau,
                data_nascimento_civil,
                data_iniciacao,
                CASE WHEN EXTRACT(MONTH FROM data_nascimento_civil) = EXTRACT(MONTH FROM CURRENT_DATE)
                      AND EXTRACT(DAY FROM data_nascimento_civil) = EXTRACT(DAY FROM CURRENT_DATE)
                     THEN true ELSE false END as is_aniversario_civil,
                CASE WHEN EXTRACT(MONTH FROM data_iniciacao) = EXTRACT(MONTH FROM CURRENT_DATE)
                      AND EXTRACT(DAY FROM data_iniciacao) = EXTRACT(DAY FROM CURRENT_DATE)
                     THEN true ELSE false END as is_aniversario_maconico
            FROM obreiros
            WHERE ativo = true
              AND (
                  (EXTRACT(MONTH FROM data_nascimento_civil) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(DAY FROM data_nascimento_civil) = EXTRACT(DAY FROM CURRENT_DATE))
                  OR
                  (EXTRACT(MONTH FROM data_iniciacao) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(DAY FROM data_iniciacao) = EXTRACT(DAY FROM CURRENT_DATE))
              )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorAniversario($data): array
    {
        $sql = "SELECT * FROM obreiros WHERE TO_CHAR(data_nascimento_civil, 'MM-DD') = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorDatasMaconicas($data): array
    {
        $sql = "SELECT nome, 'Iniciacao' as tipo, data_iniciacao as data FROM obreiros WHERE TO_CHAR(data_iniciacao, 'MM-DD') = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO obreiros (
            cim, nome, nome_historico, cpf,
            data_nascimento_civil, data_iniciacao, telefone,
            email, profissao, loja_origem, grau, cargo,
            data_elevacao, data_exaltacao, telegram_id,
            potencia_login, acesso_potencia_liberado,
            acesso_potencia_liberado_em, observacao_secretaria, ativo
        ) VALUES (
            :cim, :nome, :nome_historico, :cpf,
            :data_nascimento_civil, :data_iniciacao, :telefone,
            :email, :profissao, :loja_origem, :grau, :cargo,
            :data_elevacao, :data_exaltacao, :telegram_id,
            :potencia_login, :acesso_potencia_liberado,
            :acesso_potencia_liberado_em, :observacao_secretaria, true
        )";

        $stmt = $this->db->prepare($sql);

        $nascimento = !empty($data['data_nascimento_civil']) ? $data['data_nascimento_civil'] : null;
        $iniciacao = !empty($data['data_iniciacao']) ? $data['data_iniciacao'] : null;

        return $stmt->execute([
            'cim' => $data['cim'] ?? null,
            'nome' => $data['nome_completo'] ?? null,
            'nome_historico' => $data['nome_historico'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'data_nascimento_civil' => $nascimento,
            'data_iniciacao' => $iniciacao,
            'telefone' => $data['telefone'] ?? null,
            'email' => $data['email'] ?? null,
            'profissao' => $data['profissao'] ?? null,
            'loja_origem' => $data['loja_origem'] ?? null,
            'grau' => $data['grau'] ?? null,
            'cargo' => $data['cargo'] ?? null,
            'data_elevacao' => !empty($data['data_elevacao']) ? $data['data_elevacao'] : null,
            'data_exaltacao' => !empty($data['data_exaltacao']) ? $data['data_exaltacao'] : null,
            'telegram_id' => !empty($data['telegram_id']) ? (int) $data['telegram_id'] : null,
            'potencia_login' => $data['potencia_login'] ?? null,
            'acesso_potencia_liberado' => !empty($data['acesso_potencia_liberado']) ? 'true' : 'false',
            'acesso_potencia_liberado_em' => !empty($data['acesso_potencia_liberado'])
                ? (!empty($data['acesso_potencia_liberado_em']) ? $data['acesso_potencia_liberado_em'] : date('c'))
                : null,
            'observacao_secretaria' => $data['observacao_secretaria'] ?? null,
        ]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function update(array $data): bool
    {
        $sql = "UPDATE obreiros SET
            cim = :cim,
            nome = :nome,
            nome_historico = :nome_historico,
            grau = :grau,
            cargo = :cargo,
            loja_origem = :loja_origem,
            data_nascimento_civil = :data_nascimento_civil,
            data_iniciacao = :data_iniciacao,
            data_elevacao = :data_elevacao,
            data_exaltacao = :data_exaltacao,
            telefone = :telefone,
            email = :email,
            profissao = :profissao,
            telegram_id = :telegram_id,
            potencia_login = :potencia_login,
            acesso_potencia_liberado = :acesso_potencia_liberado,
            acesso_potencia_liberado_em = :acesso_potencia_liberado_em,
            observacao_secretaria = :observacao_secretaria,
            ativo = :ativo
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $nascimento = !empty($data['data_nascimento_civil']) ? $data['data_nascimento_civil'] : null;
        $iniciacao = !empty($data['data_iniciacao']) ? $data['data_iniciacao'] : null;
        $ativo = (isset($data['ativo']) && $data['ativo'] == '1') ? 'true' : 'false';

        return $stmt->execute([
            'id' => $data['id'],
            'cim' => $data['cim'],
            'nome' => $data['nome_completo'],
            'nome_historico' => $data['nome_historico'],
            'grau' => $data['grau'],
            'cargo' => $data['cargo'],
            'loja_origem' => $data['loja_origem'],
            'data_nascimento_civil' => $nascimento,
            'data_iniciacao' => $iniciacao,
            'data_elevacao' => !empty($data['data_elevacao']) ? $data['data_elevacao'] : null,
            'data_exaltacao' => !empty($data['data_exaltacao']) ? $data['data_exaltacao'] : null,
            'telefone' => $data['telefone'],
            'email' => $data['email'],
            'profissao' => $data['profissao'] ?? null,
            'telegram_id' => !empty($data['telegram_id']) ? (int) $data['telegram_id'] : null,
            'potencia_login' => $data['potencia_login'] ?? null,
            'acesso_potencia_liberado' => !empty($data['acesso_potencia_liberado']) ? 'true' : 'false',
            'acesso_potencia_liberado_em' => !empty($data['acesso_potencia_liberado'])
                ? (!empty($data['acesso_potencia_liberado_em']) ? $data['acesso_potencia_liberado_em'] : date('c'))
                : null,
            'observacao_secretaria' => $data['observacao_secretaria'] ?? null,
            'ativo' => $ativo,
        ]);
    }

    public function autenticar(string $matricula, string $senha): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE cim = :matricula AND ativo = true LIMIT 1");
        $stmt->execute(['matricula' => $matricula]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && !empty($usuario['senha_hash']) && password_verify($senha, $usuario['senha_hash'])) {
            return $this->hidratarCargosAtivos($usuario);
        }

        return null;
    }

    private function hidratarCargosAtivos(array $obreiro): array
    {
        $codigosPorObreiro = $this->buscarCodigosAtivosPorObreiroIds([(string) ($obreiro['id'] ?? '')]);
        $cargosCodigos = $codigosPorObreiro[(string) ($obreiro['id'] ?? '')] ?? [];
        $cargosSlugs = Cargo::slugsFromCodigos($cargosCodigos);

        $obreiro['cargos_codigos'] = $cargosCodigos;
        $obreiro['cargos'] = $cargosSlugs;
        $obreiro['cargo_principal'] = Cargo::resolverCargoPrincipal(
            $cargosSlugs,
            $this->normalizarCargoLegado((string) ($obreiro['cargo'] ?? ''))
        );

        return $obreiro;
    }

    private function hidratarListaCargosAtivos(array $obreiros): array
    {
        if ($obreiros === []) {
            return [];
        }

        $ids = array_values(array_filter(array_map(
            static fn ($row) => (string) ($row['id'] ?? ''),
            $obreiros
        )));

        $cargosPorObreiro = $this->buscarCodigosAtivosPorObreiroIds($ids);

        foreach ($obreiros as &$obreiro) {
            $id = (string) ($obreiro['id'] ?? '');
            $cargosCodigos = $cargosPorObreiro[$id] ?? [];
            $cargosSlugs = Cargo::slugsFromCodigos($cargosCodigos);

            $obreiro['cargos_codigos'] = $cargosCodigos;
            $obreiro['cargos'] = $cargosSlugs;
            $obreiro['cargo_principal'] = Cargo::resolverCargoPrincipal(
                $cargosSlugs,
                $this->normalizarCargoLegado((string) ($obreiro['cargo'] ?? ''))
            );
        }
        unset($obreiro);

        return $obreiros;
    }

    private function buscarCodigosAtivosPorObreiroIds(array $ids): array
    {
        $ids = array_values(array_filter(array_unique(array_map(
            static fn ($id) => trim((string) $id),
            $ids
        ))));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "SELECT ac.obreiro_id, c.codigo
                FROM public.atribuicoes_cargo ac
                JOIN public.cargos c ON c.id = ac.cargo_id
                WHERE ac.fim_em IS NULL
                  AND c.ativo = TRUE
                  AND ac.obreiro_id IN ($placeholders)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $obreiroId = (string) ($row['obreiro_id'] ?? '');
            $codigo = strtoupper((string) ($row['codigo'] ?? ''));
            if ($obreiroId === '' || $codigo === '') {
                continue;
            }
            $result[$obreiroId][] = $codigo;
        }

        foreach ($result as &$codigos) {
            $codigos = array_values(array_unique($codigos));
        }
        unset($codigos);

        return $result;
    }

    private function normalizarCargoLegado(string $cargo): string
    {
        $cargo = strtolower(trim($cargo));
        $cargo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $cargo) ?: $cargo;
        $cargo = preg_replace('/[^a-z0-9_]+/', '_', $cargo) ?? '';
        $cargo = trim($cargo, '_');

        return match ($cargo) {
            'administrador' => 'admin',
            'secretario' => 'secretario',
            'primeiro_vigilante', '1_vigilante', 'primeirovigilante' => 'primeiro_vigilante',
            'segundo_vigilante', '2_vigilante', 'segundovigilante' => 'segundo_vigilante',
            'tesoureiro' => 'tesoureiro',
            'chanceler' => 'chanceler',
            'veneravel', 'veneravel_mestre' => 'veneravel',
            'bibliotecario' => 'bibliotecario',
            'hospitaleiro', 'mestre_hospitaleiro' => 'hospitaleiro',
            'mestre_banquetes' => 'mestre_banquetes',
            default => $cargo,
        };
    }
}
