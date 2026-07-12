<?php

namespace Webkul\Admin\DataGrids\SupportTicket;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\DataGrid\DataGrid;
use Webkul\User\Repositories\UserRepository;

class SupportTicketDataGrid extends DataGrid
{
    protected $sortCol = 'id';

    protected $sortOrder = 'desc';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('support_tickets')
            ->addSelect(
                'support_tickets.id',
                'support_tickets.subject',
                'support_tickets.status',
                'support_tickets.priority',
                'support_tickets.created_at',
                'users.id as user_id',
                'users.name as agent_name',
                'persons.id as person_id',
                'persons.name as person_name'
            )
            ->leftJoin('users', 'support_tickets.user_id', '=', 'users.id')
            ->leftJoin('persons', 'support_tickets.person_id', '=', 'persons.id');

        $this->addFilter('id', 'support_tickets.id');
        $this->addFilter('status', 'support_tickets.status');
        $this->addFilter('priority', 'support_tickets.priority');
        $this->addFilter('agent_name', 'users.name');
        $this->addFilter('person_name', 'persons.name');
        $this->addFilter('created_at', 'support_tickets.created_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'subject',
            'label' => trans('admin::app.support-tickets.index.datagrid.subject'),
            'type' => 'string',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ]);

        $statusBadges = [
            'open' => ['bg-blue-100', 'text-blue-700', trans('admin::app.support-tickets.index.datagrid.status-open')],
            'in_progress' => ['bg-yellow-100', 'text-yellow-700', trans('admin::app.support-tickets.index.datagrid.status-in-progress')],
            'resolved' => ['bg-green-100', 'text-green-700', trans('admin::app.support-tickets.index.datagrid.status-resolved')],
            'closed' => ['bg-gray-100', 'text-gray-700', trans('admin::app.support-tickets.index.datagrid.status-closed')],
        ];

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.support-tickets.index.datagrid.status'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                ['id' => 1, 'name' => trans('admin::app.support-tickets.index.datagrid.status-open'), 'value' => 'open'],
                ['id' => 2, 'name' => trans('admin::app.support-tickets.index.datagrid.status-in-progress'), 'value' => 'in_progress'],
                ['id' => 3, 'name' => trans('admin::app.support-tickets.index.datagrid.status-resolved'), 'value' => 'resolved'],
                ['id' => 4, 'name' => trans('admin::app.support-tickets.index.datagrid.status-closed'), 'value' => 'closed'],
            ],
            'closure' => function ($row) use ($statusBadges) {
                [$bg, $text, $label] = $statusBadges[$row->status] ?? ['bg-gray-100', 'text-gray-700', $row->status];

                return "<span class=\"rounded-md {$bg} px-2 py-1 text-xs font-medium {$text}\">{$label}</span>";
            },
        ]);

        $priorityBadges = [
            'low' => ['bg-gray-100', 'text-gray-700', trans('admin::app.support-tickets.index.datagrid.priority-low')],
            'medium' => ['bg-yellow-100', 'text-yellow-700', trans('admin::app.support-tickets.index.datagrid.priority-medium')],
            'high' => ['bg-red-100', 'text-red-700', trans('admin::app.support-tickets.index.datagrid.priority-high')],
        ];

        $this->addColumn([
            'index' => 'priority',
            'label' => trans('admin::app.support-tickets.index.datagrid.priority'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                ['id' => 1, 'name' => trans('admin::app.support-tickets.index.datagrid.priority-low'), 'value' => 'low'],
                ['id' => 2, 'name' => trans('admin::app.support-tickets.index.datagrid.priority-medium'), 'value' => 'medium'],
                ['id' => 3, 'name' => trans('admin::app.support-tickets.index.datagrid.priority-high'), 'value' => 'high'],
            ],
            'closure' => function ($row) use ($priorityBadges) {
                [$bg, $text, $label] = $priorityBadges[$row->priority] ?? ['bg-gray-100', 'text-gray-700', $row->priority];

                return "<span class=\"rounded-md {$bg} px-2 py-1 text-xs font-medium {$text}\">{$label}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'person_name',
            'label' => trans('admin::app.support-tickets.index.datagrid.person'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => PersonRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'name',
                ],
            ],
        ]);

        $this->addColumn([
            'index' => 'agent_name',
            'label' => trans('admin::app.support-tickets.index.datagrid.agent'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => UserRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'name',
                ],
            ],
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.support-tickets.index.datagrid.created-at'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => core()->formatDate($row->created_at),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'index' => 'edit',
            'icon' => 'icon-edit',
            'title' => trans('admin::app.support-tickets.index.datagrid.edit'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.support_tickets.edit', $row->id),
        ]);

        $this->addAction([
            'index' => 'delete',
            'icon' => 'icon-delete',
            'title' => trans('admin::app.support-tickets.index.datagrid.delete'),
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.support_tickets.delete', $row->id),
        ]);
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        $this->addMassAction([
            'icon' => 'icon-delete',
            'title' => trans('admin::app.support-tickets.index.datagrid.delete'),
            'method' => 'POST',
            'url' => route('admin.support_tickets.mass_delete'),
        ]);
    }
}
