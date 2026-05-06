# Role Access Matrix

Objetivo:
Definir e revisar acesso, visualização e prioridade de ações por área do ERP.

Usar quando:
- houver dúvida sobre quem pode ver uma rota
- for necessario decidir o que aparece para todos, por cargo ou por acesso especial
- uma tela precisar reduzir ruido mostrando primeiro o que importa para um cargo
- uma mudanca de front-end precisar respeitar permissoes e visualização por responsabilidade

Regras:
- preservar comportamento atual enquanto a matriz estiver sendo consolidada
- não assumir lista fechada de cargos
- separar "ver", "agir" e "administrar"
- diferenciar acesso global, acesso por cargo e acesso especial
- sempre conferir a implementação real antes de propor regra nova
- não quebrar Telegram, miniapps ou rotas existentes

Fluxo:
1. identificar a área/rota analisada
2. verificar a permissão real no roteador/controlador
3. classificar o acesso:
   - comum a todos
   - por cargo
   - especial/restrito
4. mapear tipo de ação:
   - ver
   - criar
   - editar
   - aprovar
   - administrar
5. decidir o que deve aparecer primeiro na interface mobile para aquele perfil
6. registrar a decisao em `docs/matriz-acesso-erp.md` quando a regra ficar estável

Entregaveis:
- leitura da permissão atual
- proposta de visualização por cargo
- impacto em menu, dashboard ou ações da tela
- atualizacao incremental da matriz de acesso
