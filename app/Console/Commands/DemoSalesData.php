<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectTask;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Source;
use Webkul\Lead\Models\Type;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

/**
 * Gera (ou apaga) dados de demonstração de venda de cursos — 3 cursos, 3
 * clientes, 3 Oportunidades no funil de vendas (em estágios diferentes)
 * e 3 Projetos de acompanhamento de turma (com tarefas no Kanban/Gantt),
 * ligados entre si.
 *
 * Pensado pra testar o ambiente de ponta a ponta sem sujar o banco de
 * verdade — tudo que é criado fica registrado num manifesto
 * (storage/app/demo-sales-manifest.json), e o comando de limpeza apaga
 * exatamente esses registros, nada a mais, nada a menos.
 *
 * Uso:
 *   php artisan demo:sales-data           — cria os dados
 *   php artisan demo:sales-data --clear   — apaga os dados criados
 */
class DemoSalesData extends Command
{
    protected $signature = 'demo:sales-data {--clear : Remove os dados de demonstração em vez de criar}';

    protected $description = 'Cria ou remove dados de demonstração de venda de cursos (Oportunidades + Projetos)';

    private string $manifestPath;

    public function __construct()
    {
        parent::__construct();

        $this->manifestPath = storage_path('app/demo-sales-manifest.json');
    }

    public function handle(): int
    {
        if ($this->option('clear')) {
            return $this->clear();
        }

        return $this->seed();
    }

    private function seed(): int
    {
        if (File::exists($this->manifestPath)) {
            $this->error('Já existem dados de demonstração criados. Rode com --clear antes de gerar de novo.');

            return self::FAILURE;
        }

        $manifest = [
            'products' => [],
            'persons' => [],
            'leads' => [],
            'projects' => [],
            'project_tasks' => [],
        ];

        $user = User::where('role_id', Role::where('permission_type', 'all')->value('id'))->first()
            ?? User::first();

        $pipeline = Pipeline::where('is_default', 1)->first();
        $stages = $pipeline?->stages()->orderBy('id')->get();
        $source = Source::first();
        $type = Type::first();

        if (! $user || ! $pipeline || ! $stages || $stages->isEmpty() || ! $source || ! $type) {
            $this->error('Faltam dados básicos no sistema (usuário, funil de vendas, origem ou tipo de oportunidade). Rode os seeders padrão do Krayin antes.');

            return self::FAILURE;
        }

        $courses = [
            [
                'sku' => 'DEMO-CURSO-EXCEL',
                'name' => '[DEMO] Excel Avançado',
                'price' => 450,
                'cliente' => 'Mariana Souza',
                'estagio_codigo' => 'new',
                'projeto' => '[DEMO] Turma Excel Avançado — Julho/2026',
                'projeto_status' => 'planning',
            ],
            [
                'sku' => 'DEMO-CURSO-INGLES-TURISMO',
                'name' => '[DEMO] Inglês para o Turismo',
                'price' => 600,
                'cliente' => 'Carlos Eduardo Lima',
                'estagio_codigo' => 'negotiation',
                'projeto' => '[DEMO] Turma Inglês para o Turismo — Agosto/2026',
                'projeto_status' => 'in_progress',
            ],
            [
                'sku' => 'DEMO-CURSO-GESTAO-NEGOCIOS',
                'name' => '[DEMO] Gestão de Pequenos Negócios',
                'price' => 750,
                'cliente' => 'Fernanda Ribeiro',
                'estagio_codigo' => 'won',
                'projeto' => '[DEMO] Turma Gestão de Pequenos Negócios — Setembro/2026',
                'projeto_status' => 'planning',
            ],
        ];

        foreach ($courses as $course) {
            // --- Curso (Produto) ---
            $product = Product::create([
                'sku' => $course['sku'],
                'name' => $course['name'],
                'price' => $course['price'],
                'quantity' => 999,
            ]);
            $manifest['products'][] = $product->id;

            // --- Cliente interessado (Pessoa) ---
            $person = Person::create([
                'name' => '[DEMO] '.$course['cliente'],
                'emails' => [['value' => strtolower(str_replace(' ', '.', $course['cliente'])).'@exemplo.com', 'label' => 'work']],
                'contact_numbers' => [['value' => '(65) 99999-0000', 'label' => 'work']],
            ]);
            $manifest['persons'][] = $person->id;

            // --- Oportunidade de venda (Lead) ---
            $stage = $stages->firstWhere('code', $course['estagio_codigo']) ?? $stages->first();

            $lead = Lead::create([
                'title' => '[DEMO] Venda — '.$course['name'],
                'lead_value' => $course['price'],
                'user_id' => $user->id,
                'person_id' => $person->id,
                'lead_source_id' => $source->id,
                'lead_type_id' => $type->id,
                'lead_pipeline_id' => $pipeline->id,
                'lead_stage_id' => $stage->id,
                'lead_pipeline_stage_id' => $stage->id,
                'status' => 1,
            ]);
            $manifest['leads'][] = $lead->id;

            // --- Projeto de acompanhamento da turma ---
            $project = Project::create([
                'name' => $course['projeto'],
                'description' => 'Acompanhamento da turma gerado por dados de demonstração.',
                'status' => $course['projeto_status'],
                'start_date' => Carbon::now()->addDays(7)->toDateString(),
                'end_date' => Carbon::now()->addDays(37)->toDateString(),
                'lead_id' => $lead->id,
                'user_id' => $user->id,
            ]);
            $manifest['projects'][] = $project->id;

            // --- Tarefas do projeto, espalhadas pelos 3 estágios do Kanban ---
            $tasks = [
                ['title' => 'Confirmar matrícula do aluno', 'status' => 'done', 'dias_inicio' => 0, 'dias_fim' => 2],
                ['title' => 'Enviar material didático', 'status' => 'in_progress', 'dias_inicio' => 3, 'dias_fim' => 6],
                ['title' => 'Agendar aula inaugural', 'status' => 'pending', 'dias_inicio' => 7, 'dias_fim' => 8],
            ];

            foreach ($tasks as $taskData) {
                $task = ProjectTask::create([
                    'project_id' => $project->id,
                    'title' => $taskData['title'],
                    'status' => $taskData['status'],
                    'start_date' => Carbon::now()->addDays($taskData['dias_inicio'])->toDateString(),
                    'due_date' => Carbon::now()->addDays($taskData['dias_fim'])->toDateString(),
                    'user_id' => $user->id,
                ]);
                $manifest['project_tasks'][] = $task->id;
            }

            $this->info("Criado: {$course['name']} — Oportunidade + Projeto + 3 tarefas");
        }

        File::put($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));

