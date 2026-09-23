@props(['sessions', 'supported' => true])

<x-profile.section :title="__('Sesiones del navegador')"
    :description="__('Cierra tus sesiones activas en otros dispositivos.')"
    bag="logoutOtherBrowserSessions"
    :statuses="['browser-sessions-logged-out' => __('Se cerraron tus otras sesiones.')]">
    @if (! $supported)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Requiere SESSION_DRIVER=database.') }}
        </p>
    @else
        <div class="mb-5 space-y-4">
            @foreach ($sessions as $session)
                <div class="flex items-center gap-4">
                    <div class="text-gray-500 dark:text-gray-400">
                        @if ($session->is_mobile)
                            <svg class="size-8 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-15a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        @else
                            <svg class="size-8 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm text-gray-800 dark:text-white/90">
                            {{ $session->platform }} - {{ $session->browser }}
                        </div>
                        <div class="text-theme-xs text-gray-500 dark:text-gray-400">
                            {{ $session->ip_address }},
                            @if ($session->is_current)
                                <span class="text-success-600 dark:text-success-500 font-semibold">{{ __('Este dispositivo') }}</span>
                            @else
                                {{ __('Última actividad') }} {{ $session->last_active }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (count($sessions) > 1)
            <form method="POST" action="{{ route('profile.browser-sessions.destroy') }}">
                @csrf
                <div class="max-w-xs space-y-5">
                    <x-auth.input :label="__('Contraseña')" name="password" id="sessions_password" type="password" autocomplete="current-password" />
                    <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white transition">
                        {{ __('Cerrar otras sesiones') }}
                    </button>
                </div>
            </form>
        @endif
    @endif
</x-profile.section>
