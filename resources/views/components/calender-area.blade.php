@props([
    // [['start' => 'Y-m-d', 'end' => 'Y-m-d' (optional, exclusive), 'title' => '', 'type' => 'danger|primary|warning|success|weekend', 'display' => 'block|background']]
    'events' => [],
])

<div x-data="calendarDocuments({
        labelsUrl: @js(route('calendar.labels', ['date' => '__DATE__'])),
        diUrl: @js(route('calendar.delivery-instruction', ['date' => '__DATE__'])),
    })"
    @calendar-day.window="openDay($event.detail)"
    @documents-status="inforDown = !$event.detail.ok"
    data-calendar-card
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3']) }}>

    <div x-show="inforDown" x-cloak
        class="border-b border-warning-200 bg-warning-50 px-4 py-2.5 text-sm text-warning-700 sm:px-6 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-400">
        No se pudo consultar Infor, así que el calendario no muestra etiquetas ni DI. Recarga la página para intentarlo de nuevo.
    </div>

    <div class="custom-calendar relative">
        <div id="calendar" data-events='@json($events)' data-documents-url="{{ route('calendar.documents') }}"></div>
    </div>

    {{-- Day modal: documents available for the clicked day --}}
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
                class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-theme-lg motion-reduce:transition-none dark:border-gray-800 dark:bg-gray-900">

                <div class="flex items-start justify-between gap-4 px-5 pt-5 sm:px-6 sm:pt-6">
                    <div>
                        <p class="text-sm text-gray-500 capitalize dark:text-gray-400" x-text="weekday"></p>
                        <h3 id="day-modal-title" class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="longDate"></h3>
                    </div>
                    <button type="button" x-ref="closeButton" @click="close()" aria-label="Cerrar"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none dark:hover:bg-white/5 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                    </button>
                </div>

                <ul class="mt-4 divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-800 dark:border-gray-800">
                    {{-- Identification cards --}}
                    <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                            :class="day.labels > 0 ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400' : 'bg-gray-100 text-gray-400 dark:bg-white/5'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2" /><path d="M7 8h3v3H7zM14 8h3v3h-3zM7 14h3v3H7zM14 14h3v3h-3z" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Etiquetas de identificación</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"
                                x-text="day.labels > 0 ? `${day.labels} ${day.labels === 1 ? 'etiqueta' : 'etiquetas'} para las entregas de este día` : 'No hay entregas con etiquetas este día'"></p>
                        </div>
                        <button type="button" x-show="day.labels > 0" @click="download('labels')" :disabled="loading !== null"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-success-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-success-700 focus-visible:ring-2 focus-visible:ring-success-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-gray-900">
                            <svg x-show="loading !== 'labels'" xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                            <svg x-show="loading === 'labels'" class="size-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                            <span x-text="loading === 'labels' ? 'Generando…' : 'Descargar'"></span>
                        </button>
                    </li>

                    {{-- Delivery instruction --}}
                    <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                            :class="day.di > 0 ? 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400' : 'bg-gray-100 text-gray-400 dark:bg-white/5'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6M8 13h8M8 17h5" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">Instrucción de entrega (DI)</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"
                                x-text="day.di > 0 ? 'Programa de entregas a partir de este día' : 'No hay entregas programadas este día'"></p>
                        </div>
                        <button type="button" x-show="day.di > 0" @click="download('di')" :disabled="loading !== null"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-light-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-light-700 focus-visible:ring-2 focus-visible:ring-blue-light-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-gray-900">
                            <svg x-show="loading !== 'di'" xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" /></svg>
                            <svg x-show="loading === 'di'" class="size-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                            <span x-text="loading === 'di' ? 'Generando…' : 'Descargar'"></span>
                        </button>
                    </li>
                </ul>

                <p x-show="error" x-cloak x-text="error" role="alert"
                    class="mx-5 mb-5 rounded-lg bg-error-50 px-3 py-2 text-sm text-error-700 sm:mx-6 dark:bg-error-500/10 dark:text-error-400"></p>
            </div>
        </div>
    </template>
</div>

@once
    <script>
        function calendarDocuments({ labelsUrl, diUrl }) {
            const GENERIC_ERROR = 'No se pudo generar el documento. Revisa tu conexión e inténtalo de nuevo en unos minutos.';

            return {
                open: false,
                inforDown: false,
                loading: null,
                error: '',
                day: { date: '', labels: 0, di: 0 },

                get dateObj() {
                    return this.day.date ? new Date(`${this.day.date}T12:00:00`) : new Date();
                },
                get weekday() {
                    return this.dateObj.toLocaleDateString('es-MX', { weekday: 'long' });
                },
                get longDate() {
                    return this.dateObj.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
                },

                openDay(detail) {
                    this.day = { labels: 0, di: 0, ...detail };
                    this.error = '';
                    this.open = true;
                    this.$nextTick(() => this.$refs.closeButton?.focus());
                },
                close() {
                    if (this.loading) return;
                    this.open = false;
                },

                // Fetches the PDF so the button can show progress and errors stay in the modal
                async download(kind) {
                    const url = (kind === 'labels' ? labelsUrl : diUrl).replace('__DATE__', this.day.date);
                    this.loading = kind;
                    this.error = '';
                    try {
                        const response = await fetch(url, { headers: { Accept: 'application/pdf' } });
                        if (!response.ok) {
                            this.error = response.status === 404
                                ? 'Infor ya no tiene datos para este documento.'
                                : GENERIC_ERROR;
                            return;
                        }
                        const blob = await response.blob();
                        const disposition = response.headers.get('Content-Disposition') || '';
                        const match = disposition.match(/filename="?([^";]+)"?/);
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = match ? match[1] : `${kind}.pdf`;
                        link.click();
                        setTimeout(() => URL.revokeObjectURL(link.href), 1000);
                    } catch (e) {
                        this.error = GENERIC_ERROR;
                    } finally {
                        this.loading = null;
                    }
                },
            };
        }
    </script>
@endonce
