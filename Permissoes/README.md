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

---

# Matriz de perfis de acesso, autoproteção e simulação de usuário

Data: 2026-07-12 (segunda etapa, a pedido do usuário)

## Por que não usamos um pacote como o Spatie Laravel-Permission

O Krayin já resolve o mesmo problema que o Spatie Permission resolve:
papel único por usuário (`users.role_id`), permissões granulares guardadas
em JSON (`roles.permissions`), e um motor de aplicação (`Bouncer`, ver
seção anterior deste README) que já cobre middleware de rota, filtro de
menu e checagem em Blade. Trocar por um pacote externo significaria
reescrever essa integração inteira só para ganhar recursos (múltiplos
papéis por usuário, "teams") que não foram pedidos. Construímos em cima
do que já existe.

## Os 7 perfis

| Perfil | `permission_type` | O que pode fazer |
|---|---|---|
| **Super Admin (TI)** | `all` | Acesso total. Já existia (`role_id=1`, papel "Administrator"). Não foi tocado por este trabalho, só ganhou proteção contra autorrebaixamento (ver abaixo). |
| **Gerente** | `custom` | CRUD completo em todos os módulos de negócio (Oportunidades, Cotações, Contatos, Produtos, Atividades, E-mail, Chamados, Projetos) + visibilidade de usuários/configurações + acesso à Auditoria. Não gerencia papéis nem exclui usuários. |
| **Coordenador** | `custom` | Cria e edita em todos os módulos de negócio. Não exclui, não vê configurações. |
| **Analista** | `custom` | Cria e edita na maioria dos módulos, mas não edita cadastro de Produtos (só visualiza). Não exclui. |
| **Assistente** | `custom` | Só visualiza + cria em Atividades, Contatos, E-mail e Chamados (apoio operacional). Não edita, não exclui. |
| **Estagiário** | `custom` | Só visualiza. Nenhuma criação/edição/exclusão em lugar nenhum. |
| **Auditor** | `custom` | Visualiza tudo (inclusive Configurações e o painel de Auditoria), mas não pode criar/editar/excluir nada em lugar nenhum. |

A matriz completa de permissões por perfil está em código, comentada, em
`database/seeders/AccessProfilesSeeder.php` — mais fácil de auditar ali do
que descrever aqui campo a campo. Rodar `php artisan db:seed
--class=AccessProfilesSeeder` cria ou atualiza (idempotente, por nome) os
6 papéis custom.

**Isso é um ponto de partida, não uma decisão final.** Qualquer perfil
pode ser ajustado depois normalmente pela tela Configurações > Funções —
a árvore de checkboxes reflete exatamente essas mesmas permissões.

### Armadilha descoberta e corrigida durante os testes

O construtor de menu do Krayin (`Webkul\Core\Menu::prepareMenuItems`)
quebra com `Undefined array key "name"` se um papel tem permissão para um
item filho (ex.: `contacts.persons`) mas não tem permissão para a
chave-mãe (`contacts`) — o item pai "sobra" no menu como um container
vazio sem os próprios dados. **Regra ao montar qualquer permissão nova:
sempre que uma sub-chave for concedida, a chave-mãe também precisa ser
concedida**, mesmo que a intenção seja só "dar acesso ao filho". Isso já
está aplicado corretamente na matriz atual (comentado no próprio seeder).

## Auditor e o painel de Auditoria — bug corrigido de brinde

Ao testar o perfil Auditor acessando `/admin/audit-log`, apareceu um erro
500 pré-existente (`Array to string conversion` em
`app/Http/Controllers/AuditLogController.php:46`) — acontecia sempre que
um registro de auditoria tinha um campo alterado com valor não-escalar
(ex.: um array). Esse bug já existia desde que o painel de auditoria foi
criado (sessão anterior), só nunca tinha sido percebido porque ninguém
tinha testado a página com dados reais o suficiente. Corrigido trocando o
cast direto por um helper que serializa arrays em JSON antes de exibir.

## Super Admin não pode tirar o próprio acesso

Dois lugares foram travados (`packages/Webkul/Admin/src/Http/Controllers/Settings/`):

- **`UserController::destroy`** — bloqueia a exclusão da própria conta
  (`400 Bad Request` com mensagem clara), independente do papel.
- **`UserController::update`** — se o usuário logado está editando a
  própria conta e seu papel atual é `all`, o `role_id` enviado no
  formulário é ignorado e mantido como está (não dá pra trocar o próprio
  papel pra um menos privilegiado).
