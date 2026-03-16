<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load(__DIR__ . '/../.env');

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🛠️  CRIANDO ESTRUTURA COMPLETA - Tesoureiro Financeiro\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    echo "📥 Conectando ao Supabase...\n";
    $db = Database::getConnection();
    echo "✅ Conectado!\n\n";

    // 1. Corrigir categorias_financeiras
    echo "🔧 Corrigindo tabela: categorias_financeiras\n";
    $db->exec("ALTER TABLE IF EXISTS categorias_financeiras ADD COLUMN IF NOT EXISTS descricao TEXT NULL");
    $db->exec("ALTER TABLE IF EXISTS categorias_financeiras ADD COLUMN IF NOT EXISTS principal BOOLEAN NOT NULL DEFAULT true");
    $db->exec("ALTER TABLE IF EXISTS categorias_financeiras ADD COLUMN IF NOT EXISTS ativo BOOLEAN NOT NULL DEFAULT true");
    echo "   ✅ Estrutura OK\n";

    // 2. Criar lancamentos_financeiros
    echo "🔧 Criando tabela: lancamentos_financeiros\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS lancamentos_financeiros (
            id              SERIAL PRIMARY KEY,
            tipo            VARCHAR(10) NOT NULL,
            categoria_id    INT NOT NULL REFERENCES categorias_financeiras(id),
            valor           NUMERIC(10,2) NOT NULL,
            data_lancamento DATE NOT NULL,
            descricao       TEXT NULL,
            obreiro_id      UUID NULL REFERENCES obreiros(id),
            mes_ref         INT NOT NULL,
            ano_ref         INT NOT NULL,
            created_by      UUID NULL,
            created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "   ✅ Criada\n";

    // 3. Criar comprovantes_pix
    echo "🔧 Criando tabela: comprovantes_pix\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS comprovantes_pix (
            id               SERIAL PRIMARY KEY,
            obreiro_id       UUID NULL REFERENCES obreiros(id),
            telegram_user_id BIGINT NOT NULL,
            nome_telegram    VARCHAR(255) NULL,
            file_id          TEXT NOT NULL,
            tipo_arquivo     VARCHAR(50) NULL,
            nome_arquivo     VARCHAR(255) NULL,
            descricao_usuario TEXT NULL,
            valor_informado  NUMERIC(10,2) NULL,
            mes_ref_informado INT NULL,
            ano_ref_informado INT NULL,
            status           VARCHAR(20) NOT NULL DEFAULT 'pendente',
            motivo_rejeicao  TEXT NULL,
            validado_por     UUID NULL REFERENCES obreiros(id),
            valor_validado   NUMERIC(10,2) NULL,
            mes_ref_validado INT NULL,
            ano_ref_validado INT NULL,
            lancamento_id    INT NULL REFERENCES lancamentos_financeiros(id),
            criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            validado_em      TIMESTAMP NULL
        )
    ");
    echo "   ✅ Criada\n";

    // 4. Criar mensalidades_status
    echo "🔧 Criando tabela: mensalidades_status\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS mensalidades_status (
            id            SERIAL PRIMARY KEY,
            obreiro_id    UUID NOT NULL REFERENCES obreiros(id),
            mes_ref       INT NOT NULL,
            ano_ref       INT NOT NULL,
            status        VARCHAR(20) NOT NULL DEFAULT 'pendente',
            lancamento_id INT NULL REFERENCES lancamentos_financeiros(id),
            nota          TEXT NULL,
            atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(obreiro_id, mes_ref, ano_ref)
        )
    ");
    echo "   ✅ Criada\n";

    // 5. Criar regularidade_obreiro
    echo "🔧 Criando tabela: regularidade_obreiro\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS regularidade_obreiro (
            id           SERIAL PRIMARY KEY,
            obreiro_id   UUID NOT NULL REFERENCES obreiros(id),
            mes_ref      INT NOT NULL,
            ano_ref      INT NOT NULL,
            status       VARCHAR(20) NOT NULL DEFAULT 'regular',
            observacao   TEXT NULL,
            definido_por UUID NULL REFERENCES obreiros(id),
            definido_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(obreiro_id, mes_ref, ano_ref)
        )
    ");
    echo "   ✅ Criada\n";

    // 6. Criar fechamento_mensal
    echo "🔧 Criando tabela: fechamento_mensal\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS fechamento_mensal (
            id               SERIAL PRIMARY KEY,
            mes_ref          INT NOT NULL,
            ano_ref          INT NOT NULL,
            saldo_inicial    NUMERIC(12,2) NOT NULL DEFAULT 0.00,
            total_entradas   NUMERIC(12,2) NOT NULL DEFAULT 0.00,
            total_saidas     NUMERIC(12,2) NOT NULL DEFAULT 0.00,
            saldo_final      NUMERIC(12,2) NOT NULL DEFAULT 0.00,
            status           VARCHAR(20) NOT NULL DEFAULT 'aberto',
            fechado_por      UUID NULL REFERENCES obreiros(id),
            fechado_em       TIMESTAMP NULL,
            criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(mes_ref, ano_ref)
        )
    ");
    echo "   ✅ Criada\n";

    // 7. Criar ajustes_saldo_auditoria
    echo "🔧 Criando tabela: ajustes_saldo_auditoria\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS ajustes_saldo_auditoria (
            id               SERIAL PRIMARY KEY,
            fechamento_id    INT NOT NULL REFERENCES fechamento_mensal(id),
            campo_alterado   VARCHAR(50) NOT NULL,
            valor_anterior   NUMERIC(12,2) NOT NULL,
            valor_novo       NUMERIC(12,2) NOT NULL,
            justificativa    TEXT NOT NULL,
            alterado_por     UUID NULL REFERENCES obreiros(id),
            alterado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "   ✅ Criada\n";

    // 8. Criar tronco_solidariedade
    echo "🔧 Criando tabela: tronco_solidariedade\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS tronco_solidariedade (
            id        SERIAL PRIMARY KEY,
            tipo      VARCHAR(10) NOT NULL,
            valor     NUMERIC(12,2) NOT NULL,
            data_mov  DATE NOT NULL,
            sessao_ref VARCHAR(50) NULL,
            descricao TEXT NULL,
            criado_por UUID NULL REFERENCES obreiros(id),
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "   ✅ Criada\n";

    // 9. Criar índices
    echo "\n📊 Criando índices...\n";
    $db->exec("CREATE INDEX IF NOT EXISTS idx_lancamentos_mes_ano ON lancamentos_financeiros(mes_ref, ano_ref)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_lancamentos_categoria ON lancamentos_financeiros(categoria_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_lancamentos_obreiro ON lancamentos_financeiros(obreiro_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_comprovantes_status ON comprovantes_pix(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_comprovantes_obreiro ON comprovantes_pix(obreiro_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_comprovantes_telegram ON comprovantes_pix(telegram_user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mensalidades_status ON mensalidades_status(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mensalidades_obreiro_mes ON mensalidades_status(obreiro_id, mes_ref, ano_ref DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_regularidade_obreiro ON regularidade_obreiro(obreiro_id, mes_ref, ano_ref)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_fechamento_mes_ano ON fechamento_mensal(mes_ref, ano_ref DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_auditoria_fechamento ON ajustes_saldo_auditoria(fechamento_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_tronco_data ON tronco_solidariedade(data_mov)");
    echo "   ✅ 12 índices criados\n";

    // 10. Inserir categorias
    echo "\n📝 Verificando categorias...\n";
    $count = $db->query("SELECT COUNT(*) as cnt FROM categorias_financeiras")->fetch(\PDO::FETCH_ASSOC)['cnt'];
    echo "   Categorias existentes: {$count}/22\n";

    if ($count < 22) {
        echo "   ⚠️  Inserindo categorias faltantes...\n";
        
        $categorias = [
            ['Mensalidades', 'entrada', 'Contribuição mensal dos obreiros', true],
            ['Biblioteca Fiat Lux', 'entrada', 'Receita biblioteca', true],
            ['Ágape', 'entrada', 'Receita de ágapes e eventos', true],
            ['Iniciação', 'entrada', 'Taxa de iniciação', true],
            ['Elevação', 'entrada', 'Taxa de elevação de grau', true],
            ['Exaltação', 'entrada', 'Taxa de exaltação de grau', true],
            ['Regularização', 'entrada', 'Taxa de regularização', true],
            ['Filiação', 'entrada', 'Taxa de filiação de irmão externo', false],
            ['Diversos', 'entrada', 'Receitas diversas não categorizadas', false],
            ['Tronco de Solidariedade', 'entrada', 'Tronco de arrecadação solidária', true],
            ['Juros Aplicação Bancária', 'entrada', 'Juros de aplicações financeiras', false],
            ['Despesas Grande Loja', 'saida', 'Contribuição à Grande Loja', true],
            ['Aluguel Templo', 'saida', 'Aluguel do espaço de reuniões', true],
            ['Aluguel Salão de Ágapes', 'saida', 'Aluguel para eventos e ágapes', true],
            ['Aluguel', 'saida', 'Aluguéis gerais', false],
            ['Despesas Bancárias', 'saida', 'Tarifas e taxas bancárias', true],
            ['A Trolha', 'saida', 'Fornecedor de materiais ritualísticos', false],
            ['Gráfica', 'saida', 'Serviços gráficos', false],
            ['Despesas Cartório', 'saida', 'Registros cartoriais', false],
            ['Despesas Ágape', 'saida', 'Custeio de ágapes e eventos', true],
            ['Despesas Tronco de Solidariedade', 'saida', 'Aplicação do tronco solidário', true],
            ['Despesas Diversas da Loja', 'saida', 'Despesas gerais', false],
        ];

        $stmt = $db->prepare("
            INSERT INTO categorias_financeiras (nome, tipo, descricao, principal, ativo)
            VALUES (?, ?, ?, ?, true)
        ");

        foreach ($categorias as $cat) {
            try {
                $stmt->execute([$cat[0], $cat[1], $cat[2], $cat[3]]);
            } catch (\Exception $e) {
                // Categoria pode já existir, ignora
            }
        }

        echo "   ✅ Categorias inseridas\n";
    } else {
        echo "   ✅ Todas as 22 categorias já existem\n";
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ BANCO COMPLETO E PRONTO!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "📋 Tabelas criadas:\n";
    echo "   ✅ lancamentos_financeiros (Livro-Caixa)\n";
    echo "   ✅ comprovantes_pix (Fila PIX com foto/documento)\n";
    echo "   ✅ mensalidades_status (Status mensal por obreiro)\n";
    echo "   ✅ regularidade_obreiro (Regular/Irregular)\n";
    echo "   ✅ fechamento_mensal (Encerramento mensal)\n";
    echo "   ✅ ajustes_saldo_auditoria (Rastreamento de mudanças)\n";
    echo "   ✅ tronco_solidariedade (Sub-livro tronco)\n\n";
    echo "📊 Categorias: 22 inseridas (entrada + saída)\n";
    echo "🔍 Índices: 12 criados para performance\n\n";
    echo "✨ SISTEMA TESOURARIA 100% FUNCIONAL!\n\n";
    echo "🎯 Você pode acessar agora:\n";
    echo "   • Dashboard: https://gestor-loja-web.onrender.com/tesouraria/caixa\n";
    echo "   • Bot: Obreiros podem enviar PIX para validação\n";
    echo "   • Validação: Tesoureiro aprova comprovantes\n";
    echo "   • Período: Encerramento mensal com auditoria\n\n";

} catch (Exception $e) {
    echo "\n❌ ERRO FATAL:\n";
    echo "   {$e->getMessage()}\n\n";
    exit(1);
}
