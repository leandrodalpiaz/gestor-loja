-- ============================================================
-- Migration 002: Mensagens Complementares + Rotação
-- ============================================================

-- Banco de frases rotativas por tipo de evento
CREATE TABLE IF NOT EXISTS mensagens_complementares (
    id         SERIAL PRIMARY KEY,
    tipo       VARCHAR(60)  NOT NULL,
    -- Tipos aceitos:
    --   aniversario_irmao, aniversario_cunhada,
    --   aniversario_sobrinha, aniversario_sobrinho,
    --   iniciacao, elevacao, exaltacao, instalacao,
    --   fallback
    mensagem   TEXT         NOT NULL,
    ativo      BOOLEAN      NOT NULL DEFAULT true,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_msg_comp_tipo_ativo
    ON mensagens_complementares(tipo, ativo);

-- Histórico de rotação (evita repetir a mesma frase consecutivamente)
CREATE TABLE IF NOT EXISTS mensagens_rotacao_historico (
    tipo       VARCHAR(60)  NOT NULL PRIMARY KEY,
    ids_usados INTEGER[]    NOT NULL DEFAULT '{}',
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Seed: frases das abas do Google Sheets (port fiel do Python)
-- Valores baseados nos templates originais do telegram_efemerides
-- ============================================================

-- Aniversário - Irmão
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('aniversario_irmao', 'Que o Grande Arquiteto do Universo abençoe cada passo de sua jornada, concedendo-lhe saúde, sabedoria e paz. Com fraternidade e alegria, toda a Família Renascença une-se nesta celebração!'),
('aniversario_irmao', 'Que este novo ciclo seja repleto de luz, aprendizado e realizações. A Loja Renascença tem honra de tê-lo em seu quadro e deseja-lhe tudo de melhor neste dia especial!'),
('aniversario_irmao', 'Que o esquadro e o compasso guiem seus passos neste novo ano de vida, Irmão. Muita saúde, paz e fraternidade são os votos de toda a nossa Família Maçônica!'),
('aniversario_irmao', 'Com alegria e gratidão por ter um Irmão como você entre nós, desejamos que este aniversário seja o começo de um ano ainda mais iluminado e pleno de conquistas!'),
('aniversario_irmao', 'Que a Luz que nos guia neste Templo ilumine cada dia de sua vida. A Renascença celebra com você este marco e renova os votos de fraternidade eterna!');

-- Aniversário - Cunhada
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('aniversario_cunhada', 'A família Renascença se alegra em celebrar este dia tão especial. Desejamos-lhe muita saúde, felicidade e realizações neste novo ciclo!'),
('aniversario_cunhada', 'Com carinho e respeito, toda a família maçônica une-se para celebrar este dia. Que seja um ano repleto de bênçãos e alegrias!'),
('aniversario_cunhada', 'Com muita alegria, a Loja Renascença deseja-lhe um feliz aniversário, repleto de paz, saúde e felicidade!');

-- Aniversário - Sobrinha
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('aniversario_sobrinha', 'Que este novo ciclo seja repleto de alegria, conquistas e muita luz! A família Renascença celebra com vocês este dia especial.'),
('aniversario_sobrinha', 'Com carinho e fraternidade, desejamos à nossa jovem um feliz aniversário, repleto de bênçãos e sorrisos!'),
('aniversario_sobrinha', 'Que o futuro seja tão brilhante quanto o seu sorriso. Muitas felicidades neste dia especial, com todo o carinho da família Renascença!');

-- Aniversário - Sobrinho
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('aniversario_sobrinho', 'Que este novo ano de vida seja repleto de conquistas, aprendizados e muita alegria! A família Renascença celebra com vocês este dia especial.'),
('aniversario_sobrinho', 'Com fraternidade e carinho, desejamos ao nosso jovem um feliz aniversário, repleto de bênçãos e realizações!'),
('aniversario_sobrinho', 'Que cada ano seja uma nova pedra bem lavrada na construção de uma vida plena. Feliz aniversário, com todo o carinho da família Renascença!');

-- Iniciação
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('iniciacao', 'Que a Luz que recebestes naquele dia continue a guiar cada passo de vossa jornada! A Renascença celebra com alegria esta data que marcou o início de uma bela caminhada na Ordem.'),
('iniciacao', 'Naquele momento, uma porta se abriu para um mundo de luz, fraternidade e sabedoria. A Loja Renascença congratula-se com você por cada passo dado desde então!'),
('iniciacao', 'Do profano ao iniciado, uma transformação que não se apaga. Que a chama acesa naquele dia continue a iluminar sua alma e sua obra maçônica!');

-- Elevação
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('elevacao', 'Que a firmeza do Companheiro continue a guiar vossa conduta, e que a busca pela perfeição nunca cesse. A Renascença celebra com orgulho esta data!'),
('elevacao', 'O segundo passo é a confirmação de um compromisso com a Luz. A Loja Renascença recorda com honra o dia em que você se firmou ainda mais na Ordem!'),
('elevacao', 'Do Aprendiz ao Companheiro, o aprofundamento de uma linda jornada. Parabéns por cada passo nessa caminhada de luz e fraternidade!');

-- Exaltação
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('exaltacao', 'Que as virtudes do Mestre continuem a florescer em vossa vida, querido Irmão. A Renascença honra esta data com todo o respeito e fraternidade que vós mereceis!'),
('exaltacao', 'A exaltação ao grau de Mestre é o coroamento de uma jornada de dedicação e aprendizado. A Loja Renascença celebra com você este marco inesquecível!'),
('exaltacao', 'Ser Mestre Maçom é um compromisso eterno com a Luz, a Verdade e a Fraternidade. Que este aniversário reavive em seu coração a chama desse compromisso sagrado!');

-- Instalação
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('instalacao', 'Que o Malhete entregue naquele dia continue a lapidar sempre o melhor em cada Irmão. A Renascença recorda com gratidão e orgulho esta data de serviço à Ordem!'),
('instalacao', 'A Instalação é o chamado ao serviço, ao exemplo e à liderança. A Loja Renascença celebra com honra o dia em que assumistes este nobre encargo!'),
('instalacao', 'Que a sabedoria que guiou seu governo continue a inspirar nossos trabalhos. Com fraternidade e respeito, a Renascença recorda com carinho esta data!');

-- Fallback (curiosidades / mensagens de reflexão maçônica)
INSERT INTO mensagens_complementares (tipo, mensagem) VALUES
('fallback', '"A Maçonaria ensina que cada ser humano tem a possibilidade de aperfeiçoar-se, polindo as suas arestas como o canteiro aperfeiçoa a pedra bruta." — Pensamento Maçônico'),
('fallback', '"Um Maçom verdadeiro trabalha sem cessar em sua própria formação, sabendo que a obra mais importante é a do aperfeiçoamento do próprio caráter." — Tradição da Ordem'),
('fallback', '"A Fraternidade não é apenas uma palavra gravada no altar. É o compromisso de olhar para o Irmão e enxergar a nós mesmos." — Reflexão da Ordem'),
('fallback', '"O segredo da Maçonaria não está nas palavras ou sinais, mas na transformação silenciosa que ela opera no coração de quem a abraça com sinceridade." — Tradição Maçônica'),
('fallback', '"Somos todos pedras em lavra. O Templo que construímos não é de tijolos, mas de virtudes, palavras e ações que inspiram o próximo." — Pensamento da Ordem'),
('fallback', '"A Luz que buscamos não é apenas símbolo. É o conhecimento que liberta, a verdade que une e a sabedoria que guia." — Tradição da Ordem'),
('fallback', '"Antes de tentar aperfeiçoar o mundo, o Maçom é chamado a aperfeiçoar a si mesmo. Essa é a pedra fundamental de toda a nossa obra." — Reflexão Maçônica');
