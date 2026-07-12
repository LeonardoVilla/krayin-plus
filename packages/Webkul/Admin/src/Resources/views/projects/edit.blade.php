<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.projects.edit.title')
    </x-slot>

    <x-admin::form
        method="PUT"
        :action="route('admin.projects.update', $project->id)"
    >
        <div class="flex flex-col gap-4">
            <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs name="projects.edit" />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.projects.edit.title')
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    @lang('admin::app.projects.edit.save-btn')
                </button>
            </div>

            <div class="box-shadow flex max-w-[600px] flex-col gap-4 rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.projects.create.name')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        id="name"
                        name="name"
                        rules="required"
                        :value="old('name', $project->name)"
                        :label="trans('admin::app.projects.create.name')"
                        :placeholder="trans('admin::app.projects.create.name')"
                    />

                    <x-admin::form.control-group.error control-name="name" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('admin::app.projects.create.description')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        id="description"
                        name="description"
                        :value="old('description', $project->description)"
                        :label="trans('admin::app.projects.create.description')"
                        :placeholder="trans('admin::app.projects.create.description')"
                    />
                </x-admin::form.control-group>

                <div class="flex gap-4">
                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.projects.create.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="status"
                            name="status"
                            rules="required"
                            :value="old('status', $project->status)"
                            :label="trans('admin::app.projects.create.status')"
                        >
                            <option value="planning">@lang('admin::app.projects.index.datagrid.status-planning')</option>
                            <option value="in_progress">@lang('admin::app.projects.index.datagrid.status-in-progress')</option>
                            <option value="completed">@lang('admin::app.projects.index.datagrid.status-completed')</option>
                            <option value="cancelled">@lang('admin::app.projects.index.datagrid.status-cancelled')</option>
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.projects.create.owner')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="user_id"
                            name="user_id"
                            :value="old('user_id', $project->user_id)"
                            :label="trans('admin::app.projects.create.owner')"
                        >
                            <option value="">-</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>
                </div>

                <div class="flex gap-4">
                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.projects.create.start-date')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="date"
                            id="start_date"
                            name="start_date"
                            :value="old('start_date', optional($project->start_date)->format('Y-m-d'))"
                            :label="trans('admin::app.projects.create.start-date')"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.projects.create.end-date')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="date"
                            id="end_date"
                            name="end_date"
                            :value="old('end_date', optional($project->end_date)->format('Y-m-d'))"
                            :label="trans('admin::app.projects.create.end-date')"
                        />

                        <x-admin::form.control-group.error control-name="end_date" />
                    </x-admin::form.control-group>
                </div>

                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.projects.create.lead')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        id="lead_id"
                        name="lead_id"
                        :value="old('lead_id', $project->lead_id)"
                        :label="trans('admin::app.projects.create.lead')"
                    >
                        <option value="">-</option>
                        @foreach ($leads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->title }}</option>
                        @endforeach
                    </x-admin::form.control-group.control>
                </x-admin::form.control-group>
            </div>
        </div>
    </x-admin::form>

    <div class="mt-4 flex flex-col gap-4 rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.projects.tasks.title')
            </p>

            <a
                href="{{ route('admin.projects.tasks.create', $project->id) }}"
                class="secondary-button"
            >
                @lang('admin::app.projects.tasks.create-btn')
            </a>
        </div>

        @include('admin::projects.tasks.kanban', ['project' => $project])
    </div>
</x-admin::layouts>
