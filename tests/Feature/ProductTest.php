<?php

use Webkul\Product\Models\Product;
use Webkul\User\Models\Role;

it('cria, edita e exclui um produto', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->post(route('admin.products.store'), [
        'sku' => 'SKU-TESTE-001',
        'name' => 'Produto de Teste',
        'price' => '150.00',
        'quantity' => '10',
    ])->assertRedirect(route('admin.products.index'));

    $product = Product::where('sku', 'SKU-TESTE-001')->firstOrFail();

    expect($product->name)->toBe('Produto de Teste');

    test()->actingAs($admin)->put(route('admin.products.update', $product->id), [
        'sku' => 'SKU-TESTE-001',
        'name' => 'Produto Renomeado',
        'price' => '200.00',
        'quantity' => '5',
    ])->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->name)->toBe('Produto Renomeado');

    test()->actingAs($admin)
        ->delete(route('admin.products.delete', $product->id))
        ->assertOk();

    expect(Product::find($product->id))->toBeNull();
});

it('não cria produto com sku vazio', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    // A validação dinâmica (AttributeForm) só valida campos PRESENTES na
    // requisição — por isso a chave "sku" precisa estar no payload
    // (mesmo que vazia) pra regra de "obrigatório" realmente disparar.
    test()->actingAs($admin)->post(route('admin.products.store'), [
        'sku' => '',
        'name' => 'Produto sem SKU',
        'price' => '10.00',
        'quantity' => '1',
    ])->assertSessionHasErrors('sku');
});

it('não deixa criar produto com sku duplicado (regra de integridade do banco)', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    Product::create(['sku' => 'SKU-DUPLICADO', 'name' => 'Original', 'price' => 10, 'quantity' => 1]);

    // SKU nao tem validacao de unicidade no formulario (nao esta marcado
    // is_unique no attribute) — quem garante isso e' a constraint UNIQUE
    // da coluna no banco. A segunda tentativa de fato falha, so que como
    // erro de servidor (500), nao como validacao amigavel — o Laravel
    // converte a excecao de banco numa resposta HTTP normalmente (por
    // isso testamos o status, nao a excecao crua).
    test()->actingAs($admin)->post(route('admin.products.store'), [
        'sku' => 'SKU-DUPLICADO',
        'name' => 'Duplicado',
        'price' => '10.00',
        'quantity' => '1',
    ])->assertStatus(500);

    expect(Product::where('sku', 'SKU-DUPLICADO')->count())->toBe(1);
});

it('a exclusão de um produto fica registrada na auditoria', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $product = Product::create(['sku' => 'SKU-AUDIT', 'name' => 'Produto Auditado', 'price' => 10, 'quantity' => 1]);

    test()->actingAs($admin)->delete(route('admin.products.delete', $product->id));

    $logged = \DB::table('audit_logs')
        ->where('model_type', Product::class)
        ->where('model_id', $product->id)
        ->where('action', 'delete')
        ->exists();

    expect($logged)->toBeTrue();
});