- **`RoleController::update`** — se o usuário logado está editando a
  função que ele mesmo usa e ela é `all`, o `permission_type` enviado é
  ignorado e forçado a continuar `all` (não dá pra rebaixar a própria
  função enquanto ela está em uso).

Testado com requisições reais (login programático + token CSRF via cookie
`XSRF-TOKEN`): tentativa de autoexclusão → `400`; tentativa de editar a
função "Administrator" (que o Super Admin de teste usa) para
`permission_type=custom` → aceita com `302` (redirect normal), mas o
banco confirma que a função **continuou `all`** depois — a trava ignorou
silenciosamente o campo malicioso/acidental sem quebrar o fluxo do
formulário.

**Limitação consciente:** essas travas protegem contra o próprio usuário
se autolimitar sem querer (ou por engano). Elas não impedem que **outro**
Super Admin remova o acesso de um colega — isso é uma decisão de design:
o pedido original foi "não tirar os *próprios* acessos", não "nenhum
Super Admin pode mexer no outro".

## Simular usuário (impersonation)

Não existia no Krayin — construído do zero, sem pacote externo (é pouco
código: trocar a sessão de autenticação e guardar quem é o usuário real).

- **Quem pode simular:** só quem está com `permission_type=all`. A
  checagem é fixa no controller (`ImpersonateController::start`), não
  passa pela árvore de ACL — não dá pra conceder essa permissão a um
  papel custom pela tela de Funções, é proposital.
- **Como usar:** Configurações > Usuários > ícone "Simular" (👁) na linha
  do usuário desejado. Não aparece um clique funcional na própria linha
  (retorna erro amigável se tentado).
- **O que acontece:** a sessão do admin real fica guardada
  (`session('impersonator_id')`), o login troca para o usuário-alvo via
  `loginUsingId()`, e uma barra amarela fixa aparece no topo de toda tela
  admin: "Você está navegando como X (simulação iniciada por Y) — Voltar
  para o meu usuário".
- **Como volta:** clicar na barra amarela, ou ir direto em
  `/admin/settings/users/impersonate/stop`.
- **Registro:** início e fim da simulação são gravados em `audit_logs`
  (`model_type = 'Impersonation'`), com o usuário real, o usuário
  simulado e o IP. A tabela `audit_logs` não tem uma coluna própria para
  isso, então é registrado como ação `update` com a direção
  (`impersonate_start`/`impersonate_stop`) dentro de `field_changes`.
- **Não permite simulação encadeada:** se já existe uma simulação ativa
  na sessão, tentar simular outro usuário é bloqueado com mensagem de
  erro — precisa voltar pro usuário real antes.

### Testado (2026-07-12)

Fluxo completo via requisições reais (login → simular admin@example.com →
banner aparece → `/admin/settings/roles` acessível, confirmando a troca
de sessão → voltar → banner some → 2 registros aparecem em `audit_logs`
com `model_type='Impersonation'`).

## Arquivos novos/alterados nesta etapa

| Arquivo | O que mudou |
|---|---|
| `database/seeders/AccessProfilesSeeder.php` | Novo — matriz dos 6 papéis custom |
| `database/seeders/DatabaseSeeder.php` | Chama o seeder acima |
| `packages/Webkul/Admin/src/Config/acl.php` | Adiciona nó `audit_log` |
| `packages/Webkul/Admin/src/Resources/lang/pt_BR/app.php` | Label "Auditoria" |
| `packages/Webkul/Admin/src/Http/Controllers/Settings/ImpersonateController.php` | Novo — start/stop da simulação |
| `packages/Webkul/Admin/src/Http/Controllers/Settings/UserController.php` | Trava de autoexclusão e autorebaixamento |
| `packages/Webkul/Admin/src/Http/Controllers/Settings/RoleController.php` | Trava de autorebaixamento da própria função |
| `packages/Webkul/Admin/src/DataGrids/Settings/UserDataGrid.php` | Botão "Simular" (só visível pra `permission_type=all`) |
| `packages/Webkul/Admin/src/Routes/Admin/settings-routes.php` | Rotas de impersonate |
| `packages/Webkul/Admin/src/Resources/views/components/layouts/index.blade.php` | Banner amarelo de simulação ativa |
| `app/Http/Controllers/AuditLogController.php` | Correção do bug `Array to string conversion` |
