<?php

use App\Models\Project;
use App\Models\ProjectTask;
use Webkul\User\Models\Role;

it('lista as tarefas agrupadas por status pro kanban', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $project = Project::create(['name' => 'Projeto Kanban', 'status' => 'planning']);

    ProjectTask::create(['project_id' => $project->id, 'title' => 'Tarefa pendente', 'status' => 'pending']);
    ProjectTask::create(['project_id' => $project->id, 'title' => 'Tarefa em andamento', 'status' => 'in_progress']);
    ProjectTask::create(['project_id' => $project->id, 'title' => 'Tarefa feita', 'status' => 'done']);

    $response = test()->actingAs($admin)->get(route('admin.projects.tasks.kanban', $project->id));

    $response->assertOk();
    expect($response->json('pending'))->toHaveCount(1);
    expect($response->json('in_progress'))->toHaveCount(1);
    expect($response->json('done'))->toHaveCount(1);
});

it('atualiza o status de uma tarefa via drag-and-drop do kanban', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $project = Project::create(['name' => 'Projeto Kanban 2', 'status' => 'planning']);
    $task = ProjectTask::create(['project_id' => $project->id, 'title' => 'Tarefa', 'status' => 'pending']);

    test()->actingAs($admin)
        ->put(route('admin.projects.tasks.status', ['projectId' => $project->id, 'id' => $task->id]), [
            'status' => 'done',
        ])
        ->assertOk();

    expect($task->fresh()->status)->toBe('done');
});

it('rejeita um status inválido no kanban', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $project = Project::create(['name' => 'Projeto Kanban 3', 'status' => 'planning']);
    $task = ProjectTask::create(['project_id' => $project->id, 'title' => 'Tarefa', 'status' => 'pending']);

    test()->actingAs($admin)
        ->put(route('admin.projects.tasks.status', ['projectId' => $project->id, 'id' => $task->id]), [
            'status' => 'nao-existe',
        ])
        ->assertSessionHasErrors('status');

    expect($task->fresh()->status)->toBe('pending');
});

it('retorna as tarefas formatadas pro gantt (frappe-gantt)', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $project = Project::create(['name' => 'Projeto Gantt', 'status' => 'planning']);
    $task = ProjectTask::create([
        'project_id' => $project->id,
        'title' => 'Tarefa com datas',
        'status' => 'in_progress',
        'start_date' => '2026-08-01',
        'due_date' => '2026-08-10',
    ]);

    $response = test()->actingAs($admin)->get(route('admin.projects.tasks.gantt', $project->id));

    $response->assertOk();
    $data = $response->json()[0];

    expect($data['id'])->toBe((string) $task->id);
    expect($data['start'])->toBe('2026-08-01');
    expect($data['end'])->toBe('2026-08-10');
    expect($data['progress'])->toBe(50);
});

it('atualiza as datas de uma tarefa via arrastar/redimensionar no gantt', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $project = Project::create(['name' => 'Projeto Gantt 2', 'status' => 'planning']);
    $task = ProjectTask::create([
        'project_id' => $project->id,
        'title' => 'Tarefa',
        'status' => 'pending',
        'start_date' => '2026-08-01',
        'due_date' => '2026-08-05',
    ]);

    test()->actingAs($admin)
        ->put(route('admin.projects.tasks.dates', ['projectId' => $project->id, 'id' => $task->id]), [
            'start_date' => '2026-08-03',
            'due_date' => '2026-08-15',
        ])
        ->assertOk();

    expect($task->fresh()->start_date)->toBe('2026-08-03');
    expect($task->fresh()->due_date)->toBe('2026-08-15');
});

it('rejeita data de termino anterior a data de inicio no gantt', function () {
    $admin = makeUser([
        'role_id' => Role::where('permission_type', 'all')->firstOrFail()->id,
        'status' => 1,
    ]);

    $project = Project::create(['name' => 'Projeto Gantt 3', 'status' => 'planning']);
    $task = ProjectTask::create([
        'project_id' => $project->id,
        'title' => 'Tarefa',
        'status' => 'pending',
        'start_date' => '2026-08-01',
        'due_date' => '2026-08-05',
    ]);

    test()->actingAs($admin)
        ->put(route('admin.projects.tasks.dates', ['projectId' => $project->id, 'id' => $task->id]), [
            'start_date' => '2026-08-10',
            'due_date' => '2026-08-01',
        ])
        ->assertSessionHasErrors('due_date');
});
