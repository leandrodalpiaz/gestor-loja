# Bot de Efemerides - Telegram

Este modulo esta preparado para operar em paralelo ao ambiente de producao atual.

## Responsabilidade do Chanceler

A manutencao de datas e mensagens deve ser feita na planilha Google Sheets configurada em `config.json`.

### Aba principal

Campos recomendados:

- `data`: data do evento (dd/mm/aaaa)
- `Nome`
- `cod_evento` ou `Tipo`
- `cod_vinculo`
- `vinculo`
- `parentesco`
- `Local`
- `Mensagem` (opcional para eventos historicos customizados)

### Abas de mensagens

- `msg_aniversarios`
- `msg_iniciacao`
- `msg_elevacao`
- `msg_exaltacao`
- `msg_instalacao`
- `mensagens_extras`

As mensagens complementares sao rotacionadas automaticamente sem repeticao imediata.

## Teste seguro (sem envio)

No PowerShell, execute:

```powershell
cd D:\leandro_pessoal\Renascenca\gestor-loja\telegram_efemerides
python main.py --dry-run
```

Arquivo de preview gerado:

- `ultima_mensagem_preview.txt`

## Envio real para Telegram

1. Preencha `telegram_settings` em `config.json`:

- `bot_token`
- `test_chat_id`
- `production_chat_id`
- `use_test_chat`

1. Execute:

```powershell
cd D:\leandro_pessoal\Renascenca\gestor-loja\telegram_efemerides
python main.py
```

## Observacoes

- Este modulo nao depende de WhatsApp.
- O bot em producao pode continuar rodando ate voce validar a saida deste modulo.
