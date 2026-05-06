# ✅ TESOUREIRO - CHECKLIST DE HOMOLOGAÇÃO

**Status**: Pronto para homologação operacional  
**Data**: 2026-04-27  
**Risco Residual**: BAIXO (após correções de segurança)

---

## 📋 PRÉ-HOMOLOGAÇÃO

### Setup

- [ ] Aplicar migração 035 no banco:
  `````bash
  psql -h localhost -U usuario -d gestor_loja < database/migrations/035_tesoureiro_auditoria.sql
  ````bash
  psql -h localhost -U usuario -d gestor_loja < database/migrations/035_tesoureiro_auditoria.sql
  ```
  **Verificar**: Campos `quitado_por`, `quitado_em`, `cancelado_por`, `cancelado_em`, `motivo_cancelamento` existem

- [ ] Verificar se as tabelas estão criadas:
  `````sql
  SELECT * FROM obrigacao_financeira_parcelas LIMIT 1;  -- must have quitado_por, quitado_em
  SELECT * FROM comprovantes_pix LIMIT 1;               -- must have cancelado_por, cancelado_em, motivo_cancelamento
  ````sql
  SELECT * FROM obrigacao_financeira_parcelas LIMIT 1;  -- must have quitado_por, quitado_em
  SELECT * FROM comprovantes_pix LIMIT 1;               -- must have cancelado_por, cancelado_em, motivo_cancelamento
  ```

- [ ] Limpar dados de teste anteriores:
  `````sql
  DELETE FROM comprovantes_pix WHERE criado_em < NOW() - INTERVAL '1 month';
  DELETE FROM lancamentos_financeiros WHERE data_lancamento < NOW() - INTERVAL '1 month';
  ````sql
  DELETE FROM comprovantes_pix WHERE criado_em < NOW() - INTERVAL '1 month';
  DELETE FROM lancamentos_financeiros WHERE data_lancamento < NOW() - INTERVAL '1 month';
  ```

---

## 🧪 6 TESTES DE VERDADE (Cenários Reais)

### **TESTE 1: Aprovar PIX COM Vínculo de Parcela** ✅

**Setup**:
- Criar obreiro: João da Silva
- Criar obrigação: R$ 250 OUT/2025
- Criar parcela dessa obrigação
- Simular PIX: R$ 250.00 (foto/comprovante)

**Passos**:
1. Ir em `/tesouraria/comprovantes`
2. Selecionar PIX pendente
3. Validar valor: R$ 250.00
4. Selecionar competência: OUT/2025
5. Selecionar categoria: (deixar vazio/padrão)
6. Vincular parcela: "Out/2025 - R$ 250 (João)"
7. Clicar "Aprovar"

**Esperado**:
- ✅ Comprovante status = "aprovado"
- ✅ Parcela status = "quitada" (com `quitado_por` e `quitado_em` preenchidos)
- ✅ MensalidadeStatus OUT/2025 (João) = "pago"
- ✅ Nenhum lançamento foi criado (porque tinha parcela)
- ✅ Auditoria registra: "Validado por [usuário] em [data]"

**Verificar no BD**:
`````sql
SELECT status, quitado_por, quitado_em FROM obrigacao_financeira_parcelas WHERE id = <id>;  
-- Esperado: quitada, uuid, timestamp

SELECT status FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: aprovado

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 10 AND ano_ref = 2025;  
-- Esperado: pago
````sql
SELECT status, quitado_por, quitado_em FROM obrigacao_financeira_parcelas WHERE id = <id>;  
-- Esperado: quitada, uuid, timestamp

SELECT status FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: aprovado

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 10 AND ano_ref = 2025;  
-- Esperado: pago
```

---

### **TESTE 2: Aprovar PIX SEM Vínculo (Entrada Livre)** ✅

**Setup**:
- Obreiro: Maria da Silva
- Simular PIX: R$ 150.00 (foto/comprovante)
- Sem parcela em aberto

**Passos**:
1. Ir em `/tesouraria/comprovantes`
2. Selecionar PIX pendente
3. Validar valor: R$ 150.00
4. Selecionar competência: NOV/2025
5. Categoria: (deixar vazio/padrão)
6. Parcela: (deixar vazio)
7. Clicar "Aprovar"

**Esperado**:
- ✅ Comprovante status = "aprovado"
- ✅ Lançamento criado (tipo: entrada, valor: R$ 150, NOV/2025)
- ✅ MensalidadeStatus NOV/2025 (Maria) = "pago"
- ✅ Saldo de caixa aumentou em R$ 150

**Verificar no BD**:
`````sql
SELECT status FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: aprovado

SELECT tipo, valor, mes_ref, ano_ref FROM lancamentos_financeiros WHERE id = <id>;  
-- Esperado: entrada, 150.00, 11, 2025

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 11 AND ano_ref = 2025;  
-- Esperado: pago
````sql
SELECT status FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: aprovado

SELECT tipo, valor, mes_ref, ano_ref FROM lancamentos_financeiros WHERE id = <id>;  
-- Esperado: entrada, 150.00, 11, 2025

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 11 AND ano_ref = 2025;  
-- Esperado: pago
```

