-- 021_dados_oficiais_loja_historia.sql
-- Amplia os parametros institucionais da Loja com dados oficiais e historico.

ALTER TABLE public.configuracoes_loja
    ADD COLUMN IF NOT EXISTS titulo_tratamento VARCHAR(120),
    ADD COLUMN IF NOT EXISTS decreto_fundacao VARCHAR(40),
    ADD COLUMN IF NOT EXISTS data_reconhecimento DATE,
    ADD COLUMN IF NOT EXISTS data_instalacao DATE,
    ADD COLUMN IF NOT EXISTS data_entrega_carta_constitutiva DATE,
    ADD COLUMN IF NOT EXISTS tipo_loja VARCHAR(80),
    ADD COLUMN IF NOT EXISTS regiao_simposios VARCHAR(120),
    ADD COLUMN IF NOT EXISTS obediencia_templo VARCHAR(80),
    ADD COLUMN IF NOT EXISTS templo_endereco VARCHAR(255),
    ADD COLUMN IF NOT EXISTS proprietario_locatario VARCHAR(80),
    ADD COLUMN IF NOT EXISTS nome_templo VARCHAR(180),
    ADD COLUMN IF NOT EXISTS data_sagracao_templo DATE,
    ADD COLUMN IF NOT EXISTS trabalha_palacio_maconico BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS dia_semana_reuniao VARCHAR(40),
    ADD COLUMN IF NOT EXISTS horario_reuniao VARCHAR(40),
    ADD COLUMN IF NOT EXISTS periodicidade_reuniao VARCHAR(40),
    ADD COLUMN IF NOT EXISTS historia_loja TEXT;

