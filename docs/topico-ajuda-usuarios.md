# Topico de Ajuda para Usuarios (Homologacao)

Este guia e para os usuarios convidados para testes em ambiente real.
Objetivo: reduzir atrito, padronizar uso e facilitar registro de problemas.

## 1) Primeiro acesso (Telegram)

1. Abra o chat do bot oficial da Loja.
2. Envie `/start`.
3. Aguarde o menu principal.
4. Se algum botao nao abrir, envie `/painel`.

## 2) Boas praticas durante o teste

1. Teste uma acao por vez.
2. Aguarde a resposta visual antes de clicar novamente.
3. Evite cliques repetidos no mesmo botao em sequencia rapida.
4. Quando houver erro, registre horario e acao executada.

## 3) Fluxos prioritarios por cargo

### Chanceler

1. Abrir `Chancelaria`.
2. Testar `Neste Dia`.
3. Testar `Miniapp do Chanceler`.
4. Testar `Emitir Certificado`.

### Biblioteca

1. Abrir `Biblioteca`.
2. Testar consulta de acervo.
3. Testar `Scanner` e `Cadastro manual`.
4. Testar comentarios/reacoes.

### Secretario

1. Abrir `Secretaria`.
2. Validar criacao/publicacao/reabertura de sessao.
3. Validar votacao e andamento de balaustre.
4. Confirmar retornos para menu/painel.

### Tesoureiro

1. Abrir `Tesouraria`.
2. Validar `Comprovantes`, `Regularidade`, `Fechamento`.
3. Validar `Livro-caixa` e `Obrigacoes`.
4. Confirmar mensagens de sucesso/erro.

## 4) Como registrar um problema

Sempre envie:

1. Cargo e tela usada.
2. Acao executada (botao/comando).
3. Resultado esperado.
4. Resultado obtido.
5. Horario aproximado.

Exemplo:

- Cargo: Chanceler
- Acao: Cliquei em `Neste Dia`
- Esperado: abrir previa do dia
- Obtido: sem retorno visual por varios segundos
- Horario: 24/04/2026 13:04

## 5) Dicas rapidas

1. Se o menu parecer travado, use `/painel`.
2. Se um miniapp nao abrir, feche e toque novamente.
3. Se erro persistir, informe o horario para analise de log.

## 6) Canais de suporte interno

Em caso de bloqueio:

1. Contatar Secretaria da Loja.
2. Informar o problema com o modelo do item 4.
