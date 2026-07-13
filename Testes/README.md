# Testes automatizados — Fase 1 e Fase 2

Data: 2026-07-12

## Custo

Zero em dinheiro — o Krayin já vem com PHPUnit/Pest instalado
(`phpunit.xml`, `tests/Pest.php`, 3 testes de exemplo já existiam antes
desta etapa). O trabalho aqui foi 100% escrever os testes e ajustar a
infraestrutura pra rodar isolado do banco de desenvolvimento.

## Infraestrutura

- **Banco de teste dedicado** (`krayin_testing`), separado do banco de
  desenvolvimento (`krayin`) — configurado via `<php><env>` no
  `phpunit.xml`. Isso é crítico porque os testes usam `RefreshDatabase`
  (recria o schema do zero a cada execução) — se apontasse pro banco de
  dev, cada `php artisan test` apagaria os dados reais.
- **`tests/TestCase.php`** — todo teste roda `RefreshDatabase` (migra o
  banco de teste do zero, cada teste isolado numa transação desfeita no
  final) e semeia automaticamente o `KrayinDatabaseSeeder` (dados padrão
  do Krayin: países, funil de vendas etc.) + o `AccessProfilesSeeder`
  (matriz dos 7 perfis de acesso).
- **`tests/Pest.php`** — adicionado o helper `makeUser(array $attributes)`.
  Não dá pra usar `User::factory()` porque a `UserFactory` padrão do
  Laravel aponta pro model genérico `App\Models\User`, que não é o mesmo
  model que o Krayin usa (`Webkul\User\Models\User`) — `makeUser()` cria
  direto com `Model::create()`.

## Rodando os testes

```bash
cd /var/www/krayin
vendor/bin/pest
```

## O que está coberto (Fase 1)

| Arquivo | O que testa |
|---|---|
| `AccessControlTest.php` | Cada um dos 7 perfis acessando/sendo bloqueado nos módulos certos (Estagiário só visualiza, Auditor vê tudo mas não cria nada, Gerente não gerencia funções, Assistente só cria Chamados, Analista não edita Produtos, Super Admin acessa tudo) |
| `SelfProtectionTest.php` | Super Admin não consegue excluir a própria conta nem rebaixar a própria função (nem editando a função, nem trocando o próprio `role_id`) — mas consegue mexer normalmente na conta de outros |
| `ImpersonationTest.php` | Simular e voltar funciona; usuário sem acesso total não simula ninguém; não dá pra simular a si mesmo; não dá pra simular um segundo usuário com uma simulação já ativa; início/fim ficam na auditoria |
| `SupportTicketTest.php` | CRUD de Chamados (criar, editar, excluir), validação de campo obrigatório, e que a criação gera registro de auditoria |
| `ProjectTest.php` | CRUD de Projetos, validação de datas (fim não pode ser antes do início), e que Coordenador cria/edita mas não exclui (ACL na prática) |

**26 testes, 73 asserções, todos passando.**

## O que está coberto (Fase 2 — regressão dos módulos customizados)

| Arquivo | O que testa |
|---|---|
| `InvoiceConversionTest.php` | Converter Cotação em Fatura (troca de `document_type`) e que isso fica registrado na auditoria |
| `ProjectKanbanGanttTest.php` | Endpoint do Kanban (tarefas agrupadas por status), atualização de status via drag-and-drop, endpoint do Gantt (formato Frappe Gantt, cálculo de progresso por status), atualização de datas via arrastar/redimensionar, e as validações de cada um (status inválido, data de término antes da de início) |
| `AutomaticAuditTest.php` | Confirma que o sistema de auditoria (listeners globais no `AppServiceProvider`) cobre **qualquer** model automaticamente — testado com Pessoa, Empresa e Usuário (não são os módulos que a auditoria foi "feita para", é justamente pra provar que funciona em qualquer um sem precisar adicionar código por model), incluindo: diff correto de campo alterado, não registra quando nada muda de fato, e registra quem fez a ação |

