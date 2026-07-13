<?php

use Webkul\User\Models\Role;
use Webkul\User\Models\User;

it('super admin não consegue excluir a própria conta', function () {
    $role = Role::where('permission_type', 'all')->firstOrFail();
    $admin = makeUser(['role_id' => $role->id, 'status' => 1]);

    test()->actingAs($admin)
        ->delete(route('admin.settings.users.delete', $admin->id))
        ->assertStatus(400);

    expect(User::find($admin->id))->not->toBeNull();
});

it('super admin não consegue rebaixar a própria função (permission_type) editando-a', function () {
    $role = Role::where('permission_type', 'all')->firstOrFail();
    $admin = makeUser(['role_id' => $role->id, 'status' => 1]);

    test()->actingAs($admin)->put(route('admin.settings.roles.update', $role->id), [
        'name' => $role->name,
        'description' => 'tentativa de rebaixamento',
        'permission_type' => 'custom',
        'permissions' => ['dashboard'],
    ]);

    expect($role->fresh()->permission_type)->toBe('all');
});

it('super admin não consegue trocar a própria função pra uma custom via edição de usuário', function () {
    $allRole = Role::where('permission_type', 'all')->firstOrFail();
    $customRole = Role::where('name', 'Estagiário')->firstOrFail();

    $admin = makeUser(['role_id' => $allRole->id, 'status' => 1]);

    test()->actingAs($admin)->put(route('admin.settings.users.update', $admin->id), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role_id' => $customRole->id,
        'view_permission' => 'global',
    ]);

    expect($admin->fresh()->role_id)->toBe($allRole->id);
});

it('super admin consegue excluir a conta de outro usuário normalmente', function () {
    $allRole = Role::where('permission_type', 'all')->firstOrFail();
    $admin = makeUser(['role_id' => $allRole->id, 'status' => 1]);
    $other = makeUser(['role_id' => $allRole->id, 'status' => 1]);

    test()->actingAs($admin)
        ->delete(route('admin.settings.users.delete', $other->id))
        ->assertOk();

    expect(User::find($other->id))->toBeNull();
});
