<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.projects.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="projects" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::app.projects.index.title')
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.projects.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.projects.index.create-btn')
                </a>
            </div>
        </div>

        <x-admin::datagrid :src="route('admin.projects.index')" />
    </div>
</x-admin::layouts>