**Total combinado: 39 testes, 106 asserções, todos passando.**

## Armadilhas encontradas

### 1 — Nome do papel muda por idioma

Os nomes de papéis vêm de arquivos de tradução (`trans('installer::app...')`)
— o papel "Administrator" no banco de desenvolvimento (instalado
originalmente em inglês) tem esse nome literal, mas ao rodar os seeders
num banco novo com `app.locale=pt_BR`, o mesmo seeder cria o papel como
**"Administrador"**. Por isso os testes nunca buscam esse papel pelo nome
— sempre por `Role::where('permission_type', 'all')`, que é estável
independente de idioma/tradução.

### 2 — `.env.testing` quebra o segundo teste que espera erro HTTP (bug sério)

Esta foi a mais difícil de rastrear da sessão inteira. Sintoma: rodando
**um teste sozinho** que espera `401`, passava. Rodando **dois testes no
mesmo arquivo** (o primeiro podia ser literalmente `expect(1)->toBe(1)`,
sem nenhuma requisição HTTP), o **segundo** teste que fizesse uma
requisição esperando `401` **sempre falhava**, recebendo `200` com corpo
vazio — mesmo com a lógica de permissão comprovadamente correta (testado
com `error_log()` dentro do próprio `Bouncer::allow()`: `hasPermission`
calculava `false` corretamente, o `abort(401)` disparava de verdade —
confirmado rodando com `withoutExceptionHandling()`, que fez o teste
reportar a exceção real em vez de engolir).

**Causa isolada por eliminação:** o problema desaparecia completamente ao
tirar o arquivo `.env.testing` da equação e colocar as variáveis de
ambiente direto no `<php><env>` do `phpunit.xml` (que o PHPUnit aplica
antes do Laravel sequer inicializar, diferente do `.env.testing`, que o
Laravel carrega via Dotenv durante o próprio boot da aplicação). Alguma
interação entre o carregamento do `.env.testing` e o pipeline de
renderização de exceção HTTP (possivelmente envolvendo o Laravel
Debugbar, que aparecia no meio do stack de middlewares) faz a **segunda**
exceção HTTP lançada no mesmo processo PHP se perder e virar uma resposta
200 vazia. Não vale a pena investigar mais fundo — o Laravel/Debugbar em
si não é nosso código, e a solução (variáveis no `phpunit.xml`) é
robusta e documentada.

**Efeito colateral de segurança:** como o `phpunit.xml` é versionado no
git, não dá pra usar a senha real do usuário de banco da aplicação nele.
Solução: criado um usuário MySQL dedicado **`krayin_test`**, com
privilégio **só** no banco `krayin_testing` (não alcança o banco `krayin`
real), com senha própria (`TesteAutomatizado2026`) — essa sim pode ficar
no `phpunit.xml` versionado, porque o pior que alguém faz com ela é
mexer num banco de teste que já é recriado do zero a cada execução.

### 3 — `leads.create` e `leads.create.quick-create` mapeiam pra mesma rota

No `acl.php` de fábrica do Krayin, as chaves `leads.create` e
`leads.create.quick-create` apontam para as **mesmas** rotas
(`admin.leads.create`, `admin.leads.store`). Como o `Bouncer` monta um
mapa `rota => chave` (não o contrário), a chave que aparece **depois** no
array (`leads.create.quick-create`) sobrescreve a primeira — ou seja,
checar a permissão de criar Oportunidade na prática sempre valida contra
`leads.create.quick-create`, nunca contra `leads.create`. Isso é um bug
pré-existente do Krayin (não introduzido por nós). Não corrigimos porque
nossa matriz de perfis sempre concede as duas chaves juntas — não afeta
o resultado prático, só registramos aqui pra não reaparecer como
mistério numa investigação futura.

## Próxima fase (não iniciada)

- **Fase 3** — cobertura ampla dos módulos nativos do Krayin (Oportunidades,
  Cotações, Contatos, Produtos) — menor prioridade, é código de terceiros
  já mantido pelo Krayin upstream.
