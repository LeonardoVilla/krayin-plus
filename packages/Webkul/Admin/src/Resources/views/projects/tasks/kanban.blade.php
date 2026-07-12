{!! view_render_event('admin.projects.tasks.kanban.before') !!}

<v-project-tasks-kanban :project-id="{{ $project->id }}">
</v-project-tasks-kanban>

{!! view_render_event('admin.projects.tasks.kanban.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-project-tasks-kanban-template"
    >
        <div class="flex gap-2.5 overflow-x-auto">
            <div
                class="flex min-w-[240px] max-w-[240px] flex-col gap-1 rounded-lg border border-gray-300 bg-white dark:border-gray-800 dark:bg-gray-900"
                v-for="column in columns"
                :key="column.value"
            >
                <div class="flex items-center justify-between px-2 py-3">
                    <span class="text-xs font-medium dark:text-white">
                        @{{ column.label }} (@{{ (tasksByStatus[column.value] || []).length }})
                    </span>
                </div>

                <draggable
                    class="flex min-h-[80px] flex-col gap-2 overflow-y-auto p-2"
                    ghost-class="draggable-ghost"
                    handle=".task-item"
                    v-bind="{animation: 200}"
                    :list="tasksByStatus[column.value]"
                    item-key="id"
                    group="project-tasks"
                    @change="handleChange(column.value, $event)"
                >
                    <template #item="{ element }">
                        <div class="task-item flex cursor-move flex-col gap-1 rounded-lg border border-gray-300 bg-gray-100 p-2 shadow-sm dark:border-gray-400 dark:bg-gray-400">
                            <p class="text-xs font-medium">
                                @{{ element.title }}
                            </p>

                            <div class="flex flex-wrap gap-1">
                                <div
                                    class="rounded-xl bg-gray-200 px-2 py-1 text-[10px] font-medium dark:bg-gray-800 dark:text-white"
                                    v-if="element.user"
                                >
                                    @{{ element.user.name }}
                                </div>

                                <div
                                    class="rounded-xl bg-gray-200 px-2 py-1 text-[10px] font-medium dark:bg-gray-800 dark:text-white"
                                    v-if="element.due_date"
                                >
                                    @{{ element.due_date }}
                                </div>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-project-tasks-kanban', {
            template: '#v-project-tasks-kanban-template',

            props: ['projectId'],

            data() {
                return {
                    columns: [
                        { value: 'pending', label: "@lang('admin::app.projects.tasks.datagrid.status-pending')" },
                        { value: 'in_progress', label: "@lang('admin::app.projects.tasks.datagrid.status-in-progress')" },
                        { value: 'done', label: "@lang('admin::app.projects.tasks.datagrid.status-done')" },
                    ],

                    tasksByStatus: {
                        pending: [],
                        in_progress: [],
                        done: [],
                    },
                };
            },

            mounted() {
                this.load();
            },

            methods: {
                load() {
                    this.$axios
                        .get("{{ route('admin.projects.tasks.kanban', $project->id) }}")
                        .then((response) => {
                            this.tasksByStatus = response.data;
                        })
                        .catch((error) => console.log(error));
                },

                handleChange(status, event) {
                    if (! event.added) {
                        return;
                    }

                    this.$axios
                        .put(
                            "{{ route('admin.projects.tasks.status', [$project->id, '__TASK_ID__']) }}".replace('__TASK_ID__', event.added.element.id),
                            { status: status }
                        )
                        .then((response) => {
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch((error) => {
                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });

                            this.load();
                        });
                },
            },
        });
    </script>
@endPushOnce
