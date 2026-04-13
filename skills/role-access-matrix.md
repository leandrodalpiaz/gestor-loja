# Role Access Matrix

Objetivo:
Definir e revisar acesso, visualizacao e prioridade de acoes por area do ERP.

Usar quando:
- houver duvida sobre quem pode ver uma rota
- for necessario decidir o que aparece para todos, por cargo ou por acesso especial
- uma tela precisar reduzir ruido mostrando primeiro o que importa para um cargo
- uma mudanca de front-end precisar respeitar permissoes e visualizacao por responsabilidade

Regras:
- preservar comportamento atual enquanto a matriz estiver sendo consolidada
- nao assumir lista fechada de cargos
- separar "ver", "agir" e "administrar"
- diferenciar acesso global, acesso por cargo e acesso especial
- sempre conferir a implementacao real antes de propor regra nova
- nao quebrar Telegram, miniapps ou rotas existentes

Fluxo:
1. identificar a area/rota analisada
2. verificar a permissao real no roteador/controlador
3. classificar o acesso:
   - comum a todos
   - por cargo
   - especial/restrito
4. mapear tipo de acao:
   - ver
   - criar
   - editar
   - aprovar
   - administrar
5. decidir o que deve aparecer primeiro na interface mobile para aquele perfil
6. registrar a decisao em `docs/matriz-acesso-erp.md` quando a regra ficar estavel

Entregaveis:
- leitura da permissao atual
- proposta de visualizacao por cargo
- impacto em menu, dashboard ou acoes da tela
- atualizacao incremental da matriz de acesso
