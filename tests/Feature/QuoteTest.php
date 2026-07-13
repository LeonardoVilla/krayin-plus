<?php

use Webkul\Contact\Models\Person;
use Webkul\Quote\Models\Quote;
use Webkul\User\Models\Role;

it('visualiza e imprime uma cotação', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);
    $person = Person::create(['name' => 'Cliente da Cotação']);

    $quote = Quote::create([
        'subject' => 'Cotação de Teste',
        'person_id' => $person->id,
        'user_id' => $admin->id,
        'document_type' => 'quote',
    ]);

    test()->actingAs($admin)->get(route('admin.quotes.edit', $quote->id))->assertOk();
    test()->actingAs($admin)->get(route('admin.quotes.print', $quote->id))->assertOk();
});

it('exclui uma cotação', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);
    $person = Person::create(['name' => 'Cliente da Cotação 2']);

    $quote = Quote::create([
        'subject' => 'Cotação a Excluir',
        'person_id' => $person->id,
        'user_id' => $admin->id,
        'document_type' => 'quote',
    ]);

    test()->actingAs($admin)
        ->delete(route('admin.quotes.delete', $quote->id))
        ->assertOk();

    expect(Quote::find($quote->id))->toBeNull();
});

it('exclui várias cotações de uma vez (exclusão em massa)', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);
    $person = Person::create(['name' => 'Cliente da Cotação 3']);

    $quote1 = Quote::create(['subject' => 'Cotação 1', 'person_id' => $person->id, 'user_id' => $admin->id]);
    $quote2 = Quote::create(['subject' => 'Cotação 2', 'person_id' => $person->id, 'user_id' => $admin->id]);

    test()->actingAs($admin)
        ->post(route('admin.quotes.mass_delete'), ['indices' => [$quote1->id, $quote2->id]])
        ->assertOk();

    expect(Quote::find($quote1->id))->toBeNull();
    expect(Quote::find($quote2->id))->toBeNull();
});
