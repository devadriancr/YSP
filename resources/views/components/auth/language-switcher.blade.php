{{-- ES / EN / JA pills for the pages before signing in (the user dropdown is not there yet) --}}
<nav class="fixed end-6 top-6 z-50 flex gap-1 rounded-full border border-gray-200 bg-white p-1 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900" aria-label="{{ __('Idioma') }}">
    @foreach (\App\Http\Controllers\LocaleController::SUPPORTED_LOCALES as $code => $locale)
        <a href="{{ route('locale.switch', $code) }}" title="{{ $locale['native'] }}" lang="{{ $code }}"
            @if (app()->getLocale() === $code) aria-current="true" @endif
            @class([
                'flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                'bg-brand-500 text-white' => app()->getLocale() === $code,
                'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => app()->getLocale() !== $code,
            ])>
            <img src="/images/icons/flag-{{ $locale['flag'] }}.svg" alt="" class="size-4 rounded-full object-cover" />
            {{ strtoupper($code) }}
        </a>
    @endforeach
</nav>
