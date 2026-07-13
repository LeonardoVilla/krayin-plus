<?php

use App\Models\Project;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

it('cria, edita e exclui um projeto', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->post(route('admin.projects.store'), [
        'name' => 'Reforma do laboratório',
        'description' => 'Reforma completa',
        'status' => 'planning',
        'start_date' => '2026-08-01',
        'end_date' => '2026-09-01',
    ])->assertRedirect(route('admin.projects.index'));

    $project = Project::where('name', 'Reforma do laboratório')->firstOrFail();

    expect($project->status)->toBe('planning');

    test()->actingAs($admin)->put(route('admin.projects.update', $project->id), [
        'name' => 'Reforma do laboratório',
        'description' => 'Reforma completa',
        'status' => 'in_progress',
        'start_date' => '2026-08-01',
        'end_date' => '2026-09-01',
    ])->assertRedirect(route('admin.projects.index'));

    expect($project->fresh()->status)->toBe('in_progress');

    test()->actingAs($admin)
        ->delete(route('admin.projects.delete', $project->id))
        ->assertOk();

    expect(Project::find($project->id))->toBeNull();
});

it('não cria projeto com data de término anterior à data de início', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($admin)->post(route('admin.projects.store'), [
        'name' => 'Projeto com datas invertidas',
        'status' => 'planning',
        'start_date' => '2026-09-01',
        'end_date' => '2026-08-01',
    ])->assertSessionHasErrors('end_date');
});

it('coordenador cria e edita projeto mas não exclui', function () {
    $coordenador = makeUser([
        'role_id' => Role::where('name', 'Coordenador')->firstOrFail()->id,
        'status' => 1,
    ]);

    test()->actingAs($coordenador)->post(route('admin.projects.store'), [
        'name' => 'Projeto do coordenador',
        'status' => 'planning',
    ])->assertRedirect(route('admin.projects.index'));

    $project = Project::where('name', 'Projeto do coordenador')->firstOrFail();

    test()->actingAs($coordenador)
        ->delete(route('admin.projects.delete', $project->id))
        ->assertStatus(401);

    expect(Project::find($project->id))->not->toBeNull();
});