---

### **TESTE 3: Rejeitar PIX em Estado Pendente** ✅

**Setup**:
- Simular PIX novo: R$ 500.00 (foto/comprovante)
- Estado: "pendente"

**Passos**:
1. Ir em `/tesouraria/comprovantes`
2. Selecionar PIX pendente (não aprovado ainda)
3. Clicar "❌ Rejeitar"
4. Informar motivo: "Comprovante ilegível"
5. Confirmar

**Esperado**:
- ✅ Comprovante status = "rejeitado"
- ✅ Motivo registrado em DB
- ✅ Nenhum lançamento criado
- ✅ MensalidadeStatus não foi alterado
- ✅ Botão "Rejeitar" não aparece mais

**Verificar no BD**:
`````sql
SELECT status, motivo_rejeicao FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: rejeitado, "Comprovante ilegível"

SELECT COUNT(*) FROM lancamentos_financeiros WHERE comprovante_id = <id>;  
-- Esperado: 0 (nenhum)
````sql
SELECT status, motivo_rejeicao FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: rejeitado, "Comprovante ilegível"

SELECT COUNT(*) FROM lancamentos_financeiros WHERE comprovante_id = <id>;  
-- Esperado: 0 (nenhum)
```

---

### **TESTE 4: CANCELAR PIX COM Vínculo (Reversibilidade)** ✅

**Setup**:
- PIX já aprovado com vínculo (do TESTE 1)
- João da Silva, parcela #456, OUT/2025

**Passos**:
1. Ir em `/tesouraria/comprovantes`
2. Procurar comprovante aprovado (João, OUT/2025)
3. Clicar "↩️ Desfazer Aprovação"
4. Ver resumo: "Isso vai desfazer: Parcela OUT, MensalidadeStatus pago"
5. Informar motivo: "PIX falso detectado"
6. Confirmar

**Esperado**:
- ✅ Comprovante status = "cancelado"
- ✅ Parcela reverte → status = "pendente" (quitado_por = NULL)
- ✅ MensalidadeStatus OUT/2025 = "pendente" (foi revertido!)
- ✅ Auditoria registra: "Cancelado por [usuário] em [data] - PIX falso"
- ✅ Nenhum lançamento afetado (não havia)

**Verificar no BD**:
`````sql
SELECT status, quitado_por, quitado_em FROM obrigacao_financeira_parcelas WHERE id = 456;  
-- Esperado: pendente, NULL, NULL

SELECT status, cancelado_por, motivo_cancelamento FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: cancelado, uuid, "PIX falso detectado"

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 10 AND ano_ref = 2025;  
-- Esperado: pendente (foi revertido!)
````sql
SELECT status, quitado_por, quitado_em FROM obrigacao_financeira_parcelas WHERE id = 456;  
-- Esperado: pendente, NULL, NULL

SELECT status, cancelado_por, motivo_cancelamento FROM comprovantes_pix WHERE id = <id>;  
-- Esperado: cancelado, uuid, "PIX falso detectado"

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 10 AND ano_ref = 2025;  
-- Esperado: pendente (foi revertido!)
```

---

### **TESTE 5: CANCELAR PIX SEM Vínculo (Entrada Livre)** ✅

**Setup**:
- PIX já aprovado sem vínculo (do TESTE 2)
- Maria da Silva, NOV/2025, R$ 150

**Passos**:
1. Ir em `/tesouraria/comprovantes`
2. Procurar comprovante aprovado (Maria, NOV/2025)
3. Clicar "↩️ Desfazer Aprovação"
4. Ver resumo: "Isso vai desfazer: Lançamento R$ 150, MensalidadeStatus pago"
5. Informar motivo: "Valor errado"
6. Confirmar

**Esperado**:
- ✅ Comprovante status = "cancelado"
- ✅ Lançamento deletado (NOV/2025, R$ 150)
- ✅ MensalidadeStatus NOV/2025 = "pendente" (foi revertido!)
- ✅ Saldo de caixa diminuiu em R$ 150
- ✅ Auditoria registra: "Cancelado por [usuário] - Valor errado"

**Verificar no BD**:
`````sql
SELECT COUNT(*) FROM lancamentos_financeiros WHERE comprovante_id = <id>;  
-- Esperado: 0 (foi deletado)

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 11 AND ano_ref = 2025;  
-- Esperado: pendente (foi revertido!)
````sql
SELECT COUNT(*) FROM lancamentos_financeiros WHERE comprovante_id = <id>;  
-- Esperado: 0 (foi deletado)

SELECT status FROM mensalidades_status WHERE obreiro_id = <id> AND mes_ref = 11 AND ano_ref = 2025;  
-- Esperado: pendente (foi revertido!)
```

---

### **TESTE 6: Tentar CANCELAR Retroativo (> 30 dias)** ✅

**Setup**:
- PIX aprovado há 40 dias atrás
- Data criação: 2026-02-16 (40 dias antes de 2026-04-27)

**Passos**:
1. Ir em `/tesouraria/comprovantes`
2. Procurar comprovante com `criado_em` > 30 dias atrás
3. Clicar "↩️ Desfazer Aprovação"
4. Tentar confirmar