UPDATE public.configuracoes_loja
   SET nome_loja = COALESCE(NULLIF(nome_loja, ''), 'Renascenca'),
       numero_loja = COALESCE(NULLIF(numero_loja, ''), '270'),
       titulo_tratamento = COALESCE(NULLIF(titulo_tratamento, ''), 'Aug Resp Loj Simb'),
       cidade = COALESCE(NULLIF(cidade, ''), 'Arroio do Sal'),
       uf = COALESCE(NULLIF(uf, ''), 'RS'),
       oriente = COALESCE(NULLIF(oriente, ''), 'Arroio do Sal / RS'),
       potencia_nome = COALESCE(NULLIF(potencia_nome, ''), 'Grande Loja Maconica do Estado do Rio Grande do Sul'),
       potencia_sigla = COALESCE(NULLIF(potencia_sigla, ''), 'GLOJARS'),
       rito = COALESCE(NULLIF(rito, ''), 'Escoces (REAA)'),
       data_fundacao = COALESCE(data_fundacao, DATE '2017-12-20'),
       cnpj = COALESCE(NULLIF(cnpj, ''), '31.274.071/0001-06'),
       decreto_fundacao = COALESCE(NULLIF(decreto_fundacao, ''), '007'),
       data_reconhecimento = COALESCE(data_reconhecimento, DATE '2018-03-05'),
       data_instalacao = COALESCE(data_instalacao, DATE '2018-03-28'),
       data_entrega_carta_constitutiva = COALESCE(data_entrega_carta_constitutiva, DATE '2022-10-22'),
       tipo_loja = COALESCE(NULLIF(tipo_loja, ''), 'Loja Simbolica'),
       regiao_simposios = COALESCE(NULLIF(regiao_simposios, ''), '32a Regiao Maconica - Litoral'),
       obediencia_templo = COALESCE(NULLIF(obediencia_templo, ''), 'GLOJARS'),
       templo_endereco = COALESCE(NULLIF(templo_endereco, ''), 'Rua Santos Dumont, 2341, Albatroz, Osorio'),
       proprietario_locatario = COALESCE(NULLIF(proprietario_locatario, ''), 'Locatario'),
       nome_templo = COALESCE(NULLIF(nome_templo, ''), 'Samuel Herbert Johnes'),
       data_sagracao_templo = COALESCE(data_sagracao_templo, DATE '1980-09-13'),
       trabalha_palacio_maconico = COALESCE(trabalha_palacio_maconico, FALSE),
       dia_semana_reuniao = COALESCE(NULLIF(dia_semana_reuniao, ''), 'Quarta-feira'),
       horario_reuniao = COALESCE(NULLIF(horario_reuniao, ''), '20h'),
       periodicidade_reuniao = COALESCE(NULLIF(periodicidade_reuniao, ''), 'Quinzenal'),
       historia_loja = COALESCE(
            NULLIF(historia_loja, ''),
            $hist$
HISTORICO DA LOJA RENASCENCA No 270

Em 06/05/2017, apos uma sessao ordinaria da Loja de Estudos e Pesquisas Acacia do Litoral no 234, no Templo da Loja Rosa dos Ventos no 76, em Osorio, os irmaos De Latorre, Peixouto, Victor e Vilson passaram a conversar sobre a conducao das Lojas Simbolicas e a necessidade de retomar o trabalho ritualistico com fidelidade aos rituais, regulamentos e Landmarks.

Dessa conversa nasceu a ideia de fundar uma nova Loja que trabalhasse com observancia ritual, disciplina administrativa e identidade maconica bem definida. A primeira reuniao formal foi agendada para 12/05/2017, no escritorio do Irmao Peixouto, em Arroio do Sal, reunindo irmaos alinhados com esse mesmo ideal.

Nessa reuniao ficou acertado que a nova oficina se chamaria Aug. Resp. Loj. Simb. Renascenca, trabalharia no Rito Escoces Antigo e Aceito, teria Oriente em Arroio do Sal e realizaria duas sessoes mensais, inicialmente com possibilidade de funcionamento nos templos das Lojas Mario Behring no 95, em Capao da Canoa, ou Rosa dos Ventos no 76, em Osorio.

Na reuniao de 25/05/2017, em Tramandai, confirmou-se o apoio do entao candidato a Grao Mestre Norton Valladao Pazzini, bem como a viabilidade pratica da fundacao da nova Loja. Considerando o deslocamento dos irmaos e a centralidade geografica de Osorio, decidiu-se que as reunioes ocorreriam no Templo da Loja Rosa dos Ventos no 76.

Com o apoio institucional assegurado, iniciou-se a organizacao material e juridica da nova oficina: estatuto, regimento interno, identidade visual e convites a novos fundadores. Em 25/10/2017, ja com Norton Valladao Pazzini eleito Grao Mestre da Grande Loja Maconica do Estado do Rio Grande do Sul para o trienio 2018/2021, uma nova reuniao consolidou o projeto, aprovou por unanimidade o Estatuto, o Regimento Interno e o Estandarte, e definiu a primeira administracao.

A fundacao da Loja foi marcada para 20/12/2017, nas dependencias da Loja Rosa dos Ventos no 76, em Osorio. Depois disso, em 28/02/2018, uma comitiva da Renascenca protocolou na Grande Loja a prancha requerendo a instalacao e posse da nova oficina, sendo definida a recepcao da Loja sob o numero 270.

O Decreto no 07-2018/2021, publicado em 05/03/2018, homologou a primeira administracao e confirmou a instalacao da nova Loja. Finalmente, em 28/03/2018, realizou-se a Sessao Magna de Instalacao e Posse, presidida pelo Serenissimo Grao Mestre Norton Valladao Panizzi, com presenca de autoridades da Grande Loja, veneraveis mestres visitantes, comitivas de diversas oficinas e expressiva representacao maconica.

Assim se consolidou a Loja Renascenca no 270: nascida do desejo de restaurar a correicao ritualistica, fortalecer a vida simbolica e construir uma oficina fiel aos principios da Arte Real.
$hist$
       ),
       updated_at = NOW()
 WHERE id = 1;
