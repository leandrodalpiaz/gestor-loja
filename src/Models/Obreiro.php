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
     * Busca um obreiro pelo ID do Telegram
     */
    public function findByTelegramId(int $telegramId): ?array
    {
        // Garante que o obreiro encontrado não apenas existe, mas está ATIVO no quadro da loja
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE telegram_id = :telegram_id AND ativo = true LIMIT 1");
        $stmt->execute(['telegram_id' => $telegramId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca todos os obreiros ativos
     */
    public function getAllAtivos(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE ativo = true ORDER BY nome ASC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Busca efemérides do dia (aniversário civil ou maçônico)
     */
    public function getEfemeridesDoDia(): array
    {
        // PostgreSQL extract function para pegar dia e mês atual
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

    public function buscarPorAniversario($data) {
        $sql = "SELECT * FROM obreiros WHERE TO_CHAR(data_nascimento_civil, 'MM-DD') = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorDatasMaconicas($data) {
        $sql = "
            SELECT nome, 'Iniciação' as tipo, data_iniciacao as data FROM obreiros WHERE TO_CHAR(data_iniciacao, 'MM-DD') = ?
            UNION
            SELECT nome, 'Elevação' as tipo, data_elevacao as data FROM obreiros WHERE TO_CHAR(data_elevacao, 'MM-DD') = ?
            UNION
            SELECT nome, 'Exaltação' as tipo, data_exaltacao as data FROM obreiros WHERE TO_CHAR(data_exaltacao, 'MM-DD') = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data, $data, $data]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo obreiro no banco de dados
     */
    public function create(array $data): bool
    {
        // Renomeando de nome_completo para nome provisoriamente pra garantir compatibilidade com o db inicial
        $sql = "INSERT INTO obreiros (
            cim, nome, nome_historico, cpf, 
            data_nascimento_civil, data_iniciacao, telefone, 
            email, profissao, loja_origem, grau, cargo, ativo
        ) VALUES (
            :cim, :nome, :nome_historico, :cpf,
            :data_nascimento_civil, :data_iniciacao, :telefone,
            :email, :profissao, :loja_origem, :grau, :cargo, true
        )";

        $stmt = $this->db->prepare($sql);

        // Trata campos de data vazios para null 
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
        ]);
    }

    /**
     * Busca um obreiro pelo ID do banco (PK)
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Atualiza os dados do obreiro no banco
     */
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
            telefone = :telefone,
            email = :email,
            ativo = :ativo
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $nascimento = !empty($data['data_nascimento_civil']) ? $data['data_nascimento_civil'] : null;
        $iniciacao = !empty($data['data_iniciacao']) ? $data['data_iniciacao'] : null;

        // Converte o valor do checkbox de string para boolean do Postgres
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
            'telefone' => $data['telefone'],
            'email' => $data['email'],
            'ativo' => $ativo
        ]);
    }

    /**
     * Autenticação para o painel administrativo via matrícula (cim) e senha
     */
    public function autenticar(string $matricula, string $senha): ?array
    {
        // Certifica de puxar apenas se o membro for ativo para login
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE cim = :matricula AND ativo = true LIMIT 1");
        $stmt->execute(['matricula' => $matricula]);
        $usuario = $stmt->fetch();

        // O hash da senha deve estar salvo na coluna "senha_hash" no banco.
        if ($usuario && !empty($usuario['senha_hash']) && password_verify($senha, $usuario['senha_hash'])) {
            return $usuario;
        }

        return null;
    }
}