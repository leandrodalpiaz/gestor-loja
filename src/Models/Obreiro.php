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
    private array $suportaColunaCache = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function deveOcultarSystemAdminsEmListas(): bool
    {
        // System admin é suporte técnico, não membro ativo: não deve aparecer em listas comuns.
        return true;
    }

    private function aplicarSanitizacaoObreirosEmSql(string &$sql, array &$params): void
    {
        // Oculta registros técnicos/teste do contexto da Loja (somente visual/listagem).
        $sql .= " AND COALESCE(LOWER(NULLIF(nome_historico, '')), LOWER(NULLIF(nome, ''))) NOT IN ('admin', 'administrador')";

        $rawCims = trim((string) (getenv('OBREIROS_SANITIZE_EXCLUDE_CIMS') ?: ''));
        if ($rawCims !== '') {
            $cims = preg_split('/\s*,\s*/', $rawCims, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $cims = array_values(array_unique(array_filter(array_map(
                static fn ($value) => preg_replace('/\D+/', '', (string) $value) ?: '',
                $cims
            ))));

            if ($cims !== []) {
                $placeholders = [];
                foreach ($cims as $i => $cim) {
                    $key = 'sanitize_cim_' . $i;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $cim;
                }

                $sql .= " AND (COALESCE(cim::text, '') = '' OR COALESCE(cim::text, '') NOT IN (" . implode(', ', $placeholders) . "))";
            }
        }

        $rawNomes = trim((string) (getenv('OBREIROS_SANITIZE_EXCLUDE_NAMES') ?: ''));
        if ($rawNomes !== '') {
            $nomes = preg_split('/\s*,\s*/', $rawNomes, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $nomes = array_values(array_unique(array_filter(array_map(
                static fn ($value) => strtolower(trim((string) $value)),
                $nomes
            ))));

            if ($nomes !== []) {
                $placeholders = [];
                foreach ($nomes as $i => $nome) {
                    $key = 'sanitize_nome_' . $i;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $nome;
                }

                $sql .= " AND COALESCE(LOWER(NULLIF(nome_historico, '')), LOWER(NULLIF(nome, ''))) NOT IN (" . implode(', ', $placeholders) . ")";
            }
        }
    }

    private function idsTelegramSystemAdmin(): array
    {
        $raw = trim((string) (getenv('SYSTEM_ADMIN_TELEGRAM_IDS') ?: ''));
        if ($raw === '') {
            return [];
        }

        $ids = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = [];
        foreach ($ids as $id) {
            $value = (int) trim((string) $id);
            if ($value > 0) {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }

    private function aplicarExclusaoSystemAdminsEmSql(string &$sql, array &$params): void
    {
        if (!$this->deveOcultarSystemAdminsEmListas()) {
            return;
        }

        $ids = $this->idsTelegramSystemAdmin();
        if ($ids === []) {
            return;
        }

        $placeholders = [];
        foreach ($ids as $i => $id) {
            $key = 'sysadm_' . $i;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        // Evita ocultar obreiro regular quando compartilha telegram_id com conta técnica.
        // Só exclui do resultado quando o registro também tem perfil típico de conta técnica.
        $sql .= " AND (
            telegram_id IS NULL
            OR telegram_id NOT IN (" . implode(', ', $placeholders) . ")
            OR (
                COALESCE(cim::text, '') <> ''
                AND COALESCE(LOWER(NULLIF(nome_historico, '')), LOWER(NULLIF(nome, ''))) NOT IN ('admin', 'administrador', 'system admin')
            )
        )";
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

    private function suportaColuna(string $coluna): bool
    {
        $coluna = strtolower(trim($coluna));
        if ($coluna === '') {
            return false;
        }

        if (array_key_exists($coluna, $this->suportaColunaCache)) {
            return (bool) $this->suportaColunaCache[$coluna];
        }

        $stmt = $this->db->prepare(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND table_name = 'obreiros'
               AND column_name = :coluna
             LIMIT 1"
        );
        $stmt->execute(['coluna' => $coluna]);
        $this->suportaColunaCache[$coluna] = (bool) $stmt->fetchColumn();

        return (bool) $this->suportaColunaCache[$coluna];
    }

    private function aplicarExclusaoManualEmSql(string &$sql, array &$params): void
    {
        // Exclusão controlada pelo usuário via banco (não quebra vínculos por ID).
        // Se a coluna existir, registros marcados não aparecem em listagens/efemérides.
        if ($this->suportaColuna('excluir_em_listas')) {
            $sql .= " AND COALESCE(excluir_em_listas, false) = false";
        }
    }

    /**
     * Atualiza apenas o cargo legado de um obreiro pelo ID.
     * Mantido por compatibilidade com telas antigas.
     */
    public function atualizarCargo($id, $novoCargo): bool
    {
        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare("UPDATE public.obreiros SET cargo = :cargo WHERE id = :id AND loja_id = :loja_id");
            return $stmt->execute([
                'cargo' => $novoCargo,
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        }

        $stmt = $this->db->prepare("UPDATE public.obreiros SET cargo = :cargo WHERE id = :id");
        return $stmt->execute([
            'cargo' => $novoCargo,
            'id' => $id,
        ]);
    }

    public function findByTelegramId(int $telegramId): ?array
    {
        $result = $this->findByTelegramIdAnyStatus($telegramId);

        if (!$result) {
            return null;
        }

        $status = $this->resolverAcessoStatus($result);
        if (!($result['ativo'] ?? false) || $status !== 'ativo') {
            return null;
        }

        return $this->hidratarCargosAtivos($result);
    }

    public function findByTelegramIdAnyStatus(int $telegramId): ?array
    {
        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM public.obreiros
                 WHERE telegram_id = :telegram_id
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
                 FROM public.obreiros
                 WHERE telegram_id = :telegram_id
                 ORDER BY nome ASC
                 LIMIT 1"
            );
            $stmt->execute(['telegram_id' => $telegramId]);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function findByCimAnyStatus(string $cim): ?array
    {
        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM public.obreiros
                 WHERE cim = :cim
                   AND loja_id = :loja_id
                 LIMIT 1"
            );
            $stmt->execute([
                'cim' => $cim,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM public.obreiros
                 WHERE cim = :cim
                 ORDER BY nome ASC
                 LIMIT 1"
            );
            $stmt->execute(['cim' => $cim]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return null;
        }

        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM public.obreiros
                 WHERE LOWER(email) = :email
                   AND loja_id = :loja_id
                 LIMIT 1"
            );
            $stmt->execute([
                'email' => $email,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM public.obreiros
                 WHERE LOWER(email) = :email
                 ORDER BY nome ASC
                 LIMIT 1"
            );
            $stmt->execute(['email' => $email]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function findByAuthUserId(string $authUserId): ?array
    {
        $authUserId = strtolower(trim($authUserId));
        if (!preg_match('/^[0-9a-f-]{36}$/', $authUserId)) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT *
             FROM public.obreiros
             WHERE auth_user_id = :auth_user_id
             LIMIT 1"
        );
        $stmt->execute(['auth_user_id' => $authUserId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function getAllAtivos(): array
    {
        $params = [];
        if ($this->suportaLojaId()) {
            $sql = "SELECT *
                    FROM public.obreiros
                    WHERE ativo = true
                      AND loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        } else {
            $sql = "SELECT *
                    FROM public.obreiros
                    WHERE ativo = true";
        }

        $this->aplicarExclusaoManualEmSql($sql, $params);
        $this->aplicarExclusaoSystemAdminsEmSql($sql, $params);
        $this->aplicarSanitizacaoObreirosEmSql($sql, $params);

        $sql .= " ORDER BY nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->hidratarListaCargosAtivos($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function buscarAtivosPorNome(string $nome, int $limite = 8): array
    {
        $nome = trim($nome);
        if ($nome === '') {
            return [];
        }

        $limite = max(1, min(20, $limite));
        $params = ['busca' => '%' . $nome . '%'];

        if ($this->suportaLojaId()) {
            $sql = "SELECT *
                    FROM public.obreiros
                    WHERE ativo = true
                      AND loja_id = :loja_id
                      AND (
                            nome ILIKE :busca
                            OR COALESCE(nome_historico, '') ILIKE :busca
                          )";
            $params['loja_id'] = $this->obterLojaAtualId();
        } else {
            $sql = "SELECT *
                    FROM public.obreiros
                    WHERE ativo = true
                      AND (
                            nome ILIKE :busca
                            OR COALESCE(nome_historico, '') ILIKE :busca
                          )";
        }

        $this->aplicarExclusaoManualEmSql($sql, $params);
        $this->aplicarExclusaoSystemAdminsEmSql($sql, $params);
        $this->aplicarSanitizacaoObreirosEmSql($sql, $params);

        $sql .= " ORDER BY nome ASC LIMIT " . (int) $limite;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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

        $this->aplicarExclusaoManualEmSql($sql, $params);
        $situacaoFiltroInformado = trim((string) ($filtros['situacao'] ?? ''));

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

        $situacao = $situacaoFiltroInformado;
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

        $this->aplicarExclusaoSystemAdminsEmSql($sql, $params);
        $this->aplicarSanitizacaoObreirosEmSql($sql, $params);

        $cargoCodigo = trim((string) ($filtros['cargo_codigo'] ?? ''));
        if ($cargoCodigo !== '') {
            if ($this->suportaLojaId()) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM atribuicoes_cargo ac
                    JOIN cargos c
                      ON c.id = ac.cargo_id
                     AND c.loja_id = :loja_id
                    LEFT JOIN gestoes g
                      ON g.id = ac.gestao_id
                     AND g.loja_id = :loja_id
                    WHERE ac.obreiro_id = obreiros.id
                      AND ac.loja_id = :loja_id
                      AND ac.fim_em IS NULL
                      AND c.ativo = TRUE
                      AND c.codigo = :cargo_codigo
                      AND (g.id IS NULL OR g.status = 'aberta')
                )";
            } else {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM atribuicoes_cargo ac
                    JOIN cargos c ON c.id = ac.cargo_id
                    LEFT JOIN gestoes g ON g.id = ac.gestao_id
                    WHERE ac.obreiro_id = obreiros.id
                      AND ac.fim_em IS NULL
                      AND c.ativo = TRUE
                      AND c.codigo = :cargo_codigo
                      AND (g.id IS NULL OR g.status = 'aberta')
                )";
            }
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
            FROM public.obreiros
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
            $params = ['loja_id' => $this->obterLojaAtualId()];
        } else {
            $params = [];
        }

        $this->aplicarExclusaoManualEmSql($sql, $params);
        $this->aplicarExclusaoSystemAdminsEmSql($sql, $params);
        $this->aplicarSanitizacaoObreirosEmSql($sql, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorAniversario($data): array
    {
        $params = [];
        if ($this->suportaLojaId()) {
            $sql = "SELECT * FROM public.obreiros WHERE loja_id = :loja_id AND TO_CHAR(data_nascimento_civil, 'MM-DD') = :data";
            $params['loja_id'] = $this->obterLojaAtualId();
        } else {
            $sql = "SELECT * FROM public.obreiros WHERE TO_CHAR(data_nascimento_civil, 'MM-DD') = :data";
        }

        $params['data'] = $data;
        $this->aplicarExclusaoManualEmSql($sql, $params);
        $this->aplicarExclusaoSystemAdminsEmSql($sql, $params);
        $this->aplicarSanitizacaoObreirosEmSql($sql, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorDatasMaconicas($data): array
    {
        $params = [];
        if ($this->suportaLojaId()) {
            $sql = "SELECT nome, 'Iniciacao' as tipo, data_iniciacao as data FROM public.obreiros WHERE loja_id = :loja_id AND TO_CHAR(data_iniciacao, 'MM-DD') = :data";
            $params['loja_id'] = $this->obterLojaAtualId();
        } else {
            $sql = "SELECT nome, 'Iniciacao' as tipo, data_iniciacao as data FROM public.obreiros WHERE TO_CHAR(data_iniciacao, 'MM-DD') = :data";
        }

        $params['data'] = $data;
        $this->aplicarExclusaoManualEmSql($sql, $params);
        $this->aplicarExclusaoSystemAdminsEmSql($sql, $params);
        $this->aplicarSanitizacaoObreirosEmSql($sql, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $id)) {
            return null;
        }

        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare("SELECT * FROM public.obreiros WHERE id = :id AND loja_id = :loja_id LIMIT 1");
            $stmt->execute([
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM public.obreiros WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $this->hidratarCargosAtivos($result) : null;
    }

    public function update(array $data): bool
    {
        $obreiroId = trim((string) ($data['id'] ?? ''));
        if ($obreiroId === '') {
            return false;
        }

        $cim = trim((string) ($data['cim'] ?? ''));
        if ($cim !== '' && !$this->cimEstaDisponivelParaId($cim, $obreiroId)) {
            return false;
        }

        $acessoStatus = strtolower(trim((string) ($data['acesso_status'] ?? '')));
        if (!in_array($acessoStatus, ['pendente', 'ativo', 'inativo'], true)) {
            $acessoStatus = '';
        }

        $sql = "UPDATE public.obreiros SET
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
            potencia_nome = :potencia_nome,
            potencia_sigla = :potencia_sigla,
            numero_quadro = :numero_quadro,
            potencia_login = :potencia_login,
            acesso_potencia_liberado = :acesso_potencia_liberado,
            acesso_potencia_liberado_em = :acesso_potencia_liberado_em,
            observacao_secretaria = :observacao_secretaria,
            acesso_status = :acesso_status,
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
        if ($acessoStatus !== '' && $acessoStatus !== 'ativo') {
            $ativo = 'false';
        }

        $params = [
            'id' => $obreiroId,
            'cim' => $cim !== '' ? $cim : null,
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
            'potencia_nome' => $data['potencia_nome'] ?? null,
            'potencia_sigla' => $data['potencia_sigla'] ?? null,
            'numero_quadro' => $data['numero_quadro'] ?? null,
            'potencia_login' => $data['potencia_login'] ?? null,
            'acesso_potencia_liberado' => !empty($data['acesso_potencia_liberado']) ? 'true' : 'false',
            'acesso_potencia_liberado_em' => !empty($data['acesso_potencia_liberado'])
                ? (!empty($data['acesso_potencia_liberado_em']) ? $data['acesso_potencia_liberado_em'] : date('c'))
                : null,
            'observacao_secretaria' => $data['observacao_secretaria'] ?? null,
            'acesso_status' => $acessoStatus !== '' ? $acessoStatus : null,
            'ativo' => $ativo,
        ];

        if ($this->suportaLojaId()) {
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        return $stmt->execute($params);
    }

    public function atualizarConfiguracaoFinanceira(
        string $id,
        ?float $joiaValor,
        string $joiaFormato,
        ?float $bibliotecaValor,
        string $bibliotecaFormato,
        ?string $dataElevacao = null,
        ?string $dataExaltacao = null,
        bool $joiaAtiva = false,
        ?string $joiaTipo = null,
        ?string $dataIniciacao = null,
        ?int $bibliotecaMes = null,
        ?float $mensalidadeValor = null,
        string $mensalidadeFormato = 'mensal'
    ): bool {
        if (!preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $id)) {
            return false;
        }

        $sql = "UPDATE public.obreiros SET
            financeiro_joia_valor = :joia_valor,
            financeiro_joia_formato = :joia_formato,
            financeiro_joia_ativa = :joia_ativa,
            financeiro_joia_tipo = :joia_tipo,
            financeiro_biblioteca_valor = :biblioteca_valor,
            financeiro_biblioteca_formato = :biblioteca_formato,
            financeiro_biblioteca_mes = :biblioteca_mes,
            financeiro_mensalidade_valor = :mensalidade_valor,
            financeiro_mensalidade_formato = :mensalidade_formato,
            data_iniciacao = :data_iniciacao,
            data_elevacao = :data_elevacao,
            data_exaltacao = :data_exaltacao,
            updated_at = NOW()
            WHERE id = :id";
        
        if ($this->suportaLojaId()) {
            $sql .= " AND loja_id = :loja_id";
        }

        $params = [
            'id' => $id,
            'joia_valor' => $joiaValor,
            'joia_formato' => $joiaFormato,
            'joia_ativa' => $joiaAtiva ? 'true' : 'false',
            'joia_tipo' => !empty($joiaTipo) && $joiaTipo !== 'nenhuma' ? $joiaTipo : null,
            'biblioteca_valor' => $bibliotecaValor,
            'biblioteca_formato' => $bibliotecaFormato,
            'biblioteca_mes' => $bibliotecaMes,
            'mensalidade_valor' => $mensalidadeValor,
            'mensalidade_formato' => $mensalidadeFormato,
            'data_iniciacao' => !empty($dataIniciacao) ? $dataIniciacao : null,
            'data_elevacao' => !empty($dataElevacao) ? $dataElevacao : null,
            'data_exaltacao' => !empty($dataExaltacao) ? $dataExaltacao : null,
        ];

        if ($this->suportaLojaId()) {
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateSelf(string $id, array $data): bool
    {
        if (!$this->isUuid($id)) {
            return false;
        }

        $sql = "UPDATE public.obreiros SET
            nome = :nome,
            nome_historico = :nome_historico,
            data_nascimento_civil = :data_nascimento_civil,
            estado_civil = :estado_civil,
            telefone = :telefone,
            email = :email,
            profissao = :profissao,
            updated_at = NOW()
            WHERE id = :id";

        $params = [
            'id' => $id,
            'nome' => trim((string) ($data['nome'] ?? $data['nome_completo'] ?? '')),
            'nome_historico' => trim((string) ($data['nome_historico'] ?? '')),
            'data_nascimento_civil' => $this->normalizarData($data['data_nascimento_civil'] ?? null),
            'estado_civil' => $this->normalizarValorEnum($data['estado_civil'] ?? null, self::ESTADOS_CIVIS),
            'telefone' => trim((string) ($data['telefone'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'profissao' => trim((string) ($data['profissao'] ?? '')),
        ];

        if ($params['nome'] === '') {
            return false;
        }

        if ($this->suportaLojaId()) {
            $sql .= " AND loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    private function cimEstaDisponivelParaId(string $cim, string $id): bool
    {
        $cim = trim($cim);
        if ($cim === '' || $id === '') {
            return true;
        }

        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare(
                "SELECT 1
                 FROM public.obreiros
                 WHERE cim = :cim
                   AND id <> :id
                   AND loja_id = :loja_id
                 LIMIT 1"
            );
            $stmt->execute([
                'cim' => $cim,
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT 1
                 FROM public.obreiros
                 WHERE cim = :cim
                   AND id <> :id
                 LIMIT 1"
            );
            $stmt->execute([
                'cim' => $cim,
                'id' => $id,
            ]);
        }

        return $stmt->fetchColumn() === false;
    }

    public function inativarPorId(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare(
                "UPDATE public.obreiros
                 SET ativo = false,
                     situacao_quadro = 'inativo',
                     acesso_status = 'inativo',
                     telegram_id = NULL,
                     updated_at = NOW()
                 WHERE id = :id
                   AND loja_id = :loja_id"
            );
            $stmt->execute([
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE public.obreiros
                 SET ativo = false,
                     situacao_quadro = 'inativo',
                     acesso_status = 'inativo',
                     telegram_id = NULL,
                     updated_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute(['id' => $id]);
        }

        return $stmt->rowCount() > 0;
    }

    public function excluirPorId(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        if ($this->suportaColuna('excluir_em_listas')) {
            if ($this->suportaLojaId()) {
                $stmt = $this->db->prepare(
                    "UPDATE public.obreiros
                     SET excluir_em_listas = true,
                         ativo = false,
                         situacao_quadro = 'inativo',
                         acesso_status = 'inativo',
                         telegram_id = NULL,
                         updated_at = NOW()
                     WHERE id = :id
                       AND loja_id = :loja_id"
                );
                $stmt->execute([
                    'id' => $id,
                    'loja_id' => $this->obterLojaAtualId(),
                ]);
            } else {
                $stmt = $this->db->prepare(
                    "UPDATE public.obreiros
                     SET excluir_em_listas = true,
                         ativo = false,
                         situacao_quadro = 'inativo',
                         acesso_status = 'inativo',
                         telegram_id = NULL,
                         updated_at = NOW()
                     WHERE id = :id"
                );
                $stmt->execute(['id' => $id]);
            }

            return $stmt->rowCount() > 0;
        }

        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare("DELETE FROM public.obreiros WHERE id = :id AND loja_id = :loja_id");
            $stmt->execute([
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        } else {
            $stmt = $this->db->prepare("DELETE FROM public.obreiros WHERE id = :id");
            $stmt->execute(['id' => $id]);
        }

        return $stmt->rowCount() > 0;
    }

    public function autenticar(string $matricula, string $senha): ?array
    {
        $usuario = $this->findByCimAnyStatus($matricula);

        if (!$usuario) {
            return null;
        }

        $status = $this->resolverAcessoStatus($usuario);
        if (!($usuario['ativo'] ?? false) || $status !== 'ativo') {
            return null;
        }

        // Se o obreiro já possui senha cadastrada
        if (!empty($usuario['senha_hash'])) {
            if (password_verify($senha, $usuario['senha_hash'])) {
                return $usuario;
            }
            return null;
        }

        // Se o obreiro não tem senha cadastrada (primeiro acesso)
        // Verificamos se ele usou a senha provisória padrão
        $senhaProvisoria = trim((string) ($_ENV['APP_DEFAULT_PROVISORY_PASSWORD'] ?? getenv('APP_DEFAULT_PROVISORY_PASSWORD') ?: ''));
        if ($senhaProvisoria !== '' && $senha === $senhaProvisoria) {
            $usuario['primeiro_acesso_provisorio'] = true;
            return $usuario;
        }

        return null;
    }

    public function atualizarSenha(string $id, string $senha): bool
    {
        $id = trim($id);
        if ($id === '' || !$this->isUuid($id)) {
            return false;
        }

        $params = [
            'id' => $id,
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        ];

        $sql = "UPDATE obreiros SET senha_hash = :senha_hash";

        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $sql .= " WHERE id = :id AND loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        } else {
            $sql .= " WHERE id = :id";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function resolverAcessoStatus(array $obreiro): string
    {
        $status = strtolower(trim((string) ($obreiro['acesso_status'] ?? '')));
        if (in_array($status, ['pendente', 'ativo', 'inativo'], true)) {
            return $status;
        }

        return !empty($obreiro['ativo']) ? 'ativo' : 'inativo';
    }

    public function solicitarAcessoPorCim(string $cim, string $senha, ?int $telegramId = null): array
    {
        $obreiro = $this->findByCimAnyStatus($cim);
        if (!$obreiro) {
            return ['ok' => false, 'reason' => 'not_found'];
        }

        $params = [
            'id' => (string) ($obreiro['id'] ?? ''),
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'acesso_status' => 'pendente',
        ];

        $sql = "UPDATE obreiros
                SET senha_hash = :senha_hash,
                    acesso_status = :acesso_status,
                    ativo = FALSE";

        if ($telegramId !== null && $telegramId > 0) {
            $sql .= ", telegram_id = :telegram_id";
            $params['telegram_id'] = $telegramId;
        }

        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $sql .= " WHERE id = :id AND loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        } else {
            $sql .= " WHERE id = :id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['ok' => true, 'reason' => 'pending'];
    }

    public function listarPendentesAcesso(): array
    {
        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM obreiros
                 WHERE acesso_status = 'pendente'
                   AND loja_id = :loja_id
                 ORDER BY nome ASC"
            );
            $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);
        } else {
            $stmt = $this->db->query(
                "SELECT *
                 FROM obreiros
                 WHERE acesso_status = 'pendente'
                 ORDER BY nome ASC"
            );
        }

        return $this->hidratarListaCargosAtivos($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function atualizarAcessoStatus(string $id, string $status): bool
    {
        $status = strtolower(trim($status));
        if (!in_array($status, ['pendente', 'ativo', 'inativo'], true)) {
            return false;
        }

        $ativo = $status === 'ativo';
        if ($this->suportaLojaId() && $this->deveAplicarEscopoTenantNaIdentidade()) {
            $stmt = $this->db->prepare(
                "UPDATE obreiros
                 SET acesso_status = :status,
                     ativo = :ativo
                 WHERE id = :id
                   AND loja_id = :loja_id"
            );
            return $stmt->execute([
                'status' => $status,
                'ativo' => $ativo,
                'id' => $id,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
        }

        $stmt = $this->db->prepare(
            "UPDATE obreiros
             SET acesso_status = :status,
                 ativo = :ativo
             WHERE id = :id"
        );
        return $stmt->execute([
            'status' => $status,
            'ativo' => $ativo,
            'id' => $id,
        ]);
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

        // System admin (fora do RBAC da loja): flag tecnica, nao e cargo oficial.
        $telegramId = isset($obreiro['telegram_id']) ? (int) $obreiro['telegram_id'] : null;
        $obreiro['is_system_admin'] = (bool) ($obreiro['is_system_admin'] ?? false)
            || (bool) ($telegramId && $this->isSystemAdminTelegramId($telegramId));

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

            $telegramId = isset($obreiro['telegram_id']) ? (int) $obreiro['telegram_id'] : null;
            $obreiro['is_system_admin'] = (bool) ($obreiro['is_system_admin'] ?? false)
                || (bool) ($telegramId && $this->isSystemAdminTelegramId($telegramId));
        }
        unset($obreiro);

        return $obreiros;
    }

    private function isSystemAdminTelegramId(int $telegramId): bool
    {
        $raw = trim((string) (getenv('SYSTEM_ADMIN_TELEGRAM_IDS') ?: ''));
        if ($raw === '') {
            return false;
        }

        $ids = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($ids as $id) {
            if ((int) trim((string) $id) === $telegramId) {
                return true;
            }
        }

        return false;
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
                FROM atribuicoes_cargo ac
                JOIN cargos c ON c.id = ac.cargo_id
                WHERE ac.fim_em IS NULL
                  AND c.ativo = TRUE
                  AND ac.obreiro_id IN ($placeholders)";

        $params = $ids;
        if ($this->suportaLojaId()) {
            $sql .= " AND ac.loja_id = ?
                      AND c.loja_id = ?";
            $lojaId = $this->obterLojaAtualId();
            $params[] = $lojaId;
            $params[] = $lojaId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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

    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            trim($value)
        );
    }
}
