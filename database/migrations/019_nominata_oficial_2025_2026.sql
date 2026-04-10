-- 019_nominata_oficial_2025_2026.sql
-- Ajusta nomenclatura oficial dos cargos e popula a nominata real da gestao 2025/2026.

UPDATE public.cargos
   SET nome_exibicao = CASE codigo
       WHEN 'VENERAVEL' THEN 'Veneravel Mestre'
       WHEN 'PRIMEIRO_VIGILANTE' THEN '1o Vigilante'
       WHEN 'SEGUNDO_VIGILANTE' THEN '2o Vigilante'
       WHEN 'SECRETARIO' THEN 'Secretario'
       WHEN 'TESOUREIRO' THEN 'Tesoureiro'
       WHEN 'CHANCELER' THEN 'Chanceler'
       WHEN 'HOSPITALEIRO' THEN 'Hospitaleiro'
       WHEN 'PRIMEIRO_DIACONO' THEN '1o Diacono'
       WHEN 'SEGUNDO_DIACONO' THEN '2o Diacono'
       WHEN 'MESTRE_DE_CERIMONIAS' THEN 'Mestre de Cerimonias'
       WHEN 'MESTRE_BANQUETES' THEN 'Mestre de Banquetes'
       WHEN 'ARQUITETO' THEN 'Arquiteto'
       WHEN 'PORTA_ESTANDARTE' THEN 'Porta-Estandarte'
       WHEN 'PORTA_ESPADA' THEN 'Porta-Espada'
       WHEN 'GUARDA_DO_TEMPLO' THEN 'Guarda do Templo'
       WHEN 'PRIMEIRO_EXPERTO' THEN '1o Experto'
       WHEN 'SEGUNDO_EXPERTO' THEN '2o Experto'
       WHEN 'MESTRE_DE_HARMONIA' THEN 'Mestre de Harmonia'
       WHEN 'ORADOR' THEN 'Orador'
       WHEN 'GUARDA_DA_LEI' THEN 'Guarda da Lei'
       ELSE nome_exibicao
   END
 WHERE codigo IN (
    'VENERAVEL','PRIMEIRO_VIGILANTE','SEGUNDO_VIGILANTE','SECRETARIO','TESOUREIRO',
    'CHANCELER','HOSPITALEIRO','PRIMEIRO_DIACONO','SEGUNDO_DIACONO','MESTRE_DE_CERIMONIAS',
    'MESTRE_BANQUETES','ARQUITETO','PORTA_ESTANDARTE','PORTA_ESPADA','GUARDA_DO_TEMPLO',
    'PRIMEIRO_EXPERTO','SEGUNDO_EXPERTO','MESTRE_DE_HARMONIA','ORADOR','GUARDA_DA_LEI'
 );

DO $$
DECLARE
    v_gestao_id BIGINT;
    v_ato_info TEXT := 'Ato 149 - registro oficial de 10/11/2025 08:46.';
    v_inicio TIMESTAMPTZ := TIMESTAMPTZ '2025-11-10 08:46:00-03';
    v_obreiro_id UUID;
