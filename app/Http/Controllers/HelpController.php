<?php

namespace App\Http\Controllers;

class HelpController extends Controller
{
    public function index()
    {
        return response($this->render())->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function render(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Manual de Ajuda - Krayin CRM</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #f5f5f5; color: #333; }
    .wrap { max-width: 980px; margin: 0 auto; padding: 20px 24px 60px; }
    a.back { display:inline-block; margin-bottom: 12px; color: #443dff; text-decoration: none; }
    h1 { font-size: 22px; margin-bottom: 4px; }
    .subtitle { color: #666; font-size: 14px; margin-bottom: 24px; }
    nav.toc { background: #fff; border-radius: 6px; padding: 14px 18px; margin-bottom: 24px; }
    nav.toc h2 { font-size: 14px; margin: 0 0 8px; color: #443dff; }
    nav.toc ol { margin: 0; padding-left: 20px; }
    nav.toc li { margin-bottom: 4px; font-size: 13px; }
    nav.toc a { color: #1a1c26; text-decoration: none; }
    nav.toc a:hover { text-decoration: underline; }
    section { background: #fff; border-radius: 6px; padding: 18px 22px; margin-bottom: 16px; }
    section h2 { font-size: 17px; margin-top: 0; color: #1a1c26; border-bottom: 2px solid #f0f0f5; padding-bottom: 8px; }
    section h3 { font-size: 14px; margin-bottom: 4px; color: #443dff; }
    .step { display: flex; gap: 12px; margin-bottom: 14px; align-items: flex-start; }
    .step .num { flex: 0 0 26px; height: 26px; border-radius: 50%; background: #443dff; color: #fff; font-size: 13px; font-weight: bold; display: flex; align-items: center; justify-content: center; }
    .step .content { flex: 1; font-size: 13.5px; line-height: 1.5; }
    .step .content b { color: #1a1c26; }
    .badge-new { display: inline-block; background: #2e7d32; color: #fff; font-size: 10px; font-weight: bold; padding: 1px 7px; border-radius: 3px; margin-left: 6px; vertical-align: middle; }
    ul.feature-list { padding-left: 18px; font-size: 13.5px; line-height: 1.6; }
    ul.feature-list li { margin-bottom: 6px; }
    table.perfis { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
    table.perfis th, table.perfis td { border: 1px solid #eee; padding: 6px 10px; text-align: left; }
    table.perfis th { background: #1a1c26; color: #fff; }
    code { background: #f0f0f5; padding: 1px 6px; border-radius: 3px; font-size: 12.5px; }
    .note { background: #fff8e1; border-left: 4px solid #f9a825; padding: 10px 14px; font-size: 13px; border-radius: 0 4px 4px 0; margin-top: 10px; }
    .warn { background: #ffebee; border-left: 4px solid #c62828; padding: 10px 14px; font-size: 13px; border-radius: 0 4px 4px 0; margin-top: 10px; }
</style>
</head>
<body>
<div class="wrap">

<a class="back" href="/admin/dashboard">&laquo; Voltar ao painel</a>
<h1>Manual de Ajuda — Krayin CRM (SENAC-MT)</h1>
<div class="subtitle">Guia de uso do sistema: por onde começar e como usar cada funcionalidade, incluindo as novidades mais recentes.</div>

<nav class="toc">
    <h2>Índice</h2>
    <ol>
        <li><a href="#primeiros-passos">Primeiros passos — sequência recomendada</a></li>
        <li><a href="#oportunidades">Oportunidades (Leads) e Cotações</a></li>
        <li><a href="#fatura">Cotação → Fatura <span class="badge-new">Novo</span></a></li>
        <li><a href="#chamados">Chamados / Suporte <span class="badge-new">Novo</span></a></li>
        <li><a href="#projetos">Projetos, Kanban e Gantt <span class="badge-new">Novo</span></a></li>
        <li><a href="#acl">Perfis de acesso e permissões <span class="badge-new">Novo</span></a></li>
        <li><a href="#simular">Simular usuário <span class="badge-new">Novo</span></a></li>
        <li><a href="#auditoria">Auditoria — quem fez o quê</a></li>
        <li><a href="#contatos">Contatos, Organizações e Produtos</a></li>
        <li><a href="#dados-demo">Dados de demonstração para testes <span class="badge-new">Novo</span></a></li>
    </ol>
</nav>

<section id="primeiros-passos">
    <h2>1. Primeiros passos — sequência recomendada</h2>
    <p style="font-size:13.5px;color:#666;margin-top:-6px;">Siga esta ordem na primeira configuração do sistema. Cada etapa depende da anterior.</p>

    <div class="step"><div class="num">1</div><div class="content">
        <b>Usuários e perfis de acesso.</b> Vá em <code>Configurações → Usuário → Papéis</code> e confira os perfis já existentes (Super Admin, Gestor, Vendas, Suporte, Projetos, Auditor). Depois, em <code>Configurações → Usuário → Usuários</code>, cadastre cada colaborador e vincule ao papel correto. Veja a seção <a href="#acl">Perfis de acesso</a> abaixo para a matriz completa.
    </div></div>

    <div class="step"><div class="num">2</div><div class="content">
        <b>Funil de vendas (Oportunidades).</b> Em <code>Configurações → Oportunidade</code>, revise os <b>Estágios do funil</b>, <b>Origens</b> e <b>Tipos</b> — esses valores aparecem depois na tela de Oportunidades. Ajuste os nomes dos cursos/produtos oferecidos.
    </div></div>

    <div class="step"><div class="num">3</div><div class="content">
        <b>Produtos (cursos).</b> Cadastre em <code>Produtos</code> os cursos ofertados, com SKU único, preço e descrição. Eles serão usados nas Cotações.
    </div></div>

    <div class="step"><div class="num">4</div><div class="content">
        <b>Contatos e Organizações.</b> Cadastre pessoas (potenciais alunos) e organizações (empresas parceiras) em <code>Contatos</code>. Isso alimenta as Oportunidades e Cotações.
    </div></div>

    <div class="step"><div class="num">5</div><div class="content">
        <b>Comece a operação diária:</b> registre <b>Oportunidades</b> (interessados no curso) → converta em <b>Cotação</b> quando fechar o valor → gere a <b>Fatura</b> quando confirmar a matrícula → abra um <b>Projeto</b> para acompanhar a turma → use <b>Chamados</b> para dúvidas e suporte pós-matrícula.
    </div></div>

    <div class="note">Dica: se quiser testar o fluxo completo sem usar dados reais, veja a seção <a href="#dados-demo">Dados de demonstração</a> — ela cria (e depois remove) 3 exemplos completos de venda de curso.</div>
</section>

<section id="oportunidades">
    <h2>2. Oportunidades (Leads) e Cotações</h2>
    <ul class="feature-list">
        <li><b>Oportunidades</b>: representam um interessado em um curso, dentro do funil de vendas (Novo → Contato → Negociação → Ganho/Perdido). Arraste os cartões entre as colunas do Kanban conforme o andamento.</li>
        <li>Cada Oportunidade pode ter <b>Atividades</b> (ligações, e-mails, tarefas, reuniões) associadas, para registrar o histórico de contato.</li>
        <li><b>Cotações</b> são geradas a partir de uma Oportunidade, com os produtos (cursos), quantidades e valores negociados.</li>
    </ul>
</section>

<section id="fatura">
    <h2>3. Cotação → Fatura <span class="badge-new">Novo</span></h2>
    <ul class="feature-list">
        <li>Toda Cotação agora pode ser marcada com o tipo <b>Fatura</b>, indicando que a venda foi confirmada e a matrícula está fechada.</li>
        <li>Abra a Cotação, altere o tipo para <b>Fatura</b> e salve — isso não cria um registro novo, apenas identifica que aquela cotação virou uma cobrança confirmada.</li>
        <li>Use o filtro por tipo na listagem de Cotações para separar rapidamente o que ainda é proposta do que já é fatura fechada.</li>
    </ul>
</section>

<section id="chamados">
    <h2>4. Chamados / Suporte <span class="badge-new">Novo</span></h2>
    <ul class="feature-list">
        <li>Módulo para registrar dúvidas, problemas e solicitações de alunos/clientes já matriculados (pós-venda).</li>
        <li>Cada chamado tem <b>título</b>, <b>descrição</b>, <b>prioridade</b>, <b>status</b> (aberto, em andamento, resolvido, fechado) e pode ser vinculado a um Contato.</li>
        <li>Acesse pelo menu <code>Chamados</code> na barra lateral. A visibilidade e edição dependem do perfil de acesso do usuário (veja <a href="#acl">Perfis de acesso</a>).</li>
    </ul>
</section>

<section id="projetos">
    <h2>5. Projetos, Kanban e Gantt <span class="badge-new">Novo</span></h2>
    <ul class="feature-list">
        <li><b>Projetos</b> servem para acompanhar uma turma/curso já matriculado do início ao fim, com uma lista de <b>Tarefas</b>.</li>
        <li>Cada Projeto pode ser vinculado à Oportunidade que o originou, mantendo o histórico completo da venda até a execução.</li>
        <li><b>Quadro Kanban</b>: as tarefas do projeto ficam organizadas em colunas (Pendente, Em andamento, Concluída) — arraste os cartões para atualizar o status.</li>
        <li><b>Gráfico de Gantt</b>: dentro do Projeto, use a aba de Gantt para visualizar as tarefas na linha do tempo, com datas de início/fim. Arraste as barras para ajustar prazos diretamente no gráfico.</li>
    </ul>
</section>

<section id="acl">
    <h2>6. Perfis de acesso e permissões <span class="badge-new">Novo</span></h2>
    <p style="font-size:13.5px;">Cada usuário recebe um <b>Papel</b> em <code>Configurações → Usuário → Papéis</code>, que define o que ele pode ver e fazer. Perfis padrão já configurados:</p>
    <table class="perfis">
        <tr><th>Perfil</th><th>Acesso</th></tr>
        <tr><td>Super Admin</td><td>Acesso total a todos os módulos e configurações, incluindo gestão de usuários/papéis.</td></tr>
        <tr><td>Gestor</td><td>Gestão completa das áreas operacionais (Oportunidades, Cotações, Contatos, Produtos, Chamados, Projetos, E-mail, Atividades) e acesso à Auditoria. Não gerencia papéis nem exclui usuários.</td></tr>
        <tr><td>Vendas</td><td>Foco em Oportunidades, Cotações, Contatos e Produtos.</td></tr>
        <tr><td>Suporte</td><td>Foco em Chamados e Contatos.</td></tr>
        <tr><td>Projetos</td><td>Foco em Projetos e suas Tarefas (Kanban/Gantt).</td></tr>
        <tr><td>Auditor</td><td>Visualiza todos os módulos e o painel de Auditoria, mas não pode criar, editar ou excluir nada em nenhum lugar (somente leitura).</td></tr>
    </table>
    <div class="note">Para criar um novo perfil personalizado, vá em <code>Configurações → Usuário → Papéis → Criar Papel</code> e marque quais ações (ver, criar, editar, excluir) cada módulo permite.</div>
</section>

<section id="simular">
    <h2>7. Simular usuário <span class="badge-new">Novo</span></h2>
    <ul class="feature-list">
        <li>Disponível apenas para <b>Super Admin</b>, em <code>Configurações → Usuário → Usuários</code>, no menu de ações de cada usuário ("Simular").</li>
        <li>Permite ver o sistema exatamente como aquele usuário vê — útil para diagnosticar problemas de acesso ou dúvidas relatadas.</li>
        <li><b>Importante:</b> durante a simulação, o acesso é <b>somente leitura</b> — não é possível criar, editar ou excluir nada, mesmo que o usuário simulado normalmente tivesse permissão. Isso protege contra alterações acidentais feitas "no lugar" de outra pessoa.</li>
        <li>Para encerrar a simulação e voltar ao seu próprio usuário, use o botão "Encerrar simulação" que aparece no topo da tela durante a simulação.</li>
    </ul>
    <div class="warn">Simular usuário não deve ser usado para realizar tarefas em nome de outra pessoa — é uma ferramenta de suporte/diagnóstico, por isso as ações de escrita são bloqueadas.</div>
</section>

<section id="auditoria">
    <h2>8. Auditoria — quem fez o quê</h2>
    <ul class="feature-list">
        <li>Acesse em <code>/admin/audit-log</code> (link disponível para Super Admin, Gestor e Auditor).</li>
        <li>Mostra todo <b>registro criado, alterado ou excluído</b> no sistema, com data/hora, usuário responsável, módulo afetado e os campos que mudaram (antes → depois).</li>
        <li>Use os filtros de módulo, ação e usuário para localizar uma alteração específica rapidamente.</li>
    </ul>
</section>

<section id="contatos">
    <h2>9. Contatos, Organizações e Produtos</h2>
    <ul class="feature-list">
        <li><b>Contatos → Pessoas</b>: cadastro de indivíduos (alunos, interessados).</li>
        <li><b>Contatos → Organizações</b>: empresas parceiras ou que enviam grupos de alunos.</li>
        <li><b>Produtos</b>: catálogo de cursos oferecidos, usado nas Cotações.</li>
    </ul>
</section>

<section id="dados-demo">
    <h2>10. Dados de demonstração para testes <span class="badge-new">Novo</span></h2>
    <p style="font-size:13.5px;">Para testar o ambiente sem usar dados reais, a equipe de TI pode rodar no servidor:</p>
    <p><code>php artisan demo:sales-data</code></p>
    <p style="font-size:13.5px;">Isso cria 3 exemplos completos de venda de curso (Oportunidade → Projeto → Tarefas), todos marcados com o prefixo <code>[DEMO]</code> no nome para não se confundirem com dados reais. Para remover tudo o que foi criado:</p>
    <p><code>php artisan demo:sales-data --clear</code></p>
    <div class="note">Este comando é operado pela equipe de TI via terminal — não há botão na interface para isso, propositalmente, para evitar geração acidental de dados fictícios por usuários comuns. Em ambiente de produção, o comando exige uma confirmação explícita antes de criar qualquer dado.</div>
</section>

</div>
</body>
</html>
HTML;
    }
}
