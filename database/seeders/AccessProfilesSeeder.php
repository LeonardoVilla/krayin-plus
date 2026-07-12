<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\User\Models\Role;

/**
 * Matriz de perfis de acesso, construída em cima do sistema de
 * papéis/permissões nativo do Krayin (tabela roles, coluna JSON
 * `permissions`, aplicada pelo middleware Bouncer). Não usa nenhum pacote
 * externo (tipo Spatie Laravel-Permission) — o Krayin já resolve isso.
 *
 * O perfil Super Admin (permission_type = 'all') precisa existir
 * previamente (role_id=1) e não é tocado aqui.
 *
 * Idempotente: pode rodar várias vezes (updateOrCreate por nome).
 */
class AccessProfilesSeeder extends Seeder
{
    /**
     * Módulos de negócio (view/create/edit/delete), reaproveitados na
     * montagem de cada perfil abaixo.
     *
     * Importante: sempre que uma sub-chave é incluída (ex.: 'contacts.persons'),
     * a chave-mãe ('contacts') também precisa estar na lista. O construtor de
     * menu do Krayin (Webkul\Core\Menu::prepareMenuItems) quebra com
     * "Undefined array key 'name'" se um item filho sobrevive ao filtro de
     * permissão mas o item pai correspondente foi removido — o item pai vira
     * só um "container" sem os próprios dados (name/route/etc). Testado e
     * confirmado em 2026-07-12.
     */
    private const BUSINESS_VIEW = [
        'dashboard',
        'leads', 'leads.view',
        'quotes',
        'mail', 'mail.inbox', 'mail.draft', 'mail.outbox', 'mail.sent', 'mail.trash', 'mail.view',
        'activities',
        'contacts', 'contacts.persons', 'contacts.persons.view', 'contacts.organizations',
        'products', 'products.view',
        'support_tickets',
        'projects',
    ];

    private const BUSINESS_CREATE = [
        'leads.create', 'leads.create.quick-create',
        'quotes.create',
        'mail.compose', 'mail.compose.quick-create',
        'activities.create',
        'contacts.persons.create', 'contacts.persons.create.quick-create',
        'contacts.organizations.create', 'contacts.organizations.create.quick-create',
        'products.create', 'products.create.quick-create',
        'support_tickets.create',
        'projects.create',
    ];

    private const BUSINESS_EDIT = [
        'leads.edit',
        'quotes.edit', 'quotes.mail', 'quotes.print',
        'mail.edit',
        'activities.edit',
        'contacts.persons.edit',
        'contacts.organizations.edit',
        'products.edit',
        'support_tickets.edit',
        'projects.edit',
    ];

    private const BUSINESS_DELETE = [
        'leads.delete',
        'quotes.delete',
        'mail.delete',
        'activities.delete',
        'contacts.persons.delete',
        'contacts.organizations.delete',
        'products.delete',
        'support_tickets.delete',
        'projects.delete',
    ];

    /**
     * Visão só-leitura das áreas administrativas (não é gestão, é
     * visibilidade — usada por Gerente e Auditor).
     */
    private const ADMIN_VIEW = [
        'settings',
        'settings.user',
        'settings.user.users',
        'configuration',
    ];

    public function run(): void
    {
        $this->upsertRole(
            name: 'Gerente',
            description: 'Gestão completa das áreas operacionais (Oportunidades, Cotações, Contatos, Produtos, Chamados, Projetos, E-mail, Atividades), visibilidade de usuários e configurações, e acesso à auditoria. Não gerencia papéis/permissões nem exclui usuários — isso é exclusivo do Super Admin.',
            permissions: array_merge(
                self::BUSINESS_VIEW,
                self::BUSINESS_CREATE,
                self::BUSINESS_EDIT,
                self::BUSINESS_DELETE,
                self::ADMIN_VIEW,
                ['audit_log']
            ),
        );

        $this->upsertRole(
            name: 'Coordenador',
            description: 'Supervisão operacional do dia a dia: cria e edita em todos os módulos de negócio, mas não exclui registros nem tem acesso a configurações do sistema.',
            permissions: array_merge(
                self::BUSINESS_VIEW,
                self::BUSINESS_CREATE,
                self::BUSINESS_EDIT,
            ),
        );

        $this->upsertRole(
            name: 'Analista',
            description: 'Execução do trabalho operacional: cria e edita Oportunidades, Cotações, Contatos, Atividades, Chamados e Projetos. Não edita cadastro de Produtos (só visualiza) e não exclui nada.',
            permissions: array_merge(
                self::BUSINESS_VIEW,
                self::BUSINESS_CREATE,
                array_diff(self::BUSINESS_EDIT, ['products.edit']),
            ),
        );

        $this->upsertRole(
            name: 'Assistente',
            description: 'Apoio operacional: visualiza tudo, mas só cria em Atividades, Contatos, E-mail e Chamados (tarefas de suporte do dia a dia). Não mexe em Oportunidades/Cotações/Produtos/Projetos, não edita, não exclui.',
            permissions: array_merge(
                self::BUSINESS_VIEW,
                [
                    'activities.create',
                    'contacts.persons.create', 'contacts.persons.create.quick-create',
                    'mail.compose', 'mail.compose.quick-create',
                    'support_tickets.create',
                ],
            ),
        );

        $this->upsertRole(
            name: 'Estagiário',
            description: 'Acesso somente para acompanhar: visualiza os módulos operacionais, mas não cria, edita ou exclui nada.',
            permissions: self::BUSINESS_VIEW,
        );

        $this->upsertRole(
            name: 'Auditor',
            description: 'Perfil de auditoria interna/externa: visualiza todos os módulos de negócio, configurações do sistema e o painel de auditoria (log de criação/alteração/exclusão), mas não pode criar, editar ou excluir nada em lugar nenhum.',
            permissions: array_merge(
                self::BUSINESS_VIEW,
                self::ADMIN_VIEW,
                ['audit_log'],
            ),
        );
    }

    private function upsertRole(string $name, string $description, array $permissions): void
    {
        Role::updateOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'permission_type' => 'custom',
                'permissions' => array_values(array_unique($permissions)),
            ]
        );
    }
}
