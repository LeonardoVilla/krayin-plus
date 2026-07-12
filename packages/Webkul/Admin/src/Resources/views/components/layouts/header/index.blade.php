<header class="sticky top-0 z-[10001] flex items-center justify-between gap-1 border-b px-4 py-2.5 transition-all {{ session()->has('impersonator_id') ? 'bg-yellow-400 border-yellow-600 dark:bg-yellow-500 dark:border-yellow-700' : 'bg-white border-gray-300 dark:border-gray-800 dark:bg-gray-900' }}">
    <!-- logo -->
    <div class="flex items-center gap-1.5">
        <!-- Sidebar Menu -->
        <x-admin::layouts.sidebar.mobile />
        
        <a href="{{ route('admin.dashboard.index') }}">
            @if ($logo = core()->getConfigData('general.general.admin_logo.logo_image'))
                <img
                    class="h-10"
                    src="{{ Storage::url($logo) }}"
                    alt="{{ config('app.name') }}"
                />
            @else
                <img
                    class="h-10 max-sm:hidden"
                    src="{{ request()->cookie('dark_mode') ? vite()->asset('images/dark-logo.svg') : vite()->asset('images/logo.svg') }}"
                    id="logo-image"
                    alt="{{ config('app.name') }}"
                />

                <img
                    class="h-10 sm:hidden"
                    src="{{ request()->cookie('dark_mode') ? vite()->asset('images/mobile-dark-logo.svg') : vite()->asset('images/mobile-light-logo.svg') }}"
                    id="logo-image"
                    alt="{{ config('app.name') }}"
                />
            @endif
        </a>
    </div>

    <div class="flex items-center gap-1.5 max-md:hidden">
        <!-- Mega Search Bar -->
        @include('admin::components.layouts.header.desktop.mega-search')

        <!-- Quick Creation Bar -->
        @include('admin::components.layouts.header.quick-creation')
    </div>

    <div class="flex items-center gap-2.5">
        <div class="md:hidden">
            <!-- Mega Search Bar -->
            @include('admin::components.layouts.header.mobile.mega-search')
        </div>
        
        <!-- Dark mode -->
        <v-dark>
            <div class="flex">
                <span
                    class="{{ request()->cookie('dark_mode') ? 'icon-light' : 'icon-dark' }} p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-950"
                ></span>
            </div>
        </v-dark>

        @php($currentUser = auth()->guard('user')->user())
        @if ($currentUser->role && $currentUser->role->permission_type === 'all')
            <v-impersonate-quick></v-impersonate-quick>
        @endif

        <div class="md:hidden">
            <!-- Quick Creation Bar -->
            @include('admin::components.layouts.header.quick-creation')
        </div>

        @if (session()->has('impersonator_id'))
            <div class="flex items-center gap-2 rounded-md bg-gray-900 px-3 py-1.5 text-sm text-white">
                <span class="whitespace-nowrap">
                    Simulando: <strong>{{ auth()->guard('user')->user()->name }}</strong>
                </span>

                <a
                    href="{{ route('admin.settings.users.impersonate.stop') }}"
                    class="whitespace-nowrap rounded bg-yellow-400 px-2 py-0.5 font-semibold text-gray-900 hover:bg-yellow-300"
                >
                    Encerrar simulação
                </a>
            </div>
        @endif

        <!-- Admin profile -->
        <x-admin::dropdown position="bottom-{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'left' : 'right' }}">
            <x-slot:toggle>
                @php($user = auth()->guard('user')->user())

                @if ($user->image)
                    <button class="flex h-9 w-9 cursor-pointer overflow-hidden rounded-full hover:opacity-80 focus:opacity-80">
                        <img
                            src="{{ $user->image_url }}"
                            class="h-full w-full object-cover"
                        />
                    </button>
                @else
                    <button class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-pink-400 font-semibold leading-6 text-white">
                        {{ substr($user->name, 0, 1) }}
                    </button>
                @endif
            </x-slot>

            <!-- Admin Dropdown -->
            <x-slot:content class="mt-2 border-t-0 !p-0">
                <div class="flex items-center gap-1.5 border border-x-0 border-b-gray-300 px-5 py-2.5 dark:border-gray-800">
                    @if ($logo = core()->getConfigData('general.general.admin_logo.logo_image'))
                        <img
                            src="{{ Storage::url($logo) }}"
                            alt="{{ config('app.name') }}"
                            width="24"
                            height="24"
                        />
                    @else
                        <img
                            src="{{ request()->cookie('dark_mode') ? vite()->asset('images/dark-logo.svg') : vite()->asset('images/logo.svg') }}"
                            id="logo-image"
                            alt="{{ config('app.name') }}"
                            width="24"
                            height="24"
                        />
                    @endif

                    <!-- Version -->
                    <p class="text-gray-400">
                        @lang('admin::app.layouts.app-version', ['version' => core()->version()])
                    </p>
                </div>

                <div class="grid gap-1 pb-2.5">
                    <a
                        class="cursor-pointer px-5 py-2 text-base text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950"
                        href="{{ route('admin.user.account.edit') }}"
                    >
                        @lang('admin::app.layouts.my-account')
                    </a>

                    <!--Admin logout-->
                    <x-admin::form
                        method="DELETE"
                        action="{{ route('admin.session.destroy') }}"
                        id="adminLogout"
                    >
                    </x-admin::form>

                    <a
                        class="cursor-pointer px-5 py-2 text-base text-gray-800 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950"
                        href="{{ route('admin.session.destroy') }}"
                        onclick="event.preventDefault(); document.getElementById('adminLogout').submit();"
                    >
                        @lang('admin::app.layouts.sign-out')
                    </a>
                </div>
            </x-slot>
        </x-admin::dropdown>
    </div>
