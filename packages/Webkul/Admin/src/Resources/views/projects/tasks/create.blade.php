<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.projects.tasks.create.title')
    </x-slot>

    <x-admin::form :action="route('admin.projects.tasks.store', $project->id)">
        <div class="flex flex-col gap-4">
            <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <div class="text-xl font-bold dark:text-white">
                        {{ $project->name }} — @lang('admin::app.projects.tasks.create.title')
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    @lang('admin::app.projects.tasks.create.save-btn')
                </button>
            </div>

            <div class="box-shadow flex max-w-[600px] flex-col gap-4 rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.projects.tasks.create.title-field')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        id="title"
                        name="title"
                        rules="required"
                        :value="old('title')"
                        :label="trans('admin::app.projects.tasks.create.title-field')"
                        :placeholder="trans('admin::app.projects.tasks.create.title-field')"
                    />

                    <x-admin::form.control-group.error control-name="title" />
                </x-admin::form.control-group>

                <div class="flex gap-4">
                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.projects.tasks.create.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="status"
                            name="status"
                            rules="required"
                            :value="old('status', 'pending')"
                            :label="trans('admin::app.projects.tasks.create.status')"
                        >
                            <option value="pending">@lang('admin::app.projects.tasks.datagrid.status-pending')</option>
                            <option value="in_progress">@lang('admin::app.projects.tasks.datagrid.status-in-progress')</option>
                            <option value="done">@lang('admin::app.projects.tasks.datagrid.status-done')</option>
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.projects.tasks.create.due-date')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="date"
                            id="due_date"
                            name="due_date"
                            :value="old('due_date')"
                            :label="trans('admin::app.projects.tasks.create.due-date')"
                        />
                    </x-admin::form.control-group>
                </div>

                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.projects.tasks.create.responsible')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        id="user_id"
                        name="user_id"
                        :value="old('user_id')"
                        :label="trans('admin::app.projects.tasks.create.responsible')"
                    >
                        <option value="">-</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </x-admin::form.control-group.control>
                </x-admin::form.control-group>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
