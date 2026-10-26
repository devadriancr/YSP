{{-- Floating notifications of the downloads, which run in the background (calendar-documents.js) --}}
<template x-teleport="body">
    <div class="pointer-events-none fixed end-4 bottom-4 z-999999 flex w-[min(380px,calc(100vw-2rem))] flex-col gap-3" aria-live="polite">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-theme-lg dark:bg-gray-900"
                :class="toast.kind === 'error' ? 'border-error-200 dark:border-error-500/30' : 'border-gray-200 dark:border-gray-800'"
                :role="toast.kind === 'error' ? 'alert' : 'status'">
                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400': toast.kind === 'loading',
                        'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400': toast.kind === 'success',
                        'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400': toast.kind === 'error',
                    }">
                    <x-svg.spinner x-show="toast.kind === 'loading'" />
                    <svg x-show="toast.kind === 'success'" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                    <x-svg.close x-show="toast.kind === 'error'" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="toast.title"></p>
                    <p class="mt-0.5 text-sm break-words text-gray-500 dark:text-gray-400" x-text="toast.message"></p>
                </div>
                <x-ui.icon-button :label="__('Cerrar notificación')" variant="ghost" size="sm" x-on:click="dismiss(toast.id)">
                    <x-svg.close />
                </x-ui.icon-button>
            </div>
        </template>
    </div>
</template>
