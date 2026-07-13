<?php

use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * O sistema de auditoria (app/Support/AuditLogger.php) é acionado por
 * listeners globais no boot() do AppServiceProvider, escutando os eventos
 * "eloquent.created: *", "eloquent.updated: *" e "eloquent.deleted: *" —
 * ou seja, cobre QUALQUER model automaticamente, sem precisar adicionar
 * nada model a model. Estes testes confirmam isso pra alguns models
 * variados (não é uma lista exaustiva, é uma amostra).
 */

function lastAuditLog(string $modelType, $modelId, string $action)
{
    return \DB::table('audit_logs')
        ->where('model_type', $modelType)
        ->where('model_id', $modelId)
        ->where('action', $action)
        ->orderByDesc('id')
        ->first();
}

it('registra a criação de uma pessoa automaticamente', function () {
    $person = Person::create(['name' => 'Pessoa Auditada']);

    $log = lastAuditLog(Person::class, $person->id, 'insert');

    expect($log)->not->toBeNull();
    expect($log->model_label)->toBe('Pessoa Auditada');
});

it('registra a atualização de uma empresa, com o diff do campo alterado', function () {
    $organization = Organization::create(['name' => 'Empresa Original']);

    $organization->update(['name' => 'Empresa Renomeada']);

    $log = lastAuditLog(Organization::class, $organization->id, 'update');

    expect($log)->not->toBeNull();

    $changes = json_decode($log->field_changes, true);

    expect($changes['name']['old'])->toBe('Empresa Original');
    expect($changes['name']['new'])->toBe('Empresa Renomeada');
});

it('registra a exclusão de um usuário', function () {
    $role = Role::where('permission_type', 'all')->firstOrFail();

    $user = User::create([
        'name' => 'Usuário Descartável',
        'email' => 'descartavel@test.com',
        'password' => bcrypt('x'),
        'status' => 1,
        'role_id' => $role->id,
    ]);

    $userId = $user->id;

    $user->delete();

    $log = lastAuditLog(User::class, $userId, 'delete');

    expect($log)->not->toBeNull();
});

it('não registra uma atualização que não muda nada de fato', function () {
    $person = Person::create(['name' => 'Pessoa Estável']);

    $countBefore = \DB::table('audit_logs')
        ->where('model_type', Person::class)
        ->where('model_id', $person->id)
        ->count();

    // update() com o MESMO valor não deve gerar log (getChanges() fica vazio)
    $person->update(['name' => 'Pessoa Estável']);

    $countAfter = \DB::table('audit_logs')
        ->where('model_type', Person::class)
        ->where('model_id', $person->id)
        ->count();

    expect($countAfter)->toBe($countBefore);
});

it('registra quem fez a alteração (usuário autenticado no momento)', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin);

    $person = Person::create(['name' => 'Pessoa Rastreada']);

    $log = lastAuditLog(Person::class, $person->id, 'insert');

    expect($log->user_id)->toBe($admin->id);
    expect($log->user_name)->toBe($admin->name);
});