</header>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dark-template"
    >
        <div class="flex">
            <span
                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-100 dark:hover:bg-gray-950"
                :class="[isDarkMode ? 'icon-light' : 'icon-dark']"
                @click="toggle"
            ></span>
        </div>
    </script>

    <script type="module">
        app.component('v-dark', {
            template: '#v-dark-template',

            data() {
                return {
                    isDarkMode: {{ request()->cookie('dark_mode') ?? 0 }},

                    logo: "{{ vite()->asset('images/logo.svg') }}",

                    dark_logo: "{{ vite()->asset('images/dark-logo.svg') }}",
                };
            },

            methods: {
                toggle() {
                    this.isDarkMode = parseInt(this.isDarkModeCookie()) ? 0 : 1;

                    var expiryDate = new Date();

                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'dark_mode=' + this.isDarkMode + '; path=/; expires=' + expiryDate.toGMTString();

                    document.documentElement.classList.toggle('dark', this.isDarkMode === 1);

                    if (this.isDarkMode) {
                        this.$emitter.emit('change-theme', 'dark');

                        document.getElementById('logo-image').src = this.dark_logo;
                    } else {
                        this.$emitter.emit('change-theme', 'light');

                        document.getElementById('logo-image').src = this.logo;
                    }
                },

                isDarkModeCookie() {
                    const cookies = document.cookie.split(';');

                    for (const cookie of cookies) {
                        const [name, value] = cookie.trim().split('=');

                        if (name === 'dark_mode') {
                            return value;
                        }
                    }

                    return 0;
                },
            },
        });
    </script>

    <script
        type="text/x-template"
        id="v-impersonate-quick-template"
    >
        <div class="flex">
            <span
                class="icon-eye cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-100 dark:hover:bg-gray-950"
                title="Simular Usuário"
                @click="open"
            ></span>
        </div>

        <x-admin::modal ref="impersonateModal">
            <x-slot:header>
                <p class="text-lg font-bold text-gray-800 dark:text-white">
                    Simular Usuário
                </p>
            </x-slot>

            <x-slot:content>
                <div v-if="isLoading" class="py-4 text-center text-gray-500">
                    Carregando...
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            <th class="py-2">Nome</th>
                            <th class="py-2">Status</th>
                            <th class="py-2 text-right">Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="py-2 text-gray-800 dark:text-white">@{{ user.name }}</td>

                            <td class="py-2">
                                <span :class="user.status == 1 ? 'label-active' : 'label-inactive'">
                                    @{{ user.status == 1 ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>

                            <td class="py-2 text-right">
                                <a :href="`{{ url('admin/settings/users') }}/${user.id}/impersonate`">
                                    <span
                                        class="icon-eye cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                        title="Simular"
                                    ></span>
                                </a>
                            </td>
                        </tr>

                        <tr v-if="! users.length">
                            <td colspan="3" class="py-4 text-center text-gray-500">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </x-slot>
        </x-admin::modal>
    </script>

    <script type="module">
        app.component('v-impersonate-quick', {
            template: '#v-impersonate-quick-template',

            data() {
                return {
                    isLoading: false,

                    users: [],
                };
            },

            methods: {
                open() {
                    this.$refs.impersonateModal.toggle();

                    this.isLoading = true;

                    this.$axios
                        .get("{{ route('admin.settings.users.impersonate.list') }}")
                        .then((response) => {
                            this.users = response.data.data;

                            this.isLoading = false;
                        })
                        .catch(() => {
                            this.isLoading = false;
                        });
                },
            },
        });
    </script>
@endPushOnce