        $this->newLine();
        $this->info('Dados de demonstração criados com sucesso.');
        $this->line('Pra remover tudo depois: php artisan demo:sales-data --clear');

        return self::SUCCESS;
    }

    private function clear(): int
    {
        if (! File::exists($this->manifestPath)) {
            $this->error('Não existe nenhum dado de demonstração registrado (nada a remover).');

            return self::FAILURE;
        }

        $manifest = json_decode(File::get($this->manifestPath), true);

        ProjectTask::whereIn('id', $manifest['project_tasks'] ?? [])->delete();
        $this->info(count($manifest['project_tasks'] ?? []).' tarefa(s) de projeto removida(s).');

        Project::whereIn('id', $manifest['projects'] ?? [])->delete();
        $this->info(count($manifest['projects'] ?? []).' projeto(s) removido(s).');

        Lead::whereIn('id', $manifest['leads'] ?? [])->delete();
        $this->info(count($manifest['leads'] ?? []).' oportunidade(s) removida(s).');

        Person::whereIn('id', $manifest['persons'] ?? [])->delete();
        $this->info(count($manifest['persons'] ?? []).' pessoa(s) removida(s).');

        Product::whereIn('id', $manifest['products'] ?? [])->delete();
        $this->info(count($manifest['products'] ?? []).' produto(s)/curso(s) removido(s).');

        File::delete($this->manifestPath);

        $this->newLine();
        $this->info('Dados de demonstração removidos com sucesso.');

        return self::SUCCESS;
    }
}
