<?php

use Webkul\User\Models\Role;
use Webkul\User\Models\User;

it('super admin consegue simular outro usuário e depois voltar pro próprio usuário', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $target = makeUser([
        'role_id' => Role::where('name', 'Estagiário')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)
        ->get(route('admin.settings.users.impersonate.start', $target->id))
        ->assertRedirect(route('admin.dashboard.index'));

    expect(auth()->guard('user')->user()->id)->toBe($target->id);
    expect(session('impersonator_id'))->toBe($admin->id);

    test()->get(route('admin.settings.users.impersonate.stop'));

    expect(auth()->guard('user')->user()->id)->toBe($admin->id);
    expect(session()->has('impersonator_id'))->toBeFalse();
});

it('usuário sem acesso total não consegue simular ninguém', function () {
    $user = makeUser([
        'role_id' => Role::where('name', 'Analista')->firstOrFail()->id,
        'status' => 1,
    ]);

    $target = makeUser([
        'role_id' => Role::where('name', 'Estagiário')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($user)
        ->get(route('admin.settings.users.impersonate.start', $target->id))
        ->assertStatus(401);
});

it('não é possível simular a si mesmo', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->get(route('admin.settings.users.impersonate.start', $admin->id));

    expect(session()->has('impersonator_id'))->toBeFalse();
});

it('não é possível simular um segundo usuário com uma simulação já ativa', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $targetA = makeUser([
        'role_id' => Role::where('name', 'Estagiário')->firstOrFail()->id,
        'status' => 1,
    ]);

    $targetB = makeUser([
        'role_id' => Role::where('name', 'Analista')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->get(route('admin.settings.users.impersonate.start', $targetA->id));

    expect(auth()->guard('user')->user()->id)->toBe($targetA->id);

    test()->get(route('admin.settings.users.impersonate.start', $targetB->id));

    // continua simulando o primeiro alvo, não trocou pro segundo
    expect(auth()->guard('user')->user()->id)->toBe($targetA->id);
});

it('durante a simulação, não é possível criar, editar ou excluir nada (só leitura)', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    // simula um alvo COM acesso total — mesmo assim, a trava de
    // somente-leitura tem que bloquear, porque ela não depende da
    // permissão do usuário simulado.
    $target = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->get(route('admin.settings.users.impersonate.start', $target->id));

    expect(auth()->guard('user')->user()->id)->toBe($target->id);

    // GET continua liberado normalmente
    test()->get(route('admin.leads.index'))->assertOk();

    // POST/PUT/DELETE ficam bloqueados, mesmo o usuário simulado tendo
    // permissão de sobra pra isso
    test()->post(route('admin.support_tickets.store'), [
        'subject' => 'Tentativa durante simulação',
        'status' => 'open',
        'priority' => 'low',
    ])->assertRedirect();

    expect(\App\Models\SupportTicket::where('subject', 'Tentativa durante simulação')->exists())->toBeFalse();
});

it('a rota de encerrar simulação continua funcionando mesmo com a trava de somente-leitura', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $target = makeUser([
        'role_id' => Role::where('name', 'Estagiário')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->get(route('admin.settings.users.impersonate.start', $target->id));

    test()->get(route('admin.settings.users.impersonate.stop'));

    expect(auth()->guard('user')->user()->id)->toBe($admin->id);
    expect(session()->has('impersonator_id'))->toBeFalse();
});

it('início e fim da simulação ficam registrados na auditoria', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $target = makeUser([
        'role_id' => Role::where('name', 'Estagiário')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->get(route('admin.settings.users.impersonate.start', $target->id));
    test()->get(route('admin.settings.users.impersonate.stop'));

    $count = \DB::table('audit_logs')->where('model_type', 'Impersonation')->count();

    expect($count)->toBe(2);
});
