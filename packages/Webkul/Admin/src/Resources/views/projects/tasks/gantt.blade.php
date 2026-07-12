{!! view_render_event('admin.projects.tasks.gantt.before') !!}

<v-project-tasks-gantt :project-id="{{ $project->id }}">
</v-project-tasks-gantt>

{!! view_render_event('admin.projects.tasks.gantt.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-project-tasks-gantt-template"
    >
        <div>
            <div
                v-if="! isLoading && ! tasks.length"
                class="flex items-center justify-center rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400"
            >
                @lang('admin::app.projects.tasks.gantt.empty')
            </div>

            <div
                v-show="tasks.length"
                ref="ganttContainer"
                class="gantt-container overflow-x-auto"
            ></div>
        </div>
    </script>

    <script type="module">
        app.component('v-project-tasks-gantt', {
            template: '#v-project-tasks-gantt-template',

            props: ['projectId'],

            data() {
                return {
                    tasks: [],
                    isLoading: true,
                    ganttInstance: null,
                };
            },

            mounted() {
                this.load();
            },

            methods: {
                load() {
                    this.$axios
                        .get("{{ route('admin.projects.tasks.gantt', $project->id) }}")
                        .then((response) => {
                            this.isLoading = false;

                            this.tasks = response.data;

                            this.$nextTick(() => this.render());
                        })
                        .catch((error) => console.log(error));
                },

                render() {
                    if (! this.tasks.length || ! this.$refs.ganttContainer) {
                        return;
                    }

                    this.ganttInstance = new this.$Gantt(this.$refs.ganttContainer, this.tasks, {
                        view_mode: 'Day',
                        language: 'pt-BR',
                        on_date_change: (task, start, end) => this.handleDateChange(task, start, end),
                    });
                },

                handleDateChange(task, start, end) {
                    const format = (date) => {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');

                        return `${year}-${month}-${day}`;
                    };

                    this.$axios
                        .put(
                            "{{ route('admin.projects.tasks.dates', [$project->id, '__TASK_ID__']) }}".replace('__TASK_ID__', task.id),
                            {
                                start_date: format(start),
                                due_date: format(end),
                            }
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
