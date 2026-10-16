<div class="relative" x-data="{
    isOpen: false,
    subDropdownOpen: false,
    currentLocale: '{{ app()->getLocale() }}',
    languages: [
        { id: 'es', name: 'Español', shortName: 'ES', flag: 'flag-mx.svg', dir: 'ltr' },
        { id: 'en', name: 'English', shortName: 'EN', flag: 'flag-us.svg', dir: 'ltr' },
        { id: 'ja', name: '日本語', shortName: 'JA', flag: 'flag-jp.svg', dir: 'ltr' },
    ],
    get currentLang() {
        return this.languages.find(l => l.id === this.currentLocale) || this.languages[0];
    },
    toggleDropdown() {
        this.isOpen = !this.isOpen;
        if (!this.isOpen) {
            this.subDropdownOpen = false;
        }
    },
    closeDropdown() {
        this.isOpen = false;
        this.subDropdownOpen = false;
    },
    selectLanguage(lang) {
        this.currentLocale = lang.id;
        const dir = lang.dir || 'ltr';
        localStorage.setItem('locale', lang.id);
        localStorage.setItem('dir', dir);
        document.documentElement.setAttribute('dir', dir);
        document.documentElement.setAttribute('lang', lang.id);
        window.location.href = '/locale/' + lang.id;
    }
}" @click.outside="closeDropdown()">
    <!-- User Trigger -->
    <button
        class="flex items-center text-gray-700 dark:text-gray-400"
        type="button"
        @click="toggleDropdown()"
    >
        <x-common.user-avatar class="me-3" />

        <span class="block me-1 font-medium text-theme-sm">{{ Str::before(auth()->user()->name, ' ') }}</span>

        <!-- Chevron Down Icon -->
        <svg
            :class="isOpen ? 'rotate-180' : ''"
            class="transition-transform duration-200 stroke-gray-500 group-hover:stroke-gray-700 dark:stroke-gray-400 dark:group-hover:stroke-gray-200"
            width="18"
            height="18"
            viewBox="0 0 18 18"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M4.5 6.75L9 11.25L13.5 6.75"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute ltr:right-0 rtl:left-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark z-50"
        style="display: none;"
    >
        <!-- User Info -->
        <div class="flex items-center gap-3">
            <x-common.user-avatar size="size-10 text-sm" />
            <div class="min-w-0">
                <span class="block truncate font-medium text-gray-700 text-theme-sm dark:text-gray-400">{{ auth()->user()->name }}</span>
                <span class="mt-0.5 block truncate text-theme-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</span>
            </div>
        </div>

        <!-- Menu Items -->
        <ul class="flex flex-col gap-1 pt-4 pb-3 border-b border-gray-200 dark:border-gray-800">
            <li>
                <a
                    href="/profile"
                    class="flex items-center gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.5C7.30558 3.5 3.5 7.30558 3.5 12C3.5 14.1526 4.3002 16.1184 5.61936 17.616C6.17279 15.3096 8.24852 13.5955 10.7246 13.5955H13.2746C15.7509 13.5955 17.8268 15.31 18.38 17.6167C19.6996 16.119 20.5 14.153 20.5 12C20.5 7.30558 16.6944 3.5 12 3.5ZM17.0246 18.8566V18.8455C17.0246 16.7744 15.3457 15.0955 13.2746 15.0955H10.7246C8.65354 15.0955 6.97461 16.7744 6.97461 18.8455V18.856C8.38223 19.8895 10.1198 20.5 12 20.5C13.8798 20.5 15.6171 19.8898 17.0246 18.8566ZM2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12ZM11.9991 7.25C10.8847 7.25 9.98126 8.15342 9.98126 9.26784C9.98126 10.3823 10.8847 11.2857 11.9991 11.2857C13.1135 11.2857 14.0169 10.3823 14.0169 9.26784C14.0169 8.15342 13.1135 7.25 11.9991 7.25ZM8.48126 9.26784C8.48126 7.32499 10.0563 5.75 11.9991 5.75C13.9419 5.75 15.5169 7.32499 15.5169 9.26784C15.5169 11.2107 13.9419 12.7857 11.9991 12.7857C10.0563 12.7857 8.48126 11.2107 8.48126 9.26784Z" fill="currentColor" />
                        </svg>
                    </span>
                    {{ __('Editar perfil') }}
                </a>
            </li>

            <!-- Language / RTL Submenu Item -->
            <li class="relative" @click.outside="subDropdownOpen = false">
                <button
                    type="button"
                    @click.stop="subDropdownOpen = !subDropdownOpen"
                    class="group flex max-h-10 w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-theme-sm font-medium transition-colors"
                    :class="subDropdownOpen
                        ? 'bg-gray-100 text-gray-900 dark:bg-white/5 dark:text-white'
                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300'"
                >
                    <span class="flex items-center gap-3 text-theme-sm">
                        <svg class="stroke-gray-500 group-hover:stroke-gray-700 dark:stroke-gray-400 dark:group-hover:stroke-gray-300"
                            width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12.001 2.75C17.1091 2.75 21.2501 6.89178 21.2501 11.9999C21.2501 17.108 17.1091 21.2498 12.001 21.2498M12.001 2.75C6.89289 2.75 2.75195 6.89178 2.75195 11.9999C2.75195 17.108 6.8929 21.2498 12.001 21.2498M12.001 2.75C14.2097 2.75 16.0005 6.8914 16.0005 11.9993C16.0005 17.1073 14.2098 21.2498 12.001 21.2498M12.001 2.75C9.79226 2.75 8.00195 6.89141 8.00195 11.9994C8.00195 17.1073 9.79226 21.2498 12.001 21.2498M3.24561 8.99976H20.7544M3.24561 14.9998H20.7544"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <span>{{ __('Idioma') }}</span>
                    </span>

                    <span
                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-theme-xs font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300"
                    >
                        <span x-text="currentLang.shortName"></span>
                        <img :src="'/images/icons/' + currentLang.flag" :alt="currentLang.shortName" class="size-3.5 shrink-0 overflow-hidden rounded-full object-cover" />
                    </span>
                </button>

                <!-- Submenu Flyout -->
                <div x-show="subDropdownOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute top-11 ltr:-left-2 rtl:-right-2 w-[250px] rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg md:top-0 ltr:md:right-[calc(100%+14px)] ltr:md:left-auto rtl:md:left-[calc(100%+14px)] rtl:md:right-auto dark:border-gray-800 dark:bg-gray-dark z-50"
                    style="display: none;"
                >
                    <ul class="flex flex-col gap-1">
                        <template x-for="lang in languages" :key="lang.id">
                            <li>
                                <button
                                    type="button"
                                    @click="selectLanguage(lang)"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 ltr:text-left rtl:text-right text-theme-sm font-medium transition-colors"
                                    :class="currentLocale === lang.id
                                        ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white'"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-1.5 shrink-0 rounded-full transition-opacity"
                                            :class="currentLocale === lang.id ? 'bg-brand-500 opacity-100 dark:bg-brand-400' : 'opacity-0'"></span>
                                        <img :src="'/images/icons/' + lang.flag" :alt="lang.name" class="size-5 shrink-0 overflow-hidden rounded-full object-cover" />
                                        <span class="truncate" x-text="lang.name"></span>
                                    </span>

                                    <template x-if="lang.badge">
                                        <span
                                            class="rounded bg-warning-50 px-1.5 py-0.5 text-theme-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-400"
                                            x-text="lang.badge">
                                        </span>
                                    </template>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </li>
        </ul>

        <!-- Sign Out -->
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button
                type="submit"
                class="flex items-center w-full gap-3 px-3 py-2 font-medium text-gray-700 rounded-lg group text-theme-sm hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                @click="closeDropdown()"
            >
                <span class="text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </span>
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</div>
