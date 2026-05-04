-- Fase 2: PWA Theme Configuration
-- Este script adiciona as colunas de identidade visual à tabela de configurações da loja.
-- Como é uma transição multi-tenant aditiva, as tabelas não são recriadas.

DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'configuracoes_loja' AND column_name = 'cor_primaria_light') THEN
        ALTER TABLE configuracoes_loja ADD COLUMN cor_primaria_light VARCHAR(20) DEFAULT '#1E3A8A';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'configuracoes_loja' AND column_name = 'cor_primaria_dark') THEN
        ALTER TABLE configuracoes_loja ADD COLUMN cor_primaria_dark VARCHAR(20) DEFAULT '#0F172A';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'configuracoes_loja' AND column_name = 'logo_path') THEN
        ALTER TABLE configuracoes_loja ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL;
    END IF;
END $$;
