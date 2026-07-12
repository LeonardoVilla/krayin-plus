<?php

namespace Webkul\Admin\Http\Controllers\Project;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Repositories\UserRepository;

class ProjectTaskController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Show the form for creating a new task for the given project.
     */
    public function create(int $projectId): View
    {
        $project = Project::findOrFail($projectId);

        $users = $this->userRepository->all();

        return view('admin::projects.tasks.create', compact('project', 'users'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request, int $projectId): RedirectResponse
    {
        Project::findOrFail($projectId);

        $this->validateTask($request);

        ProjectTask::create($request->only(['title', 'status', 'start_date', 'due_date', 'user_id']) + [
            'project_id' => $projectId,
        ]);

        session()->flash('success', trans('admin::app.projects.tasks.create-success'));

        return redirect()->route('admin.projects.edit', $projectId);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(int $projectId, int $id): View
    {
        $project = Project::findOrFail($projectId);

        $task = ProjectTask::where('project_id', $projectId)->findOrFail($id);

        $users = $this->userRepository->all();

        return view('admin::projects.tasks.edit', compact('project', 'task', 'users'));
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, int $projectId, int $id): RedirectResponse
    {
        $task = ProjectTask::where('project_id', $projectId)->findOrFail($id);

        $this->validateTask($request);

        $task->update($request->only(['title', 'status', 'start_date', 'due_date', 'user_id']));

        session()->flash('success', trans('admin::app.projects.tasks.update-success'));

        return redirect()->route('admin.projects.edit', $projectId);
    }

    /**
     * Return the project's tasks grouped by status, for the Kanban board.
     */
    public function kanban(int $projectId): JsonResponse
    {
        Project::findOrFail($projectId);

        $tasks = ProjectTask::with('user')
            ->where('project_id', $projectId)
            ->orderByDesc('id')
            ->get()
            ->groupBy('status');

        return response()->json([
            'pending' => $tasks->get('pending', collect())->values(),
            'in_progress' => $tasks->get('in_progress', collect())->values(),
            'done' => $tasks->get('done', collect())->values(),
        ]);
    }

    /**
     * Update only the status of a task (used by the Kanban board drag-and-drop).
     */
    public function updateStatus(Request $request, int $projectId, int $id): JsonResponse
    {
        $task = ProjectTask::where('project_id', $projectId)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,in_progress,done',
        ]);

        $task->update(['status' => $request->input('status')]);

        return response()->json([
            'message' => trans('admin::app.projects.tasks.update-success'),
        ]);
    }

    /**
     * Return the project's tasks formatted for the Gantt chart (Frappe Gantt).
     */
    public function gantt(int $projectId): JsonResponse
    {
        Project::findOrFail($projectId);

        $progressByStatus = [
            'pending' => 0,
            'in_progress' => 50,
            'done' => 100,
        ];

        $tasks = ProjectTask::where('project_id', $projectId)
            ->orderBy('start_date')
            ->get()
            ->map(function (ProjectTask $task) use ($progressByStatus) {
                $start = $task->start_date ?? $task->created_at->toDateString();
                $end = $task->due_date ?? \Carbon\Carbon::parse($start)->addDay()->toDateString();

                if ($end <= $start) {
                    $end = \Carbon\Carbon::parse($start)->addDay()->toDateString();
                }

                return [
                    'id' => (string) $task->id,
                    'name' => $task->title,
                    'start' => $start,
                    'end' => $end,
                    'progress' => $progressByStatus[$task->status] ?? 0,
                ];
            });

        return response()->json($tasks->values());
    }

    /**
     * Update a task's start/due date (used by the Gantt chart drag/resize).
     */
    public function updateDates(Request $request, int $projectId, int $id): JsonResponse
    {
        $task = ProjectTask::where('project_id', $projectId)->findOrFail($id);

        $request->validate([
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
        ]);

        $task->update($request->only(['start_date', 'due_date']));

        return response()->json([
            'message' => trans('admin::app.projects.tasks.update-success'),
        ]);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(int $projectId, int $id): JsonResponse
    {
        try {
            ProjectTask::where('project_id', $projectId)->findOrFail($id)->delete();

            return response()->json([
                'message' => trans('admin::app.projects.tasks.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.projects.tasks.delete-failed'),
            ], 400);
        }
    }

    private function validateTask(Request $request): void
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:pending,in_progress,done',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
        ]);
    }
}
