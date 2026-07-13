<?php

use Webkul\Contact\Models\Person;
use Webkul\Quote\Models\Quote;
use Webkul\User\Models\Role;

it('converte uma cotação em fatura trocando document_type', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $person = Person::create(['name' => 'Cliente Teste']);

    $quote = Quote::create([
        'subject' => 'Cotação de teste',
        'person_id' => $person->id,
        'user_id' => $admin->id,
        'document_type' => 'quote',
    ]);

    expect($quote->document_type)->toBe('quote');

    test()->actingAs($admin)
        ->post(route('admin.quotes.convert_to_invoice', $quote->id))
        ->assertOk();

    expect($quote->fresh()->document_type)->toBe('invoice');
});

it('a conversão para fatura fica registrada na auditoria', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $person = Person::create(['name' => 'Cliente Teste']);

    $quote = Quote::create([
        'subject' => 'Cotação de teste 2',
        'person_id' => $person->id,
        'user_id' => $admin->id,
        'document_type' => 'quote',
    ]);

    test()->actingAs($admin)->post(route('admin.quotes.convert_to_invoice', $quote->id));

    $changes = \DB::table('audit_logs')
        ->where('model_type', Quote::class)
        ->where('model_id', $quote->id)
        ->where('action', 'update')
        ->orderByDesc('id')
        ->value('field_changes');

    expect($changes)->not->toBeNull();
    expect(json_decode($changes, true))->toHaveKey('document_type');
});
