<?php

namespace Webkul\Admin\DataGrids\Project;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\User\Repositories\UserRepository;

class ProjectDataGrid extends DataGrid
{
    protected $sortCol = 'id';

    protected $sortOrder = 'desc';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('projects')
            ->addSelect(
                'projects.id',
                'projects.name',
                'projects.status',
                'projects.start_date',
                'projects.end_date',
                'projects.created_at',
                'users.id as user_id',
                'users.name as owner_name',
                'leads.id as lead_id',
                'leads.title as lead_title'
            )
            ->leftJoin('users', 'projects.user_id', '=', 'users.id')
            ->leftJoin('leads', 'projects.lead_id', '=', 'leads.id');

        $this->addFilter('id', 'projects.id');
        $this->addFilter('status', 'projects.status');
        $this->addFilter('owner_name', 'users.name');
        $this->addFilter('created_at', 'projects.created_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.projects.index.datagrid.name'),
            'type' => 'string',
            'filterable' => true,
            'searchable' => true,
            'sortable' => true,
        ]);

        $statusBadges = [
            'planning' => ['bg-gray-100', 'text-gray-700', trans('admin::app.projects.index.datagrid.status-planning')],
            'in_progress' => ['bg-yellow-100', 'text-yellow-700', trans('admin::app.projects.index.datagrid.status-in-progress')],
            'completed' => ['bg-green-100', 'text-green-700', trans('admin::app.projects.index.datagrid.status-completed')],
            'cancelled' => ['bg-red-100', 'text-red-700', trans('admin::app.projects.index.datagrid.status-cancelled')],
        ];

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.projects.index.datagrid.status'),
            'type' => 'string',
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                ['id' => 1, 'name' => trans('admin::app.projects.index.datagrid.status-planning'), 'value' => 'planning'],
                ['id' => 2, 'name' => trans('admin::app.projects.index.datagrid.status-in-progress'), 'value' => 'in_progress'],
                ['id' => 3, 'name' => trans('admin::app.projects.index.datagrid.status-completed'), 'value' => 'completed'],
                ['id' => 4, 'name' => trans('admin::app.projects.index.datagrid.status-cancelled'), 'value' => 'cancelled'],
            ],
            'closure' => function ($row) use ($statusBadges) {
                [$bg, $text, $label] = $statusBadges[$row->status] ?? ['bg-gray-100', 'text-gray-700', $row->status];

                return "<span class=\"rounded-md {$bg} px-2 py-1 text-xs font-medium {$text}\">{$label}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'lead_title',
            'label' => trans('admin::app.projects.index.datagrid.lead'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'owner_name',
            'label' => trans('admin::app.projects.index.datagrid.owner'),
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
            'index' => 'start_date',
            'label' => trans('admin::app.projects.index.datagrid.start-date'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->start_date ? core()->formatDate($row->start_date, 'd M Y') : '-',
        ]);

        $this->addColumn([
            'index' => 'end_date',
            'label' => trans('admin::app.projects.index.datagrid.end-date'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->end_date ? core()->formatDate($row->end_date, 'd M Y') : '-',
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
            'title' => trans('admin::app.projects.index.datagrid.edit'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.projects.edit', $row->id),
        ]);

        $this->addAction([
            'index' => 'delete',
            'icon' => 'icon-delete',
            'title' => trans('admin::app.projects.index.datagrid.delete'),
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.projects.delete', $row->id),
        ]);
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        $this->addMassAction([
            'icon' => 'icon-delete',
            'title' => trans('admin::app.projects.index.datagrid.delete'),
            'method' => 'POST',
            'url' => route('admin.projects.mass_delete'),
        ]);
    }
}
