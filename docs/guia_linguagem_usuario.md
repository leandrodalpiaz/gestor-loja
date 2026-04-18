# Guia de Linguagem ao Usuário Final (PT-BR)

## Objetivo
Padronizar textos do ERP Web, Miniapps e Bot Telegram com linguagem natural, objetiva e operacional.

## Princípios
- Escrever em voz ativa.
- Evitar jargão técnico e termos internos de desenvolvimento.
- Priorizar frases curtas com ação clara.
- Manter consistência de termos por domínio (Secretaria, Chancelaria, Tesouraria, Biblioteca, Vigilâncias, Venerável).

## Padrões por tipo de mensagem
- Sucesso: confirmar resultado e manter foco operacional.
  Exemplo: `Sessão salva com sucesso.`
- Erro de validação: indicar contexto e próxima ação.
  Exemplo: `Não foi possível salvar. Revise os dados obrigatórios e tente novamente.`
- Erro operacional/integracão: indicar impacto e próxima ação sem detalhe técnico.
  Exemplo: `Não foi possível concluir esta ação agora. Tente novamente em instantes.`
- Acesso negado: informar restrição de forma direta.
  Exemplo: `Acesso restrito ao perfil responsável por este módulo.`
- Estado vazio: explicar ausência de dados e orientar continuidade.
  Exemplo: `Não há registros para este filtro no período selecionado.`
- Confirmação: explicitar consequência da ação.
  Exemplo: `Deseja publicar esta sessão agora?`

## Regras de qualidade textual
- Usar ortografia e acentuação do português do Brasil.
- Evitar caixa alta excessiva, exceto quando necessário por padrão do canal.
- Evitar termos genéricos: `Falha na operação`, `Erro desconhecido`, `Teste`, `Placeholder`.
- Evitar exposição de mensagem técnica ao usuário final.
- Manter logs técnicos apenas em camada interna (`error_log`, observabilidade, auditoria técnica).

## Checklist antes de publicar
- Menus e submenus com nomenclatura consistente.
- Alertas, toasts, placeholders e confirmações revisados.
- Mensagens de erro com formato `contexto + próxima ação`.
- Sem alteração de rota, callback, método HTTP ou estrutura de payload.
