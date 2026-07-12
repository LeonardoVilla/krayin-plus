# Krayin Plus

Fork do [Krayin CRM](https://krayincrm.com) (Laravel) com módulos extras,
tradução pt-BR completa, login com Microsoft Entra ID e trilha de
auditoria — construído em cima do Krayin sem alterar o core, só
adicionando.

## O que tem a mais em relação ao Krayin original

| Módulo | O que é |
|---|---|
| **Faturas** | Extensão do módulo de Cotações — um campo `document_type` marca um registro como Cotação ou Fatura, com botão "Converter em Fatura" na listagem. Reaproveita 100% da tela/PDF de Cotações já existente. |
| **Chamados/Suporte** | Módulo dedicado (Chamado, Prioridade, Status) — formulários em Blade puro, sem dependência extra de build. |
| **Projetos** | Projeto (vinculado opcionalmente a uma Oportunidade) com Tarefas organizadas num **quadro Kanban** com drag-and-drop entre Pendente/Em Andamento/Concluída. |
| **Login com Microsoft Entra ID** | Botão "Entrar com a Microsoft" via OAuth2/Socialite, convivendo normalmente com o login usuário/senha padrão. |
| **Trilha de auditoria** | Todo `create`/`update`/`delete` de **qualquer** model é registrado automaticamente (quem, quando, quais campos mudaram) — sem precisar adicionar nada nos models novos, o listener é genérico. |
| **Tradução pt-BR** | Interface travada em português (sem seletor de idioma, sem fallback pra inglês em chave faltante). |

## Por que esses módulos e não outros

Comparado a CRMs mais "enterprise" (tipo SuiteCRM), o Krayin tem uma base
de vendas sólida (Leads, Cotações, Contatos, Produtos, Campanhas,
Automação/Workflows) mas não tinha Faturas, Chamados ou Projetos. A
prioridade foi sempre o menor esforço pra maior valor: Faturas quase não
custou nada (reaproveitou Cotações), Chamados foi um módulo simples do
zero, Projetos precisou de duas entidades relacionadas, e o Kanban só saiu
depois de confirmar que o build de front-end do projeto funcionava sem
atrito.

## Stack

Laravel 12 · Vue 3 + Vite · MySQL 8.0 · Tailwind CSS

## Instalação

```bash
composer install
npm install && npm run build            # assets do projeto raiz
cd packages/Webkul/Admin && npm install && npm run build   # assets do painel admin
cd ../../..
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Preencha o `.env` com as credenciais de banco e, se for usar login com
Microsoft, do App Registration no Entra ID (seção `ENTRA_*` no
`.env.example`).

⚠️ **Qualquer alteração nos componentes Vue do painel admin
(`packages/Webkul/Admin/src/Resources/assets`) exige rodar `npm run build`
de novo dentro de `packages/Webkul/Admin`** — o projeto raiz e o pacote
Admin têm builds Vite separados.

## Login com Microsoft Entra ID

- `app/Http/Controllers/Auth/EntraIdController.php` — redirect/callback
- `app/Providers/AppServiceProvider.php` — registro do provider Socialite
  (`socialiteproviders/microsoft-azure`)
- Busca o usuário por `entra_id` ou, se não achar, por `email` — se
  nenhum existir, **cria um usuário novo automaticamente** com o papel
  padrão (`role_id = 1`). Revise essa política antes de usar em produção
  (pode não ser o comportamento que você quer).

A Redirect URI cadastrada no Azure não pode ter query string — use
`{APP_URL}/admin/login/entra-id/callback` (caminho limpo).

## Auditoria

`App\Support\AuditLogger`, registrado via `eloquent.created/updated/
deleted: *` no `AppServiceProvider` — cobre automaticamente qualquer model
Eloquent, presente ou futuro, sem precisar tocar em cada model. Consulte
os registros em `app/Models/AuditLog.php` /
`app/Http/Controllers/AuditLogController.php`.

## Kanban de Tarefas

`packages/Webkul/Admin/src/Resources/views/projects/tasks/kanban.blade.php`
— componente Vue enxuto (3 colunas fixas) usando `vuedraggable`, a mesma
biblioteca que o Kanban de Leads do Krayin já usa. Endpoint de status:
`PUT admin/projects/{projectId}/tasks/{id}/status`.

## Licença e créditos

Baseado no [Krayin CRM](https://github.com/krayin/laravel-crm), projeto
open-source da [Webkul](https://webkul.com), licenciado sob MIT. Este fork
segue a mesma licença — contribuições são bem-vindas.
