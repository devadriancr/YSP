<button {{ $attributes->merge(['type' => 'submit', 'class' => 'bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex w-full items-center justify-center rounded-lg px-4 py-3 text-sm font-medium text-white transition']) }}>
    {{ $slot }}
</button>
