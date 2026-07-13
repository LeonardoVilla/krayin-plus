<?php

use App\Models\SupportTicket;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

it('cria, edita e exclui um chamado', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->post(route('admin.support_tickets.store'), [
        'subject' => 'Impressora não funciona',
        'description' => 'Impressora do 3º andar não liga',
        'status' => 'open',
        'priority' => 'medium',
    ])->assertRedirect(route('admin.support_tickets.index'));

    $ticket = SupportTicket::where('subject', 'Impressora não funciona')->firstOrFail();

    expect($ticket->status)->toBe('open');

    test()->actingAs($admin)->put(route('admin.support_tickets.update', $ticket->id), [
        'subject' => 'Impressora não funciona',
        'description' => 'Impressora do 3º andar não liga',
        'status' => 'in_progress',
        'priority' => 'high',
    ])->assertRedirect(route('admin.support_tickets.index'));

    expect($ticket->fresh()->status)->toBe('in_progress');
    expect($ticket->fresh()->priority)->toBe('high');

    test()->actingAs($admin)
        ->delete(route('admin.support_tickets.delete', $ticket->id))
        ->assertOk();

    expect(SupportTicket::find($ticket->id))->toBeNull();
});

it('não cria chamado sem assunto', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->post(route('admin.support_tickets.store'), [
        'description' => 'sem assunto',
        'status' => 'open',
        'priority' => 'medium',
    ])->assertSessionHasErrors('subject');
});

it('a criação de um chamado fica registrada na auditoria', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->post(route('admin.support_tickets.store'), [
        'subject' => 'Teste de auditoria',
        'description' => 'x',
        'status' => 'open',
        'priority' => 'low',
    ]);

    $logged = \DB::table('audit_logs')
        ->where('model_type', 'App\\Models\\SupportTicket')
        ->where('action', 'insert')
        ->exists();

    expect($logged)->toBeTrue();
});
