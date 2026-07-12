<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.support-tickets.edit.title')
    </x-slot>

    <x-admin::form
        method="PUT"
        :action="route('admin.support_tickets.update', $ticket->id)"
    >
        <div class="flex flex-col gap-4">
            <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs name="support_tickets.edit" />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.support-tickets.edit.title')
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    @lang('admin::app.support-tickets.edit.save-btn')
                </button>
            </div>

            <div class="box-shadow flex max-w-[600px] flex-col gap-4 rounded-lg border border-gray-300 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('admin::app.support-tickets.create.subject')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        id="subject"
                        name="subject"
                        rules="required"
                        :value="old('subject', $ticket->subject)"
                        :label="trans('admin::app.support-tickets.create.subject')"
                        :placeholder="trans('admin::app.support-tickets.create.subject')"
                    />

                    <x-admin::form.control-group.error control-name="subject" />
                </x-admin::form.control-group>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('admin::app.support-tickets.create.description')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        id="description"
                        name="description"
                        :value="old('description', $ticket->description)"
                        :label="trans('admin::app.support-tickets.create.description')"
                        :placeholder="trans('admin::app.support-tickets.create.description')"
                    />
                </x-admin::form.control-group>

                <div class="flex gap-4">
                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.support-tickets.create.status')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="status"
                            name="status"
                            rules="required"
                            :value="old('status', $ticket->status)"
                            :label="trans('admin::app.support-tickets.create.status')"
                        >
                            <option value="open">@lang('admin::app.support-tickets.index.datagrid.status-open')</option>
                            <option value="in_progress">@lang('admin::app.support-tickets.index.datagrid.status-in-progress')</option>
                            <option value="resolved">@lang('admin::app.support-tickets.index.datagrid.status-resolved')</option>
                            <option value="closed">@lang('admin::app.support-tickets.index.datagrid.status-closed')</option>
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.support-tickets.create.priority')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="priority"
                            name="priority"
                            rules="required"
                            :value="old('priority', $ticket->priority)"
                            :label="trans('admin::app.support-tickets.create.priority')"
                        >
                            <option value="low">@lang('admin::app.support-tickets.index.datagrid.priority-low')</option>
                            <option value="medium">@lang('admin::app.support-tickets.index.datagrid.priority-medium')</option>
                            <option value="high">@lang('admin::app.support-tickets.index.datagrid.priority-high')</option>
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>
                </div>

                <div class="flex gap-4">
                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.support-tickets.create.person')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="person_id"
                            name="person_id"
                            :value="old('person_id', $ticket->person_id)"
                            :label="trans('admin::app.support-tickets.create.person')"
                        >
                            <option value="">-</option>
                            @foreach ($persons as $person)
                                <option value="{{ $person->id }}">{{ $person->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="flex-1">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.support-tickets.create.agent')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="user_id"
                            name="user_id"
                            :value="old('user_id', $ticket->user_id)"
                            :label="trans('admin::app.support-tickets.create.agent')"
                        >
                            <option value="">-</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
