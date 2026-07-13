# Dados de demonstração — venda de cursos

Data: 2026-07-12

## O que é

Um comando artisan que gera (e depois remove, se quiser) um conjunto de
dados de teste representando o fluxo completo de venda de curso: da
Oportunidade no funil de vendas até o Projeto de acompanhamento da turma
já matriculada — pensado pra testar o ambiente de ponta a ponta sem sujar
o banco com dado real.

## Uso

```bash
# criar os dados de demonstração
php artisan demo:sales-data

# apagar tudo que foi criado (e só isso)
php artisan demo:sales-data --clear
```

Roda uma vez só — se tentar criar de novo sem limpar antes, o comando
recusa (evita duplicar).

## O que é criado

3 cursos completos, cada um com Produto (curso) + Pessoa (cliente
interessado) + Oportunidade (no funil de vendas, em estágios
diferentes) + Projeto de acompanhamento de turma (com 3 tarefas
espalhadas pelo Kanban), tudo ligado entre si (o Projeto referencia a
Oportunidade que o originou):

| Curso | Estágio da Oportunidade | Status do Projeto |
|---|---|---|
| Excel Avançado | Novo (`new`) | Planejamento |
| Inglês para o Turismo | Negociação (`negotiation`) | Em andamento |
| Gestão de Pequenos Negócios | Ganho/Matriculado (`won`) | Planejamento |

Cada Projeto vem com 3 tarefas nos 3 estágios do Kanban (uma feita, uma
em andamento, uma pendente), com datas espalhadas ao longo de ~3 semanas
— dá pra testar o Gantt direto sem precisar cadastrar nada na mão.

Todos os registros têm o prefixo **`[DEMO]`** no nome, pra ficar óbvio
visualmente na interface que é dado de teste, não dado real.

## Como funciona a remoção segura

O comando **não** apaga "tudo que tem `[DEMO]` no nome" (isso seria
frágil — e se um dia existir um dado real chamado parecido?). Em vez
disso, na hora de criar, ele grava um **manifesto**
(`storage/app/demo-sales-manifest.json`) com o ID exato de cada registro
criado. Na hora de limpar, ele usa esse manifesto pra apagar
**exatamente** esses registros — nem um a mais, nem um a menos — depois
apaga o próprio manifesto.

Se o manifesto não existir, o `--clear` avisa que não tem nada pra
remover, em vez de tentar adivinhar.

## Onde fica o código

`app/Console/Commands/DemoSalesData.php`
