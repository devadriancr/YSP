@props(['loading' => null])

{{-- loading: Alpine expression that shows the spinner and disables the button, e.g. loading="sending" --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold text-white transition disabled:cursor-wait disabled:opacity-70']) }}
    @if ($loading) :disabled="{{ $loading }}" @endif>
    @if ($loading)
        <svg x-show="{{ $loading }}" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
        </svg>
    @endif
    {{ $slot }}
</button>
