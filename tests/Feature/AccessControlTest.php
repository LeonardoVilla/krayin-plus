<?php

use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * Cria um usuário de teste com o papel informado (da matriz de perfis
 * criada pelo AccessProfilesSeeder, ou "Administrator" que já vem do
 * seeder padrão do Krayin).
 */
function userWithRole(string $roleName): User
{
    $role = Role::where('name', $roleName)->firstOrFail();

    return makeUser([
        'role_id' => $role->id,
        'status' => 1,
    ]);
}

it('estagiário vê a lista de oportunidades mas não pode criar', function () {
    $user = userWithRole('Estagiário');

    test()->actingAs($user)->get(route('admin.leads.index'))->assertOk();
    test()->actingAs($user)->get(route('admin.leads.create'))->assertStatus(401);
});

it('estagiário não acessa configurações de usuários nem auditoria', function () {
    $user = userWithRole('Estagiário');

    test()->actingAs($user)->get(route('admin.settings.users.index'))->assertStatus(401);
    test()->actingAs($user)->get(route('admin.audit_log.index'))->assertStatus(401);
});

it('auditor vê tudo (inclusive configurações e auditoria) mas não pode criar nada', function () {
    $user = userWithRole('Auditor');

    test()->actingAs($user)->get(route('admin.leads.index'))->assertOk();
    test()->actingAs($user)->get(route('admin.settings.users.index'))->assertOk();
    test()->actingAs($user)->get(route('admin.audit_log.index'))->assertOk();
    test()->actingAs($user)->get(route('admin.leads.create'))->assertStatus(401);
    test()->actingAs($user)->get(route('admin.support_tickets.create'))->assertStatus(401);
});

it('gerente cria oportunidades e vê auditoria, mas não gerencia funções', function () {
    $user = userWithRole('Gerente');

    test()->actingAs($user)->get(route('admin.leads.create'))->assertOk();
    test()->actingAs($user)->get(route('admin.audit_log.index'))->assertOk();
    test()->actingAs($user)->get(route('admin.settings.roles.index'))->assertStatus(401);
});

it('assistente só cria chamados, não cria projetos nem oportunidades', function () {
    $user = userWithRole('Assistente');

    test()->actingAs($user)->get(route('admin.support_tickets.index'))->assertOk();
    test()->actingAs($user)->get(route('admin.support_tickets.create'))->assertOk();
    test()->actingAs($user)->get(route('admin.projects.create'))->assertStatus(401);
    test()->actingAs($user)->get(route('admin.leads.create'))->assertStatus(401);
});

it('analista não edita cadastro de produtos, mas edita oportunidades', function () {
    $user = userWithRole('Analista');

    test()->actingAs($user)->get(route('admin.products.edit', 1))->assertStatus(401);
    test()->actingAs($user)->get(route('admin.leads.create'))->assertOk();
});

it('super admin (permission_type=all) acessa absolutamente tudo', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);

    test()->actingAs($admin)->get(route('admin.leads.create'))->assertOk();
    test()->actingAs($admin)->get(route('admin.settings.users.index'))->assertOk();
    test()->actingAs($admin)->get(route('admin.settings.roles.index'))->assertOk();
    test()->actingAs($admin)->get(route('admin.audit_log.index'))->assertOk();
    test()->actingAs($admin)->get(route('admin.projects.create'))->assertOk();
});
