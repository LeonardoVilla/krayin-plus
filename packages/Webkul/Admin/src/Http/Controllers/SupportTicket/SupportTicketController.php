<?php

namespace Webkul\Admin\Http\Controllers\SupportTicket;

use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\SupportTicket\SupportTicketDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\User\Repositories\UserRepository;

class SupportTicketController extends Controller
{
    public function __construct(
        protected PersonRepository $personRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(SupportTicketDataGrid::class)->process();
        }

        return view('admin::support-tickets.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $persons = $this->personRepository->all();

        $users = $this->userRepository->all();

        return view('admin::support-tickets.create', compact('persons', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validateTicket($request);

        SupportTicket::create($request->only([
            'subject', 'description', 'status', 'priority', 'person_id', 'user_id',
        ]));

        session()->flash('success', trans('admin::app.support-tickets.index.create-success'));

        return redirect()->route('admin.support_tickets.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $ticket = SupportTicket::findOrFail($id);

        $persons = $this->personRepository->all();

        $users = $this->userRepository->all();

        return view('admin::support-tickets.edit', compact('ticket', 'persons', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $ticket = SupportTicket::findOrFail($id);

        $this->validateTicket($request);

        $ticket->update($request->only([
            'subject', 'description', 'status', 'priority', 'person_id', 'user_id',
        ]));

        session()->flash('success', trans('admin::app.support-tickets.index.update-success'));

        return redirect()->route('admin.support_tickets.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            SupportTicket::findOrFail($id)->delete();

            return response()->json([
                'message' => trans('admin::app.support-tickets.index.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.support-tickets.index.delete-failed'),
            ], 400);
        }
    }

    /**
     * Mass Delete the specified resources.
     */
    public function massDestroy(Request $request): JsonResponse
    {
        SupportTicket::whereIn('id', $request->input('indices', []))->get()->each->delete();

        return response()->json([
            'message' => trans('admin::app.support-tickets.index.delete-success'),
        ]);
    }

    private function validateTicket(Request $request): void
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'required|in:low,medium,high',
            'person_id' => 'nullable|exists:persons,id',
            'user_id' => 'nullable|exists:users,id',
        ]);
    }
}
