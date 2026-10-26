{{-- Day modal: the documents of the clicked day; state and actions live in calendar-documents.js --}}
<template x-teleport="body">
    <div x-show="open" x-cloak @keydown.escape.window="close()"
        class="fixed inset-0 z-99999 flex items-end justify-center p-4 sm:items-center sm:p-6"
        role="dialog" aria-modal="true" aria-labelledby="day-modal-title">
        <div @click="close()" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm" x-transition.opacity></div>

        <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="relative flex max-h-[calc(100dvh-2rem)] w-full flex-col rounded-2xl border border-gray-200 bg-white shadow-theme-lg sm:max-h-[calc(100dvh-3rem)] dark:border-gray-800 dark:bg-gray-900"
            :class="view ? 'max-w-3xl' : 'max-w-md'">

            {{-- Header: the day, its kind in the work calendar and close --}}
            <div class="flex items-start gap-3 px-5 pt-5 pb-4 sm:px-6">
                <x-ui.icon-button x-show="view" x-cloak :label="__('Volver')" variant="ghost" x-on:click="hideDetails()" class="-ms-2">
                    <x-svg.chevron-left />
                </x-ui.icon-button>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-500 capitalize dark:text-gray-400"
                        x-text="view ? (view === 'labels' ? texts.names.labels : texts.names.di) : weekday"></p>
                    <h3 id="day-modal-title" class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="longDate"></h3>
                    <span x-show="day.workday && !view" x-cloak
                        class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="{
                            'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400': day.workday === 'holiday',
                            'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400': day.workday === 'half_shift',
                            'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400': day.workday === 'rest' || day.workday === 'other',
                        }">
                        <span class="size-1.5 rounded-full bg-current"></span>
                        <span x-text="texts.workdays[day.workday] || ''"></span>
                    </span>
                </div>
                <x-ui.icon-button x-ref="closeButton" :label="__('Cerrar')" variant="ghost" x-on:click="close()" class="-me-2">
                    <x-svg.close class="size-5" />
                </x-ui.icon-button>
            </div>

            {{-- The documents of the day; no overflow clipping, so the format menu can open over the rows --}}
            <div x-show="!view" class="border-t border-gray-100 dark:border-gray-800">
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    <x-calendar.document-row kind="labels" :title="__('Etiquetas de identificación')"
                        :empty="__('No hay entregas con etiquetas este día')" :emptyPast="__('No hubo entregas con etiquetas este día')">
                        <x-slot:icon>
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2" /><path d="M7 8h3v3H7zM14 8h3v3h-3zM7 14h3v3H7zM14 14h3v3h-3z" /></svg>
                        </x-slot:icon>
                    </x-calendar.document-row>

                    <x-calendar.document-row kind="di" tone="blue-light" excel :title="__('Instrucción de entrega')"
                        :empty="__('No hay entregas programadas este día')" :emptyPast="__('No hubo entregas programadas este día')">
                        <x-slot:icon>
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6M8 13h8M8 17h5" /></svg>
                        </x-slot:icon>
                    </x-calendar.document-row>
                </ul>
            </div>

            {{-- Details: what the document contains, to look at (no QR or barcodes) --}}
            <div x-show="view" x-cloak class="custom-scrollbar min-h-0 flex-1 overflow-auto border-t border-gray-100 px-5 py-5 sm:px-6 dark:border-gray-800">
                <p x-show="loadingDetails" class="flex items-center gap-2 py-8 text-sm text-gray-500 dark:text-gray-400">
                    <x-svg.spinner /> {{ __('Cargando…') }}
                </p>
                <p x-show="detailsError" class="rounded-lg bg-error-50 px-3 py-2 text-sm text-error-600 dark:bg-error-500/15 dark:text-error-400">
                    {{ __('No se pudo cargar el detalle. Inténtalo de nuevo.') }}
                </p>

                <template x-if="view === 'labels' && details">
                    <x-calendar.labels-details />
                </template>
                <template x-if="view === 'di' && details">
                    <x-calendar.delivery-details />
                </template>
            </div>
        </div>
    </div>
</template>
