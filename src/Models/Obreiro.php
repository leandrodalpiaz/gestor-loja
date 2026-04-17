<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class Obreiro
{
    use ResolvesStoreTenant;

    public const ESTADOS_CIVIS = [
        'solteiro',
        'casado',
        'divorciado',
        'separado',
        'viuvo',
        'uniao_estavel',
        'nao_informado',
    ];

    public const ESCOLARIDADES = [
        'fundamental_incompleto',
        'fundamental_completo',
        'medio_incompleto',
        'medio_completo',
        'tecnico',
        'superior_incompleto',
        'superior_completo',
        'pos_graduacao',
        'mestrado',
        'doutorado',
        'nao_informado',
    ];

    public const FAIXAS_RENDA = [
        'ate_1_sm',
        'de_1_a_3_sm',
        'de_3_a_5_sm',
        'de_5_a_10_sm',
        'acima_10_sm',
        'nao_informado',
    ];

    public const SITUACOES_QUADRO = [
        'ativo',
        'licenciado',
        'suspenso',
        'desligado',
        'falecido',
        'oriente_eterno',
        'inativo',
    ];

    private PDO $db;
    private ?bool $suportaLojaId = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function suportaLojaId(): bool
    {
        if ($this->suportaLojaId !== null) {
            return $this->suportaLojaId;
        }

        $stmt = $this->db->prepare(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND table_name = 'obreiros'
               AND column_name = 'loja_id'
             LIMIT 1"
        );
        $stmt->execute();

        $this->suportaLojaId = (bool) $stmt->fetchColumn();

        return $this->suportaLojaId;
    }

    /**
     * Atualiza apenas o cargo legado de um obreiro pelo ID.
     * Mantido por compatibilidade com telas antigas.
     */
    public function atualizarCargo($id, $novoCargo): bool
    {
        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare("UPDATE obreiros SET cargo = :cargo WHERE id = :id AND loja_id = :loja_id");
            return $stmt->execute([
                'cargo' => $novoCargo,
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        }

        $stmt = $this->db->prepare("UPDATE obreiros SET cargo = :cargo WHERE id = :id");
        return $stmt->execute([
            'cargo' => $novoCargo,
            'id' => $id,
        ]);
    }

    public function findByTelegramId(int $telegramId): ?array
    {
        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM obreiros
                 WHERE telegram_id = :telegram_id
                   AND ativo = true
                   AND loja_id = :loja_id
                 LIMIT 1"
            );
            $stmt->execute([
                'telegram_id' => $telegramId,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM obreiros
                 WHERE telegram_id = :telegram_id
                   AND ativo = true
                 ORDER BY nome ASC
                 LIMIT 1"
            );
            $stmt->execute(['telegram_id' => $telegramId]);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function getAllAtivos(): array
    {
        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM obreiros
                 WHERE ativo = true
                   AND loja_id = :loja_id
                 ORDER BY nome ASC"
            );
            $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);
        } else {
            $stmt = $this->db->query(
                "SELECT *
                 FROM obreiros
                 WHERE ativo = true
                 ORDER BY nome ASC"
            );
        }

        return $this->hidratarListaCargosAtivos($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function listarParaSecretaria(array $filtros = []): array
    {
        if ($this->suportaLojaId()) {
            $sql = "SELECT *
                    FROM public.obreiros
                    WHERE loja_id = :loja_id";
            $params = ['loja_id' => $this->obterLojaAtualId()];
        } else {
            $sql = "SELECT *
                    FROM public.obreiros
                    WHERE 1 = 1";
            $params = [];
        }

        $busca = trim((string) ($filtros['busca'] ?? ''));
        if ($busca !== '') {
            $sql .= " AND (
                nome ILIKE :busca
                OR COALESCE(nome_historico, '') ILIKE :busca
                OR COALESCE(cim::text, '') ILIKE :busca
                OR COALESCE(grau, '') ILIKE :busca
            )";
            $params['busca'] = '%' . $busca . '%';
        }

        $situacao = trim((string) ($filtros['situacao'] ?? ''));
        if ($situacao !== '') {
            $sql .= " AND COALESCE(situacao_quadro, 'ativo') = :situacao";
            $params['situacao'] = $situacao;
        }

        $grau = trim((string) ($filtros['grau'] ?? ''));
        if ($grau !== '') {
            $sql .= " AND COALESCE(grau, '') = :grau";
            $params['grau'] = $grau;
        }

        $alerta = trim((string) ($filtros['alerta'] ?? $filtros['pendencia'] ?? ''));
        if ($alerta === 'cadastro') {
            $sql .= " AND (
                data_nascimento_civil IS NULL
                OR COALESCE(escolaridade, '') = ''
                OR COALESCE(profissao, '') = ''
                OR COALESCE(situacao_quadro, '') = ''
                OR (COALESCE(situacao_quadro, 'ativo') = 'ativo' AND data_filiacao IS NULL AND data_iniciacao IS NULL)
            )";
        }

        $cargoCodigo = trim((string) ($filtros['cargo_codigo'] ?? ''));
        if ($cargoCodigo !== '') {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM public.atribuicoes_cargo ac
                JOIN public.cargos c ON c.id = ac.cargo_id
                LEFT JOIN public.gestoes g ON g.id = ac.gestao_id
                WHERE ac.obreiro_id = obreiros.id
                  AND ac.fim_em IS NULL
                  AND c.ativo = TRUE
                  AND c.codigo = :cargo_codigo
                  AND (g.id IS NULL OR g.status = 'aberta')
            )";
            $params['cargo_codigo'] = strtoupper($cargoCodigo);
        }

        $ordenacao = trim((string) ($filtros['ordenacao'] ?? 'nome'));
        $sql .= ' ORDER BY ' . $this->resolverOrdenacaoSecretaria($ordenacao);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $obreiros = $this->hidratarListaCargosAtivos($stmt->fetchAll(PDO::FETCH_ASSOC));

        foreach ($obreiros as &$obreiro) {
            $obreiro['alertas_cadastro'] = $this->mapearPendenciasCadastro($obreiro);
        }
        unset($obreiro);

        if ($ordenacao === 'alerta') {
            usort($obreiros, function (array $a, array $b): int {
                return count($b['alertas_cadastro'] ?? []) <=> count($a['alertas_cadastro'] ?? []);
            });
        }

        return $obreiros;
    }

    public function obterResumoSecretaria(array $filtros = []): array
    {
        $obreiros = $this->listarParaSecretaria($filtros);
        $resumo = [
            'total' => count($obreiros),
            'ativos' => 0,
            'com_alerta' => 0,
            'com_telegram' => 0,
            'mestres' => 0,
        ];

        foreach ($obreiros as $obreiro) {
            $situacao = (string) ($obreiro['situacao_quadro'] ?? 'ativo');
            if (in_array($situacao, ['ativo', 'licenciado', 'suspenso'], true)) {
                $resumo['ativos']++;
            }
            if (!empty($obreiro['alertas_cadastro'])) {
                $resumo['com_alerta']++;
            }
            if (!empty($obreiro['telegram_id'])) {
                $resumo['com_telegram']++;
            }
            if (in_array((string) ($obreiro['grau'] ?? ''), ['Mestre', 'Mestre Instalado'], true)) {
                $resumo['mestres']++;
            }
        }

        return $resumo;
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

        if ($this->suportaLojaId()) {
            $sql = str_replace(
                'WHERE ativo = true',
                'WHERE ativo = true
              AND loja_id = :loja_id',
                $sql
            );
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorAniversario($data): array
    {
        if ($this->suportaLojaId()) {
            $sql = "SELECT * FROM obreiros WHERE loja_id = ? AND TO_CHAR(data_nascimento_civil, 'MM-DD') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->obterLojaAtualId(), $data]);
        } else {
            $sql = "SELECT * FROM obreiros WHERE TO_CHAR(data_nascimento_civil, 'MM-DD') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorDatasMaconicas($data): array
    {
        if ($this->suportaLojaId()) {
            $sql = "SELECT nome, 'Iniciacao' as tipo, data_iniciacao as data FROM obreiros WHERE loja_id = ? AND TO_CHAR(data_iniciacao, 'MM-DD') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->obterLojaAtualId(), $data]);
        } else {
            $sql = "SELECT nome, 'Iniciacao' as tipo, data_iniciacao as data FROM obreiros WHERE TO_CHAR(data_iniciacao, 'MM-DD') = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        if ($this->suportaLojaId()) {
            $sql = "INSERT INTO obreiros (
            loja_id,
            cim, nome, nome_historico, cpf,
            data_nascimento_civil, estado_civil, data_iniciacao, data_filiacao,
            data_regularizacao, data_reintegracao, data_quite_placet,
            data_suspensao, data_desligamento, data_oriente_eterno,
            telefone, email, profissao, escolaridade, faixa_renda,
            loja_origem, grau, cargo, situacao_quadro,
            data_elevacao, data_exaltacao, telegram_id,
            potencia_nome, potencia_sigla, numero_quadro, potencia_login,
            acesso_potencia_liberado, acesso_potencia_liberado_em,
            observacao_secretaria, ativo,
            created_at, updated_at
        ) VALUES (
            :loja_id,
            :cim, :nome, :nome_historico, :cpf,
            :data_nascimento_civil, :estado_civil, :data_iniciacao, :data_filiacao,
            :data_regularizacao, :data_reintegracao, :data_quite_placet,
            :data_suspensao, :data_desligamento, :data_oriente_eterno,
            :telefone, :email, :profissao, :escolaridade, :faixa_renda,
            :loja_origem, :grau, :cargo, :situacao_quadro,
            :data_elevacao, :data_exaltacao, :telegram_id,
            :potencia_nome, :potencia_sigla, :numero_quadro, :potencia_login,
            :acesso_potencia_liberado, :acesso_potencia_liberado_em,
            :observacao_secretaria, :ativo,
            NOW(), NOW()
        )";

            $stmt = $this->db->prepare($sql);
        } else {
            $sql = "INSERT INTO obreiros (
            cim, nome, nome_historico, cpf,
            data_nascimento_civil, estado_civil, data_iniciacao, data_filiacao,
            data_regularizacao, data_reintegracao, data_quite_placet,
            data_suspensao, data_desligamento, data_oriente_eterno,
            telefone, email, profissao, escolaridade, faixa_renda,
            loja_origem, grau, cargo, situacao_quadro,
            data_elevacao, data_exaltacao, telegram_id,
            potencia_nome, potencia_sigla, numero_quadro, potencia_login,
            acesso_potencia_liberado, acesso_potencia_liberado_em,
            observacao_secretaria, ativo,
            created_at, updated_at
        ) VALUES (
            :cim, :nome, :nome_historico, :cpf,
            :data_nascimento_civil, :estado_civil, :data_iniciacao, :data_filiacao,
            :data_regularizacao, :data_reintegracao, :data_quite_placet,
            :data_suspensao, :data_desligamento, :data_oriente_eterno,
            :telefone, :email, :profissao, :escolaridade, :faixa_renda,
            :loja_origem, :grau, :cargo, :situacao_quadro,
            :data_elevacao, :data_exaltacao, :telegram_id,
            :potencia_nome, :potencia_sigla, :numero_quadro, :potencia_login,
            :acesso_potencia_liberado, :acesso_potencia_liberado_em,
            :observacao_secretaria, :ativo,
            NOW(), NOW()
        )";
            $stmt = $this->db->prepare($sql);
        }

        $nascimento = !empty($data['data_nascimento_civil']) ? $data['data_nascimento_civil'] : null;
        $iniciacao = !empty($data['data_iniciacao']) ? $data['data_iniciacao'] : null;
        $situacaoQuadro = $this->normalizarSituacaoQuadro($data['situacao_quadro'] ?? null);
        $ativo = $this->situacaoMantemCadastroAtivo($situacaoQuadro) ? 'true' : 'false';

        $params = [
            'cim' => $data['cim'] ?? null,
            'nome' => $data['nome_completo'] ?? null,
            'nome_historico' => $data['nome_historico'] ?? null,
            'cpf' => $data['cpf'] ?? null,
            'data_nascimento_civil' => $nascimento,
            'estado_civil' => $this->normalizarValorEnum($data['estado_civil'] ?? null, self::ESTADOS_CIVIS),
            'data_iniciacao' => $iniciacao,
            'data_filiacao' => $this->normalizarData($data['data_filiacao'] ?? null),
            'data_regularizacao' => $this->normalizarData($data['data_regularizacao'] ?? null),
            'data_reintegracao' => $this->normalizarData($data['data_reintegracao'] ?? null),
            'data_quite_placet' => $this->normalizarData($data['data_quite_placet'] ?? null),
            'data_suspensao' => $this->normalizarData($data['data_suspensao'] ?? null),
            'data_desligamento' => $this->normalizarData($data['data_desligamento'] ?? null),
            'data_oriente_eterno' => $this->normalizarData($data['data_oriente_eterno'] ?? null),
            'telefone' => $data['telefone'] ?? null,
            'email' => $data['email'] ?? null,
            'profissao' => $data['profissao'] ?? null,
            'escolaridade' => $this->normalizarValorEnum($data['escolaridade'] ?? null, self::ESCOLARIDADES),
            'faixa_renda' => $this->normalizarValorEnum($data['faixa_renda'] ?? null, self::FAIXAS_RENDA),
            'loja_origem' => $data['loja_origem'] ?? null,
            'grau' => $data['grau'] ?? null,
            'cargo' => $data['cargo'] ?? null,
            'situacao_quadro' => $situacaoQuadro,
            'data_elevacao' => !empty($data['data_elevacao']) ? $data['data_elevacao'] : null,
            'data_exaltacao' => !empty($data['data_exaltacao']) ? $data['data_exaltacao'] : null,
            'telegram_id' => !empty($data['telegram_id']) ? (int) $data['telegram_id'] : null,
            'potencia_nome' => $data['potencia_nome'] ?? null,
            'potencia_sigla' => $data['potencia_sigla'] ?? null,
            'numero_quadro' => $data['numero_quadro'] ?? null,
            'potencia_login' => $data['potencia_login'] ?? null,
            'acesso_potencia_liberado' => !empty($data['acesso_potencia_liberado']) ? 'true' : 'false',
            'acesso_potencia_liberado_em' => !empty($data['acesso_potencia_liberado'])
                ? (!empty($data['acesso_potencia_liberado_em']) ? $data['acesso_potencia_liberado_em'] : date('c'))
                : null,
            'observacao_secretaria' => $data['observacao_secretaria'] ?? null,
            'ativo' => $ativo,
        ];

        if ($this->suportaLojaId()) {
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        return $stmt->execute($params);
    }

    public function findById(string $id): ?array
    {
        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE id = :id AND loja_id = :loja_id LIMIT 1");
            $stmt->execute([
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function update(array $data): bool
    {
        $sql = "UPDATE obreiros SET
            cim = :cim,
            nome = :nome,
            nome_historico = :nome_historico,
            cpf = :cpf,
            grau = :grau,
            cargo = :cargo,
            loja_origem = :loja_origem,
            data_nascimento_civil = :data_nascimento_civil,
            estado_civil = :estado_civil,
            data_iniciacao = :data_iniciacao,
            data_filiacao = :data_filiacao,
            data_regularizacao = :data_regularizacao,
            data_reintegracao = :data_reintegracao,
            data_quite_placet = :data_quite_placet,
            data_suspensao = :data_suspensao,
            data_desligamento = :data_desligamento,
            data_oriente_eterno = :data_oriente_eterno,
            data_elevacao = :data_elevacao,
            data_exaltacao = :data_exaltacao,
            telefone = :telefone,
            email = :email,
            profissao = :profissao,
            escolaridade = :escolaridade,
            faixa_renda = :faixa_renda,
            situacao_quadro = :situacao_quadro,
            telegram_id = :telegram_id,
            potencia_nome = :potencia_nome,
            potencia_sigla = :potencia_sigla,
            numero_quadro = :numero_quadro,
            potencia_login = :potencia_login,
            acesso_potencia_liberado = :acesso_potencia_liberado,
            acesso_potencia_liberado_em = :acesso_potencia_liberado_em,
            observacao_secretaria = :observacao_secretaria,
            ativo = :ativo,
            updated_at = NOW()
            WHERE id = :id";

        if ($this->suportaLojaId()) {
            $sql .= "
              AND loja_id = :loja_id";
        }

        $stmt = $this->db->prepare($sql);

        $nascimento = !empty($data['data_nascimento_civil']) ? $data['data_nascimento_civil'] : null;
        $iniciacao = !empty($data['data_iniciacao']) ? $data['data_iniciacao'] : null;
        $situacaoQuadro = $this->normalizarSituacaoQuadro($data['situacao_quadro'] ?? null);
        $ativoManual = (isset($data['ativo']) && $data['ativo'] == '1');
        $ativo = ($ativoManual && $this->situacaoMantemCadastroAtivo($situacaoQuadro)) ? 'true' : 'false';

        $params = [
            'id' => $data['id'],
            'cim' => $data['cim'],
            'nome' => $data['nome_completo'],
            'nome_historico' => $data['nome_historico'],
            'cpf' => $data['cpf'] ?? null,
            'grau' => $data['grau'],
            'cargo' => $data['cargo'],
            'loja_origem' => $data['loja_origem'],
            'data_nascimento_civil' => $nascimento,
            'estado_civil' => $this->normalizarValorEnum($data['estado_civil'] ?? null, self::ESTADOS_CIVIS),
            'data_iniciacao' => $iniciacao,
            'data_filiacao' => $this->normalizarData($data['data_filiacao'] ?? null),
            'data_regularizacao' => $this->normalizarData($data['data_regularizacao'] ?? null),
            'data_reintegracao' => $this->normalizarData($data['data_reintegracao'] ?? null),
            'data_quite_placet' => $this->normalizarData($data['data_quite_placet'] ?? null),
            'data_suspensao' => $this->normalizarData($data['data_suspensao'] ?? null),
            'data_desligamento' => $this->normalizarData($data['data_desligamento'] ?? null),
            'data_oriente_eterno' => $this->normalizarData($data['data_oriente_eterno'] ?? null),
            'data_elevacao' => !empty($data['data_elevacao']) ? $data['data_elevacao'] : null,
            'data_exaltacao' => !empty($data['data_exaltacao']) ? $data['data_exaltacao'] : null,
            'telefone' => $data['telefone'],
            'email' => $data['email'],
            'profissao' => $data['profissao'] ?? null,
            'escolaridade' => $this->normalizarValorEnum($data['escolaridade'] ?? null, self::ESCOLARIDADES),
            'faixa_renda' => $this->normalizarValorEnum($data['faixa_renda'] ?? null, self::FAIXAS_RENDA),
            'situacao_quadro' => $situacaoQuadro,
            'telegram_id' => !empty($data['telegram_id']) ? (int) $data['telegram_id'] : null,
            'potencia_nome' => $data['potencia_nome'] ?? null,
            'potencia_sigla' => $data['potencia_sigla'] ?? null,
            'numero_quadro' => $data['numero_quadro'] ?? null,
            'potencia_login' => $data['potencia_login'] ?? null,
            'acesso_potencia_liberado' => !empty($data['acesso_potencia_liberado']) ? 'true' : 'false',
            'acesso_potencia_liberado_em' => !empty($data['acesso_potencia_liberado'])
                ? (!empty($data['acesso_potencia_liberado_em']) ? $data['acesso_potencia_liberado_em'] : date('c'))
                : null,
            'observacao_secretaria' => $data['observacao_secretaria'] ?? null,
            'ativo' => $ativo,
        ];

        if ($this->suportaLojaId()) {
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        return $stmt->execute($params);
    }

    public function autenticar(string $matricula, string $senha): ?array
    {
        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM obreiros
                 WHERE cim = :matricula
                   AND ativo = true
                   AND loja_id = :loja_id
                 LIMIT 1"
            );
            $stmt->execute([
                'matricula' => $matricula,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM obreiros
                 WHERE cim = :matricula
                   AND ativo = true
                 ORDER BY nome ASC
                 LIMIT 1"
            );
            $stmt->execute(['matricula' => $matricula]);
        }
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && !empty($usuario['senha_hash']) && password_verify($senha, $usuario['senha_hash'])) {
            return $this->hidratarCargosAtivos($usuario);
        }

        return null;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function deveAplicarEscopoTenantNaIdentidade(): bool
    {
        return isset($_SESSION['tenant_id']) || isset($_SESSION['tenant_slug']) || isset($_SESSION['tenant_name']);
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

    private function normalizarData(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }

    private function normalizarValorEnum(?string $valor, array $permitidos): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '' || !in_array($valor, $permitidos, true)) {
            return null;
        }

        return $valor;
    }

    private function normalizarSituacaoQuadro(?string $valor): string
    {
        $valor = trim((string) $valor);
        return in_array($valor, self::SITUACOES_QUADRO, true) ? $valor : 'ativo';
    }

    private function situacaoMantemCadastroAtivo(string $situacao): bool
    {
        return in_array($situacao, ['ativo', 'licenciado', 'suspenso'], true);
    }

    private function resolverOrdenacaoSecretaria(string $ordenacao): string
    {
        return match ($ordenacao) {
            'grau' => "COALESCE(grau, '') ASC, COALESCE(nome_historico, nome) ASC",
            'situacao' => "COALESCE(situacao_quadro, 'ativo') ASC, COALESCE(nome_historico, nome) ASC",
            'nome' => "COALESCE(nome_historico, nome) ASC",
            'alerta' => "COALESCE(nome_historico, nome) ASC",
            default => "COALESCE(nome_historico, nome) ASC",
        };
    }

    private function mapearPendenciasCadastro(array $obreiro): array
    {
        $pendencias = [];

        if (empty($obreiro['data_nascimento_civil'])) {
            $pendencias[] = 'sem_nascimento';
        }
        if (empty($obreiro['escolaridade'])) {
            $pendencias[] = 'sem_escolaridade';
        }
        if (empty($obreiro['profissao'])) {
            $pendencias[] = 'sem_profissao';
        }
        if (empty($obreiro['situacao_quadro'])) {
            $pendencias[] = 'sem_situacao';
        }
        if (
            (($obreiro['situacao_quadro'] ?? 'ativo') === 'ativo')
            && empty($obreiro['data_filiacao'])
            && empty($obreiro['data_iniciacao'])
        ) {
            $pendencias[] = 'sem_data_ingresso';
        }
        if (empty($obreiro['potencia_nome'])) {
            $pendencias[] = 'sem_potencia';
        }

        return $pendencias;
    }
}