BEGIN
    SELECT id
      INTO v_gestao_id
      FROM public.gestoes
     WHERE titulo = 'Gestao 2025/2026'
     ORDER BY id DESC
     LIMIT 1;

    IF v_gestao_id IS NULL THEN
        SELECT id
          INTO v_gestao_id
          FROM public.gestoes
         WHERE status = 'aberta'
         ORDER BY inicio_em DESC, id DESC
         LIMIT 1;

        IF v_gestao_id IS NOT NULL THEN
            UPDATE public.gestoes
               SET titulo = 'Gestao 2025/2026',
                   inicio_em = DATE '2025-11-10',
                   observacao = TRIM(BOTH ' ' FROM COALESCE(observacao || ' ', '') || v_ato_info),
                   updated_at = NOW()
             WHERE id = v_gestao_id;
        ELSE
            INSERT INTO public.gestoes (titulo, inicio_em, status, observacao, created_at, updated_at)
            VALUES ('Gestao 2025/2026', DATE '2025-11-10', 'aberta', v_ato_info, NOW(), NOW())
            RETURNING id INTO v_gestao_id;
        END IF;
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Alexandre Bock', 'Alexandre Hahn Bock')
        OR o.nome IN ('Alexandre Bock', 'Alexandre Hahn Bock')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Alexandre Bock' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('SEGUNDO_VIGILANTE', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Cassiano Nobre', 'Cassiano Mendes', 'Cassiano Mendes Nobre do Espirito Santo')
        OR o.nome IN ('Cassiano Nobre', 'Cassiano Mendes', 'Cassiano Mendes Nobre do Espirito Santo')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Cassiano Nobre' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('ORADOR', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Joel Horn', 'Joel Felipe Horn')
        OR o.nome IN ('Joel Horn', 'Joel Felipe Horn')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Joel Horn' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('PRIMEIRO_VIGILANTE', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    PERFORM public.atribuir_cargo('VENERAVEL', '8c117597-329f-4bb6-80be-9bf1d55ebd33', v_gestao_id, v_inicio, v_ato_info);

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Rafael Aislan', 'Rafael Aislan Amaral')
        OR o.nome IN ('Rafael Aislan', 'Rafael Aislan Amaral')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Rafael Aislan' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('TESOUREIRO', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    PERFORM public.atribuir_cargo('PRIMEIRO_EXPERTO', '58ccd3b3-0ee0-4ada-b489-a1ac49f1aab7', v_gestao_id, v_inicio, v_ato_info);
    PERFORM public.atribuir_cargo('ARQUITETO', '58ccd3b3-0ee0-4ada-b489-a1ac49f1aab7', v_gestao_id, v_inicio, v_ato_info);

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Gilvan Christello', 'Gilvan Consul Christello')
        OR o.nome IN ('Gilvan Christello', 'Gilvan Consul Christello')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Gilvan Christello' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('SEGUNDO_DIACONO', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    PERFORM public.atribuir_cargo('MESTRE_DE_HARMONIA', '77cd865b-00fa-4dd8-afcf-54b7159cc3e6', v_gestao_id, v_inicio, v_ato_info);

    PERFORM public.atribuir_cargo('GUARDA_DO_TEMPLO', '7e6278d8-7446-4a03-b5e7-5f6473b8dfc9', v_gestao_id, v_inicio, v_ato_info);

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Leandro Ortiz', 'Luiz Leandro Ortiz Bica')
        OR o.nome IN ('Leandro Ortiz', 'Luiz Leandro Ortiz Bica')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Leandro Ortiz' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('PRIMEIRO_DIACONO', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Marcelo Kordyas', 'Marcelo Vicentini Kordyas')
        OR o.nome IN ('Marcelo Kordyas', 'Marcelo Vicentini Kordyas')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Marcelo Kordyas' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('CHANCELER', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Marlon Maciel', 'Marlon de Oliveira Maciel')
        OR o.nome IN ('Marlon Maciel', 'Marlon de Oliveira Maciel')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Marlon Maciel' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('SECRETARIO', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Munir Mashini', 'Munir Musbah Mustafa Youssef Mashini')
        OR o.nome IN ('Munir Mashini', 'Munir Musbah Mustafa Youssef Mashini')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Munir Mashini' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('MESTRE_DE_CERIMONIAS', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Paulo da Silva Tedesco', 'Paulo Tedesco')
        OR o.nome IN ('Paulo da Silva Tedesco', 'Paulo Tedesco')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Paulo da Silva Tedesco' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('PORTA_ESPADA', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Rafael Euzebio', 'Rafael Euzebio Floriano')
        OR o.nome IN ('Rafael Euzebio', 'Rafael Euzebio Floriano')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Rafael Euzebio' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('MESTRE_BANQUETES', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Tiago Irigoyen', 'Tiago de Lima Irigoyen')
        OR o.nome IN ('Tiago Irigoyen', 'Tiago de Lima Irigoyen')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Tiago Irigoyen' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('HOSPITALEIRO', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;

    SELECT o.id
      INTO v_obreiro_id
      FROM public.obreiros o
     WHERE COALESCE(o.nome_historico, o.nome) IN ('Vilson Braga', 'Vilson Nunes Braga')
        OR o.nome IN ('Vilson Braga', 'Vilson Nunes Braga')
     ORDER BY CASE WHEN COALESCE(o.nome_historico, o.nome) = 'Vilson Braga' THEN 0 ELSE 1 END, o.id
     LIMIT 1;
    IF v_obreiro_id IS NOT NULL THEN
        PERFORM public.atribuir_cargo('PORTA_ESTANDARTE', v_obreiro_id, v_gestao_id, v_inicio, v_ato_info);
    END IF;
END $$;
