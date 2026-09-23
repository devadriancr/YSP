@props(['title', 'description' => null, 'bag' => null, 'statuses' => []])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 mb-5 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @else
        <div class="mb-5"></div>
    @endif

    @if (isset($statuses[session('status')]))
        <div class="mb-5 rounded-lg border border-success-500 bg-success-50 px-4 py-3 text-sm text-success-600 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-500">
            {{ $statuses[session('status')] }}
        </div>
    @endif

    @if ($bag && $errors->getBag($bag)->any())
        <div class="mb-5 rounded-lg border border-error-500 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400">
            {{ $errors->getBag($bag)->first() }}
        </div>
    @endif

    {{ $slot }}
</div>
