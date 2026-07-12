<?php

namespace Webkul\Admin\Http\Controllers\Project;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Project\ProjectDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\User\Repositories\UserRepository;

class ProjectController extends Controller
{
    public function __construct(
        protected LeadRepository $leadRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(ProjectDataGrid::class)->process();
        }

        return view('admin::projects.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $leads = $this->leadRepository->all();

        $users = $this->userRepository->all();

        return view('admin::projects.create', compact('leads', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validateProject($request);

        Project::create($request->only([
            'name', 'description', 'status', 'start_date', 'end_date', 'lead_id', 'user_id',
        ]));

        session()->flash('success', trans('admin::app.projects.index.create-success'));

        return redirect()->route('admin.projects.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $project = Project::with(['tasks.user'])->findOrFail($id);

        $leads = $this->leadRepository->all();

        $users = $this->userRepository->all();

        return view('admin::projects.edit', compact('project', 'leads', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $project = Project::findOrFail($id);

        $this->validateProject($request);

        $project->update($request->only([
            'name', 'description', 'status', 'start_date', 'end_date', 'lead_id', 'user_id',
        ]));

        session()->flash('success', trans('admin::app.projects.index.update-success'));

        return redirect()->route('admin.projects.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Project::findOrFail($id)->delete();

            return response()->json([
                'message' => trans('admin::app.projects.index.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.projects.index.delete-failed'),
            ], 400);
        }
    }

    /**
     * Mass Delete the specified resources.
     */
    public function massDestroy(Request $request): JsonResponse
    {
        Project::whereIn('id', $request->input('indices', []))->get()->each->delete();

        return response()->json([
            'message' => trans('admin::app.projects.index.delete-success'),
        ]);
    }

    private function validateProject(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:planning,in_progress,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'lead_id' => 'nullable|exists:leads,id',
            'user_id' => 'nullable|exists:users,id',
        ]);
    }
}
