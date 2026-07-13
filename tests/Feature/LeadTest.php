<?php

use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline as LeadPipeline;
use Webkul\Lead\Models\Source as LeadSource;
use Webkul\Lead\Models\Type as LeadType;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * Criação de Oportunidade (Lead) via HTTP passa por um formulário com
 * validação dinâmica orientada por atributos customizáveis (LeadForm) —
 * testar o POST completo exigiria replicar essa engine de atributos.
 * Em vez disso, criamos a Oportunidade direto via Eloquent (o que já é
 * comportamento real e correto do sistema) e testamos as ações de negócio
 * de verdade pela rota HTTP: visualizar, mudar de estágio no funil,
 * excluir.
 */
/**
 * A tela de visualização da Oportunidade (leads/view/person.blade.php)
 * itera sobre `person.emails` sem checagem de nulo — por isso toda Pessoa
 * usada em teste de Oportunidade precisa ter `emails` (mesmo que vazio).
 */
function makePersonForLead(string $name): Person
{
    return Person::create(['name' => $name, 'emails' => [], 'contact_numbers' => []]);
}

function makeLead(User $user, Person $person): Lead
{
    $pipeline = LeadPipeline::where('is_default', 1)->firstOrFail();
    $stage = $pipeline->stages()->first();
    $source = LeadSource::firstOrFail();
    $type = LeadType::firstOrFail();

    return Lead::create([
        'title' => 'Oportunidade de Teste',
        'lead_value' => 1000,
        'user_id' => $user->id,
        'person_id' => $person->id,
        'lead_source_id' => $source->id,
        'lead_type_id' => $type->id,
        'lead_pipeline_id' => $pipeline->id,
        'lead_stage_id' => $stage->id,
        'lead_pipeline_stage_id' => $stage->id,
        'status' => 1,
    ]);
}

it('visualiza uma oportunidade', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);
    $person = makePersonForLead('Cliente da Oportunidade');
    $lead = makeLead($admin, $person);

    test()->actingAs($admin)->get(route('admin.leads.view', $lead->id))->assertOk();
});

it('move uma oportunidade pra outro estágio do funil', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);
    $person = makePersonForLead('Cliente da Oportunidade 2');
    $lead = makeLead($admin, $person);

    $pipeline = LeadPipeline::where('is_default', 1)->firstOrFail();
    $secondStage = $pipeline->stages()->skip(1)->first();

    test()->actingAs($admin)
        ->put(route('admin.leads.stage.update', $lead->id), [
            'lead_pipeline_stage_id' => $secondStage->id,
        ])
        ->assertOk();

    expect($lead->fresh()->lead_pipeline_stage_id)->toBe($secondStage->id);
});

it('exclui uma oportunidade', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);
    $person = makePersonForLead('Cliente da Oportunidade 3');
    $lead = makeLead($admin, $person);

    test()->actingAs($admin)
        ->delete(route('admin.leads.delete', $lead->id))
        ->assertOk();

    expect(Lead::find($lead->id))->toBeNull();
});

it('exclui várias oportunidades de uma vez (exclusão em massa)', function () {
    $admin = makeUser(['role_id' => Role::where('permission_type', 'all')->firstOrFail()->id, 'status' => 1]);
    $person = makePersonForLead('Cliente da Oportunidade 4');
    $lead1 = makeLead($admin, $person);
    $lead2 = makeLead($admin, $person);

    test()->actingAs($admin)
        ->post(route('admin.leads.mass_delete'), ['indices' => [$lead1->id, $lead2->id]])
        ->assertOk();

    expect(Lead::find($lead1->id))->toBeNull();
    expect(Lead::find($lead2->id))->toBeNull();
});
