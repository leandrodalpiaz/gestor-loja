<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class ConfiguracaoLoja
{
    use ResolvesStoreTenant;

    private PDO $db;
    private AuditoriaAdministrativa $auditoria;
    private ?int $lojaId = null;
    private ?bool $suportaLojaId = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->auditoria = new AuditoriaAdministrativa();
        $this->lojaId = $this->resolveCurrentStoreId($this->db);
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
               AND table_name = 'configuracoes_loja'
               AND column_name = 'loja_id'
             LIMIT 1"
        );
        $stmt->execute();

        $this->suportaLojaId = (bool) $stmt->fetchColumn();

        return $this->suportaLojaId;
    }

    public function obter(): array
    {
        if ($this->suportaLojaId()) {
            $stmt = $this->db->prepare("SELECT * FROM configuracoes_loja WHERE loja_id = :loja_id LIMIT 1");
            $stmt->execute(['loja_id' => $this->obterLojaIdAtual()]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($config !== false) {
                return $config;
            }
        }

        $stmtLegado = $this->db->query("SELECT * FROM configuracoes_loja WHERE id = 1 LIMIT 1");
        $configLegado = $stmtLegado->fetch(PDO::FETCH_ASSOC);
        if ($configLegado !== false) {
            $configLegado['loja_id'] = $configLegado['loja_id'] ?? $this->obterLojaIdAtual();
            return $configLegado;
        }

        return [
            'id' => 1,
            'loja_id' => $this->obterLojaIdAtual(),
            'nome_loja' => '',
            'numero_loja' => '',
            'titulo_tratamento' => '',
            'cidade' => '',
            'uf' => '',
            'oriente' => '',
            'potencia_nome' => '',
            'potencia_sigla' => '',
            'grande_secretaria' => '',
            'rito' => '',
            'data_fundacao' => '',
            'decreto_fundacao' => '',
            'data_reconhecimento' => '',
            'data_instalacao' => '',
            'data_entrega_carta_constitutiva' => '',
            'tipo_loja' => '',
            'regiao_simposios' => '',
            'obediencia_templo' => '',
            'templo_endereco' => '',
            'proprietario_locatario' => '',
            'nome_templo' => '',
            'data_sagracao_templo' => '',
            'trabalha_palacio_maconico' => false,
            'dia_semana_reuniao' => '',
            'horario_reuniao' => '',
            'periodicidade_reuniao' => '',
            'cnpj' => '',
            'email_oficial' => '',
            'telefone_oficial' => '',
            'endereco' => '',
            'cep' => '',
            'mensalidade_valor_padrao' => 150.00,
            'mensalidade_dia_sugerido' => 10,
            'mensalidade_regra_atraso' => 'primeiro_dia_util_mes_seguinte',
            'contribuicao_biblioteca_valor_padrao' => 44.00,
            'contribuicao_biblioteca_quantidade_mensal' => 2,
            'pix_chave_tipo' => 'CNPJ',
            'pix_chave_valor' => '31.274.071/0001-06',
            'pix_beneficiario' => 'Renascença nº 270',
            'observacao_relatorios' => '',
            'historia_loja' => '',
            'cor_primaria_light' => '#1E3A8A',
            'cor_primaria_dark' => '#0F172A',
            'logo_path' => null,
        ];
    }

    public function salvar(array $dados): bool
    {
        $anterior = $this->obter();
        if ($this->suportaLojaId()) {
            $sql = "INSERT INTO configuracoes_loja (
                    id, loja_id, nome_loja, numero_loja, titulo_tratamento, cidade, uf, oriente,
                    potencia_nome, potencia_sigla, grande_secretaria, rito,
                    data_fundacao, decreto_fundacao, data_reconhecimento, data_instalacao,
                    data_entrega_carta_constitutiva, tipo_loja, regiao_simposios,
                    obediencia_templo, templo_endereco, proprietario_locatario, nome_templo,
                    data_sagracao_templo, trabalha_palacio_maconico, dia_semana_reuniao,
                    horario_reuniao, periodicidade_reuniao, cnpj, email_oficial,
                    telefone_oficial, endereco, cep, mensalidade_valor_padrao,
                    mensalidade_dia_sugerido, mensalidade_regra_atraso,
                    contribuicao_biblioteca_valor_padrao, contribuicao_biblioteca_quantidade_mensal,
                    pix_chave_tipo, pix_chave_valor, pix_beneficiario,
                    observacao_relatorios, historia_loja,
                    cor_primaria_light, cor_primaria_dark, logo_path,
                    created_at, updated_at
                ) VALUES (
                    1, :loja_id, :nome_loja, :numero_loja, :titulo_tratamento, :cidade, :uf, :oriente,
                    :potencia_nome, :potencia_sigla, :grande_secretaria, :rito,
                    :data_fundacao, :decreto_fundacao, :data_reconhecimento, :data_instalacao,
                    :data_entrega_carta_constitutiva, :tipo_loja, :regiao_simposios,
                    :obediencia_templo, :templo_endereco, :proprietario_locatario, :nome_templo,
                    :data_sagracao_templo, :trabalha_palacio_maconico, :dia_semana_reuniao,
                    :horario_reuniao, :periodicidade_reuniao, :cnpj, :email_oficial,
                    :telefone_oficial, :endereco, :cep, :mensalidade_valor_padrao,
                    :mensalidade_dia_sugerido, :mensalidade_regra_atraso,
                    :contribuicao_biblioteca_valor_padrao, :contribuicao_biblioteca_quantidade_mensal,
                    :pix_chave_tipo, :pix_chave_valor, :pix_beneficiario,
                    :observacao_relatorios, :historia_loja,
                    :cor_primaria_light, :cor_primaria_dark, :logo_path,
                    NOW(), NOW()
                )
                ON CONFLICT (loja_id) DO UPDATE SET
                    id = EXCLUDED.id,
                    nome_loja = EXCLUDED.nome_loja,
                    numero_loja = EXCLUDED.numero_loja,
                    titulo_tratamento = EXCLUDED.titulo_tratamento,
                    cidade = EXCLUDED.cidade,
                    uf = EXCLUDED.uf,
                    oriente = EXCLUDED.oriente,
                    potencia_nome = EXCLUDED.potencia_nome,
                    potencia_sigla = EXCLUDED.potencia_sigla,
                    grande_secretaria = EXCLUDED.grande_secretaria,
                    rito = EXCLUDED.rito,
                    data_fundacao = EXCLUDED.data_fundacao,
                    decreto_fundacao = EXCLUDED.decreto_fundacao,
                    data_reconhecimento = EXCLUDED.data_reconhecimento,
                    data_instalacao = EXCLUDED.data_instalacao,
                    data_entrega_carta_constitutiva = EXCLUDED.data_entrega_carta_constitutiva,
                    tipo_loja = EXCLUDED.tipo_loja,
                    regiao_simposios = EXCLUDED.regiao_simposios,
                    obediencia_templo = EXCLUDED.obediencia_templo,
                    templo_endereco = EXCLUDED.templo_endereco,
                    proprietario_locatario = EXCLUDED.proprietario_locatario,
                    nome_templo = EXCLUDED.nome_templo,
                    data_sagracao_templo = EXCLUDED.data_sagracao_templo,
                    trabalha_palacio_maconico = EXCLUDED.trabalha_palacio_maconico,
                    dia_semana_reuniao = EXCLUDED.dia_semana_reuniao,
                    horario_reuniao = EXCLUDED.horario_reuniao,
                    periodicidade_reuniao = EXCLUDED.periodicidade_reuniao,
                    cnpj = EXCLUDED.cnpj,
                    email_oficial = EXCLUDED.email_oficial,
                    telefone_oficial = EXCLUDED.telefone_oficial,
                    endereco = EXCLUDED.endereco,
                    cep = EXCLUDED.cep,
                    mensalidade_valor_padrao = EXCLUDED.mensalidade_valor_padrao,
                    mensalidade_dia_sugerido = EXCLUDED.mensalidade_dia_sugerido,
                    mensalidade_regra_atraso = EXCLUDED.mensalidade_regra_atraso,
                    contribuicao_biblioteca_valor_padrao = EXCLUDED.contribuicao_biblioteca_valor_padrao,
                    contribuicao_biblioteca_quantidade_mensal = EXCLUDED.contribuicao_biblioteca_quantidade_mensal,
                    pix_chave_tipo = EXCLUDED.pix_chave_tipo,
                    pix_chave_valor = EXCLUDED.pix_chave_valor,
                    pix_beneficiario = EXCLUDED.pix_beneficiario,
                    observacao_relatorios = EXCLUDED.observacao_relatorios,
                    historia_loja = EXCLUDED.historia_loja,
                    cor_primaria_light = EXCLUDED.cor_primaria_light,
                    cor_primaria_dark = EXCLUDED.cor_primaria_dark,
                    logo_path = EXCLUDED.logo_path,
                    updated_at = NOW()";
        } else {
            $sql = "INSERT INTO configuracoes_loja (
                    id, nome_loja, numero_loja, titulo_tratamento, cidade, uf, oriente,
                    potencia_nome, potencia_sigla, grande_secretaria, rito,
                    data_fundacao, decreto_fundacao, data_reconhecimento, data_instalacao,
                    data_entrega_carta_constitutiva, tipo_loja, regiao_simposios,
                    obediencia_templo, templo_endereco, proprietario_locatario, nome_templo,
                    data_sagracao_templo, trabalha_palacio_maconico, dia_semana_reuniao,
                    horario_reuniao, periodicidade_reuniao, cnpj, email_oficial,
                    telefone_oficial, endereco, cep, mensalidade_valor_padrao,
                    mensalidade_dia_sugerido, mensalidade_regra_atraso,
                    contribuicao_biblioteca_valor_padrao, contribuicao_biblioteca_quantidade_mensal,
                    pix_chave_tipo, pix_chave_valor, pix_beneficiario,
                    observacao_relatorios, historia_loja,
                    cor_primaria_light, cor_primaria_dark, logo_path,
                    created_at, updated_at
                ) VALUES (
                    1, :nome_loja, :numero_loja, :titulo_tratamento, :cidade, :uf, :oriente,
                    :potencia_nome, :potencia_sigla, :grande_secretaria, :rito,
                    :data_fundacao, :decreto_fundacao, :data_reconhecimento, :data_instalacao,
                    :data_entrega_carta_constitutiva, :tipo_loja, :regiao_simposios,
                    :obediencia_templo, :templo_endereco, :proprietario_locatario, :nome_templo,
                    :data_sagracao_templo, :trabalha_palacio_maconico, :dia_semana_reuniao,
                    :horario_reuniao, :periodicidade_reuniao, :cnpj, :email_oficial,
                    :telefone_oficial, :endereco, :cep, :mensalidade_valor_padrao,
                    :mensalidade_dia_sugerido, :mensalidade_regra_atraso,
                    :contribuicao_biblioteca_valor_padrao, :contribuicao_biblioteca_quantidade_mensal,
                    :pix_chave_tipo, :pix_chave_valor, :pix_beneficiario,
                    :observacao_relatorios, :historia_loja,
                    :cor_primaria_light, :cor_primaria_dark, :logo_path,
                    NOW(), NOW()
                )
                ON CONFLICT (id) DO UPDATE SET
                    nome_loja = EXCLUDED.nome_loja,
                    numero_loja = EXCLUDED.numero_loja,
                    titulo_tratamento = EXCLUDED.titulo_tratamento,
                    cidade = EXCLUDED.cidade,
                    uf = EXCLUDED.uf,
                    oriente = EXCLUDED.oriente,
                    potencia_nome = EXCLUDED.potencia_nome,
                    potencia_sigla = EXCLUDED.potencia_sigla,
                    grande_secretaria = EXCLUDED.grande_secretaria,
                    rito = EXCLUDED.rito,
                    data_fundacao = EXCLUDED.data_fundacao,
                    decreto_fundacao = EXCLUDED.decreto_fundacao,
                    data_reconhecimento = EXCLUDED.data_reconhecimento,
                    data_instalacao = EXCLUDED.data_instalacao,
                    data_entrega_carta_constitutiva = EXCLUDED.data_entrega_carta_constitutiva,
                    tipo_loja = EXCLUDED.tipo_loja,
                    regiao_simposios = EXCLUDED.regiao_simposios,
                    obediencia_templo = EXCLUDED.obediencia_templo,
                    templo_endereco = EXCLUDED.templo_endereco,
                    proprietario_locatario = EXCLUDED.proprietario_locatario,
                    nome_templo = EXCLUDED.nome_templo,
                    data_sagracao_templo = EXCLUDED.data_sagracao_templo,
                    trabalha_palacio_maconico = EXCLUDED.trabalha_palacio_maconico,
                    dia_semana_reuniao = EXCLUDED.dia_semana_reuniao,
                    horario_reuniao = EXCLUDED.horario_reuniao,
                    periodicidade_reuniao = EXCLUDED.periodicidade_reuniao,
                    cnpj = EXCLUDED.cnpj,
                    email_oficial = EXCLUDED.email_oficial,
                    telefone_oficial = EXCLUDED.telefone_oficial,
                    endereco = EXCLUDED.endereco,
                    cep = EXCLUDED.cep,
                    mensalidade_valor_padrao = EXCLUDED.mensalidade_valor_padrao,
                    mensalidade_dia_sugerido = EXCLUDED.mensalidade_dia_sugerido,
                    mensalidade_regra_atraso = EXCLUDED.mensalidade_regra_atraso,
                    contribuicao_biblioteca_valor_padrao = EXCLUDED.contribuicao_biblioteca_valor_padrao,
                    contribuicao_biblioteca_quantidade_mensal = EXCLUDED.contribuicao_biblioteca_quantidade_mensal,
                    pix_chave_tipo = EXCLUDED.pix_chave_tipo,
                    pix_chave_valor = EXCLUDED.pix_chave_valor,
                    pix_beneficiario = EXCLUDED.pix_beneficiario,
                    observacao_relatorios = EXCLUDED.observacao_relatorios,
                    historia_loja = EXCLUDED.historia_loja,
                    cor_primaria_light = EXCLUDED.cor_primaria_light,
                    cor_primaria_dark = EXCLUDED.cor_primaria_dark,
                    logo_path = EXCLUDED.logo_path,
                    updated_at = NOW()";
        }

        $stmt = $this->db->prepare($sql);

        $params = [
            'nome_loja' => $this->texto($dados['nome_loja'] ?? null),
            'numero_loja' => $this->texto($dados['numero_loja'] ?? null),
            'titulo_tratamento' => $this->texto($dados['titulo_tratamento'] ?? null),
            'cidade' => $this->texto($dados['cidade'] ?? null),
            'uf' => $this->uf($dados['uf'] ?? null),
            'oriente' => $this->texto($dados['oriente'] ?? null),
            'potencia_nome' => $this->texto($dados['potencia_nome'] ?? null),
            'potencia_sigla' => $this->texto($dados['potencia_sigla'] ?? null),
            'grande_secretaria' => $this->texto($dados['grande_secretaria'] ?? null),
            'rito' => $this->texto($dados['rito'] ?? null),
            'data_fundacao' => $this->data($dados['data_fundacao'] ?? null),
            'decreto_fundacao' => $this->texto($dados['decreto_fundacao'] ?? null),
            'data_reconhecimento' => $this->data($dados['data_reconhecimento'] ?? null),
            'data_instalacao' => $this->data($dados['data_instalacao'] ?? null),
            'data_entrega_carta_constitutiva' => $this->data($dados['data_entrega_carta_constitutiva'] ?? null),
            'tipo_loja' => $this->texto($dados['tipo_loja'] ?? null),
            'regiao_simposios' => $this->texto($dados['regiao_simposios'] ?? null),
            'obediencia_templo' => $this->texto($dados['obediencia_templo'] ?? null),
            'templo_endereco' => $this->texto($dados['templo_endereco'] ?? null),
            'proprietario_locatario' => $this->texto($dados['proprietario_locatario'] ?? null),
            'nome_templo' => $this->texto($dados['nome_templo'] ?? null),
            'data_sagracao_templo' => $this->data($dados['data_sagracao_templo'] ?? null),
            'trabalha_palacio_maconico' => !empty($dados['trabalha_palacio_maconico']) ? 'true' : 'false',
            'dia_semana_reuniao' => $this->texto($dados['dia_semana_reuniao'] ?? null),
            'horario_reuniao' => $this->texto($dados['horario_reuniao'] ?? null),
            'periodicidade_reuniao' => $this->texto($dados['periodicidade_reuniao'] ?? null),
            'cnpj' => $this->texto($dados['cnpj'] ?? null),
            'email_oficial' => $this->texto($dados['email_oficial'] ?? null),
            'telefone_oficial' => $this->texto($dados['telefone_oficial'] ?? null),
            'endereco' => $this->texto($dados['endereco'] ?? null),
            'cep' => $this->texto($dados['cep'] ?? null),
            'mensalidade_valor_padrao' => $this->decimal($dados['mensalidade_valor_padrao'] ?? 150),
            'mensalidade_dia_sugerido' => $this->inteiro($dados['mensalidade_dia_sugerido'] ?? 10, 10),
            'mensalidade_regra_atraso' => $this->texto($dados['mensalidade_regra_atraso'] ?? 'primeiro_dia_util_mes_seguinte'),
            'contribuicao_biblioteca_valor_padrao' => $this->decimal($dados['contribuicao_biblioteca_valor_padrao'] ?? 44),
            'contribuicao_biblioteca_quantidade_mensal' => $this->inteiro($dados['contribuicao_biblioteca_quantidade_mensal'] ?? 2, 2),
            'pix_chave_tipo' => $this->texto($dados['pix_chave_tipo'] ?? 'CNPJ'),
            'pix_chave_valor' => $this->texto($dados['pix_chave_valor'] ?? null),
            'pix_beneficiario' => $this->texto($dados['pix_beneficiario'] ?? null),
            'observacao_relatorios' => $this->texto($dados['observacao_relatorios'] ?? null),
            'historia_loja' => $this->texto($dados['historia_loja'] ?? null),
            'cor_primaria_light' => $this->texto($dados['cor_primaria_light'] ?? '#1E3A8A'),
            'cor_primaria_dark' => $this->texto($dados['cor_primaria_dark'] ?? '#0F172A'),
            'logo_path' => $this->texto($dados['logo_path'] ?? null),
        ];

        if ($this->suportaLojaId()) {
            $params['loja_id'] = $this->obterLojaIdAtual();
        }

        $ok = $stmt->execute($params);

        if ($ok) {
            $atual = $this->obter();
            $mudancas = $this->comparar($anterior, $atual);
            if ($mudancas !== []) {
                $this->auditoria->registrar(
                    'admin',
                    'configuracao_loja',
                    '1',
                    'atualizacao',
                    'Parametros da Loja atualizados',
                    ['mudancas' => $mudancas],
                    isset($_SESSION['usuario_id']) ? (string) $_SESSION['usuario_id'] : null
                );
            }
        }

        return $ok;
    }

    private function comparar(array $anterior, array $atual): array
    {
        $ignorados = ['created_at', 'updated_at'];
        $mudancas = [];
        foreach ($atual as $campo => $valorAtual) {
            if (in_array($campo, $ignorados, true)) {
                continue;
            }
            $valorAnterior = $anterior[$campo] ?? null;
            if ((string) $valorAnterior !== (string) $valorAtual) {
                $mudancas[$campo] = [
                    'antes' => $valorAnterior,
                    'depois' => $valorAtual,
                ];
            }
        }
        return $mudancas;
    }

    private function texto(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }

    private function data(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }

    private function uf(?string $valor): ?string
    {
        $valor = strtoupper(trim((string) $valor));
        return $valor !== '' ? substr($valor, 0, 2) : null;
    }

    private function decimal($valor): float
    {
        return round((float) $valor, 2);
    }

    private function inteiro($valor, int $padrao): int
    {
        $numero = (int) $valor;
        return $numero > 0 ? $numero : $padrao;
    }

    private function obterLojaIdAtual(): int
    {
        if ($this->lojaId === null) {
            $this->lojaId = $this->resolveCurrentStoreId($this->db);
        }

        return $this->lojaId;
    }
}
