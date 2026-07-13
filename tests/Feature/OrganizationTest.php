<?php

use Webkul\Contact\Models\Organization;
use Webkul\User\Models\Role;

it('cria, edita e exclui uma empresa', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->post(route('admin.contacts.organizations.store'), [
        'name' => 'Empresa de Teste',
    ])->assertRedirect(route('admin.contacts.organizations.index'));

    $organization = Organization::where('name', 'Empresa de Teste')->firstOrFail();

    test()->actingAs($admin)->put(route('admin.contacts.organizations.update', $organization->id), [
        'name' => 'Empresa Renomeada',
    ])->assertRedirect(route('admin.contacts.organizations.index'));

    expect($organization->fresh()->name)->toBe('Empresa Renomeada');

    test()->actingAs($admin)
        ->delete(route('admin.contacts.organizations.delete', $organization->id))
        ->assertOk();

    expect(Organization::find($organization->id))->toBeNull();
});

it('não cria empresa com nome vazio', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    // A validação dinâmica (AttributeForm) só valida campos que estão
    // PRESENTES na requisição — mandar o payload inteiro vazio (sem a
    // chave "name") pula a checagem de obrigatório. Por isso mandamos a
    // chave presente, mas vazia, pra testar a regra de "obrigatório" de
    // verdade.
    test()->actingAs($admin)->post(route('admin.contacts.organizations.store'), [
        'name' => '',
    ])->assertSessionHasErrors('name');
});