**Esperado**:
- ✅ Erro de validação: "Não é possível cancelar operações com mais de 30 dias"
- ✅ Operação é bloqueada (sem rollback, pois nunca começou a transação)
- ✅ Comprovante permanece "aprovado"
- ✅ Botão "Desfazer" fica desabilitado com tooltip: "Operação muito antiga"

**Verificar**:
- Linha de código garante: 
  `````php
  $diasAtras = (int) ((time() - strtotime($comprovante['criado_em'])) / 86400);
  if ($diasAtras > 30) { JsonResponse::send(['ok' => false, 'erro' => '...']); }
  ````php
  $diasAtras = (int) ((time() - strtotime($comprovante['criado_em'])) / 86400);
  if ($diasAtras > 30) { JsonResponse::send(['ok' => false, 'erro' => '...']); }
  ```

---

## 🔄 TESTE 7 (Bonus): Fluxo Completo com Fechamento

**Setup**:
- Competência: OUT/2025
- 3 obreiros com obrigações
- PIX aprovados vinculados

**Passos**:
1. Aprovar 3 PIX (TESTE 1 x3)
2. Ir em `/tesouraria/fechamento`
3. Selecionar competência OUT/2025
4. Revisar relatório:
   - ✅ Lançamentos aparecem
   - ✅ Obreiros aparecem como "pago"
   - ✅ Saldo final correto
5. Clicar "Fechar competência"

**Esperado**:
- ✅ Competência é fechada
- ✅ Não permite mais cancelamentos (operações retroativas bloqueadas)
- ✅ Relatório imutável

**Verificar no BD**:
`````sql
SELECT COUNT(*) FROM fechamento_mensal WHERE mes_ref = 10 AND ano_ref = 2025;  
-- Esperado: 1

SELECT status FROM comprovantes_pix WHERE mes_ref_validado = 10 AND ano_ref_validado = 2025;  
-- Esperado: todos "aprovado", nenhum "cancelado"
````sql
SELECT COUNT(*) FROM fechamento_mensal WHERE mes_ref = 10 AND ano_ref = 2025;  
-- Esperado: 1

SELECT status FROM comprovantes_pix WHERE mes_ref_validado = 10 AND ano_ref_validado = 2025;  
-- Esperado: todos "aprovado", nenhum "cancelado"
```

---

## 📊 Tabela de Validação

| Teste | Ação | Resultado Esperado | BD Verificação | Status |
|-------|------|-------------------|-----------------|--------|
| 1 | Aprovar com vínculo | Parcela quitada, MensalidadeStatus pago | ✅ |  |
| 2 | Aprovar sem vínculo | Lançamento criado, MensalidadeStatus pago | ✅ |  |
| 3 | Rejeitar pendente | Comprovante rejeitado, nenhuma mudança | ✅ |  |
| 4 | Cancelar com vínculo | Parcela pendente, MensalidadeStatus revertido | ✅ |  |
| 5 | Cancelar sem vínculo | Lançamento deletado, MensalidadeStatus revertido | ✅ |  |
| 6 | Lock retroativo | Erro se > 30 dias | ✅ |  |
| 7 | Fechamento | Competência fechada, relatório imutável | ✅ |  |

---

## ⚠️ Cenários de Risco (Monitorar em Produção)

1. **MensalidadeStatus com inconsistência**: Se algum usuário vir "Pago" mas "Parcela Pendente"
   - → Dashboard deve mostrar alerta: "Inconsistência detectada"
   - → Botão "Sincronizar" para resolver manualmente

2. **Cancelamento retroativo**: Se tesoureiro tenta cancelar PIX de 2 meses atrás
   - → Será bloqueado com erro claro
   - → Log será gerado: "Tentativa de cancelamento retroativo bloqueada"

3. **Dupla-contagem de parcela**: Se quitar parcela, cancelar, quitar novamente
   - → Sistema permite (lógica está correta)
   - → Mas auditoria rastreia tudo: 2 quitações registradas

---

## 🎯 Conclusão de Homologação

**Tesoureiro está PRONTO para homologação** com:

✅ **Atomicidade**: Aprovar = transação (tudo ou nada)  
✅ **Reversibilidade**: Cancelamento desfaz 100% (inclusive MensalidadeStatus)  
✅ **Validações**: Bloqueiam entrada inválida (valor, mês, categoria, parcela)  
✅ **Auditoria**: Rastreia quem, quando, por quê (em todos os campos críticos)  
✅ **Proteção Retroativa**: Lock de 30 dias evita erros operacionais em relatórios  

**Risco Operacional**: 🟢 BAIXO

---

## 📝 Deploy Checklist

- [ ] Executar migração 035
- [ ] Verificar colunas no BD
- [ ] Passar por todos os 7 testes
- [ ] Confirmar auditoria em logs
- [ ] Treinar tesoureiro nos novos cenários (especialmente cancelamento)
- [ ] Deploy em staging
- [ ] Deploy em produção

---

**Gerado**: 2026-04-27  
**Próxima revisão**: Após 1ª semana em produção  
**Contato**: Tesouraria ERP
