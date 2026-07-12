# Permissões (ACL) para Chamados e Projetos

Data: 2026-07-12

## O problema

O Krayin já tem um sistema completo de papéis/permissões (Configurações >
Usuários > Funções): cada papel tem `permission_type` = `all` (acesso
total) ou `custom` (permissões granulares marcadas numa árvore de
checkboxes, por módulo).

Só que essa árvore é montada a partir de um arquivo de configuração
(`packages/Webkul/Admin/src/Config/acl.php`) que mapeia cada permissão a um
ou mais nomes de rota do Laravel. Os módulos **Chamados** e **Projetos**,
por terem sido criados do zero nesta customização, não tinham entradas
nesse arquivo — ou seja, existiam no menu e nas rotas, mas **não existiam
para o sistema de permissões**. Na prática, qualquer usuário autenticado
(independente do papel) conseguia acessar os dois módulos livremente,
porque o middleware que aplica as regras (`Webkul\Admin\Http\Middleware\Bouncer`,
aplicado globalmente a todas as rotas `/admin/*`) simplesmente não
encontrava a rota no mapa de permissões e deixava passar.

## A solução

Registrar Chamados e Projetos na árvore de ACL do mesmo jeito que os
módulos nativos (Oportunidades, Cotações etc.) já fazem — sem inventar um
mecanismo novo, só preenchendo a lacuna no mecanismo que já existe.

### Arquivos alterados

| Arquivo | O que mudou |
|---|---|
| `packages/Webkul/Admin/src/Config/acl.php` | Adiciona os nós `support_tickets` (+ `.create`, `.edit`, `.delete`) e `projects` (+ `.create`, `.edit`, `.delete`), cada um mapeado às rotas reais do módulo |
| `packages/Webkul/Admin/src/Resources/lang/pt_BR/app.php` | Adiciona `'support-tickets' => 'Chamados'` e `'projects' => 'Projetos'` na seção `acl` (rótulos exibidos na árvore de permissões) |
| `packages/Webkul/Admin/src/Resources/views/support-tickets/index.blade.php` | Botão "Criar" só aparece se `bouncer()->hasPermission('support_tickets.create')` |
| `packages/Webkul/Admin/src/Resources/views/projects/index.blade.php` | Botão "Criar" só aparece se `bouncer()->hasPermission('projects.create')` |

Nenhuma mudança em controller foi necessária: o bloqueio de fato (o que
importa de verdade, porque é a barreira do lado do servidor) já é feito
automaticamente pelo middleware global assim que a rota existe no
`acl.php` — o mesmo motor que já protegia Oportunidades e Cotações agora
protege Chamados e Projetos.

### Mapeamento de permissões

**Chamados** (`support_tickets`)
- `support_tickets` — ver a lista (`admin.support_tickets.index`)
- `support_tickets.create` — criar (`admin.support_tickets.create/store`)
- `support_tickets.edit` — editar (`admin.support_tickets.edit/update`)
- `support_tickets.delete` — excluir (`admin.support_tickets.delete/mass_delete`)

**Projetos** (`projects`)
- `projects` — ver a lista (`admin.projects.index`)
- `projects.create` — criar (`admin.projects.create/store`)
- `projects.edit` — editar, inclui abrir o Kanban/Gantt e mexer nas tarefas
  (`admin.projects.edit/update`, `admin.projects.tasks.*` exceto delete)
- `projects.delete` — excluir projeto ou tarefa
  (`admin.projects.delete/mass_delete`, `admin.projects.tasks.delete`)

`projects.edit` cobre as tarefas de propósito porque, na nossa
implementação, o Kanban e o Gantt vivem dentro da tela de edição do
projeto (não são telas separadas) — só faz sentido dar acesso a "editar
tarefas" pra quem já pode editar o projeto.

## Como usar (Configurações > Usuários > Funções)

1. Criar um papel novo com `permission_type = custom`.
2. Na árvore de permissões, marcar "Chamados" e/ou "Projetos" (e os
   sub-itens Criar/Editar/Excluir conforme o que a pessoa deve poder
   fazer).
3. Atribuir esse papel ao usuário em Configurações > Usuários.

Quem não tiver a permissão de um módulo nem vê ele no menu lateral (o
`Menu.php` do Krayin já filtra os itens por `bouncer()->hasPermission()`
automaticamente) e, se tentar acessar a URL direto, recebe erro 401.

## Teste realizado (2026-07-12)

Criado um papel de teste `custom` só com `dashboard`, `support_tickets`,
`support_tickets.create`, `support_tickets.edit` (sem nada de `projects`),
e um usuário nesse papel. Login feito via `curl` (login programático,
sessão + cookie), depois:

- `GET /admin/support-tickets` → **200** (permitido, como esperado)
- `GET /admin/support-tickets/create` → **200** (permitido)
- `GET /admin/projects` → **401** (bloqueado, como esperado)

Papel e usuário de teste foram removidos depois da validação.

## Limitação conhecida

Os botões de exclusão em massa nos grids (datagrid) de Chamados/Projetos
não são escondidos por permissão na UI — isso é consistente com o
restante do Krayin (Oportunidades e Cotações também não escondem o botão
de excluir da grid por permissão, só a rota é protegida no back-end).
Quem não tiver `*.delete` vê o botão mas recebe 401 ao tentar excluir.
