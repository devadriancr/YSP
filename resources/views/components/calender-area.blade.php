@props([
    // [['start' => 'Y-m-d', 'end' => 'Y-m-d' (optional, exclusive), 'title' => '', 'type' => 'danger|primary|warning|success|weekend', 'display' => 'block|background']]
    'events' => [],
    // Vendors of the user and the one being shown
    'vendors' => collect(),
    'vendor' => null,
    // Y-m-d: days before this one are past: shown greyed out, without downloads
    'availableFrom' => null,
    // Y-m-d: first day that can be viewed (the calendar goes back one month)
    'visibleFrom' => null,
])

@php
    $selectClass = 'shadow-theme-xs h-11 w-full appearance-none truncate rounded-lg border border-gray-300 bg-white py-2.5 ps-11 pe-10 text-sm font-medium text-gray-800 transition-colors hover:border-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:hover:border-gray-600 dark:focus:border-brand-800';
    $navClass = 'flex size-10 items-center justify-center rounded-lg border border-gray-200 text-gray-700 transition-colors hover:bg-gray-50 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-900';
@endphp

<div x-data="calendarDocuments({
        labelsUrl: @js(route('calendar.labels', ['date' => '__DATE__'])),
        diUrl: @js(route('calendar.delivery-instruction', ['date' => '__DATE__'])),
    })"
    @calendar-day.window="openDay($event.detail)"
    @documents-status="documentsDown = !$event.detail.ok"
    data-calendar-card
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/3']) }}>

    <div x-show="documentsDown" x-cloak
        class="border-b border-warning-200 bg-warning-50 px-4 py-2.5 text-sm text-warning-700 sm:px-6 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-400">
        {{ __('No se pudieron cargar las etiquetas y la instrucción de entrega. Si tu usuario no tiene un proveedor asignado, contacta a Compras; si no, recarga la página para intentarlo de nuevo.') }}
    </div>

    {{-- Toolbar: vendor on the left, month in the middle, navigation on the right (calendar-init.js drives it) --}}
    <div data-calendar-toolbar
        class="flex flex-wrap items-center gap-3 px-4 pt-4 pb-3 sm:grid sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:gap-4 sm:px-6 sm:pt-6 sm:pb-4">

        <div class="order-2 min-w-0 flex-1 sm:order-1 sm:max-w-sm">
            @if ($vendors->count() > 1)
                <form method="POST" action="{{ route('calendar.vendor') }}" x-data class="relative">
                    @csrf
                    <label for="vendor" class="sr-only">{{ __('Proveedor') }}</label>
                    <span class="pointer-events-none absolute start-3 top-1/2 flex size-7 -translate-y-1/2 items-center justify-center rounded-md bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01" /></svg>
                    </span>
                    <select id="vendor" name="vendor" @change="$el.form.submit()" class="{{ $selectClass }}">
                        @foreach ($vendors as $option)
                            <option value="{{ $option->number }}" @selected($option->is($vendor)) class="text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                {{ $option->number }} · {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute end-3.5 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                        <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.79 7.4 10 12.6l5.21-5.2" /></svg>
                    </span>
                    <noscript><button type="submit" class="mt-2 text-sm text-brand-600">{{ __('Cambiar') }}</button></noscript>
                </form>
            @elseif ($vendor)
                {{-- A single vendor: same look, nothing to choose --}}
                <div class="shadow-theme-xs flex h-11 items-center gap-3 rounded-lg border border-gray-200 bg-white ps-3 pe-4 dark:border-gray-800 dark:bg-gray-900">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01" /></svg>
                    </span>
                    <span class="truncate text-sm font-medium text-gray-800 dark:text-white/90">{{ $vendor->number }} · {{ $vendor->name }}</span>
                </div>
            @endif
        </div>

        <h2 data-calendar-title aria-live="polite"
            class="order-1 w-full text-base font-semibold text-gray-800 capitalize sm:order-2 sm:w-auto sm:text-center sm:text-lg dark:text-white/90"></h2>

        <div class="order-3 flex items-center gap-2 sm:justify-self-end">
            <button type="button" data-calendar-nav="today" title="{{ __('Ir a la semana actual') }}"
                class="h-10 rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-none disabled:cursor-default disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-900">
                {{ __('Hoy') }}
            </button>
            <button type="button" data-calendar-nav="prev" title="{{ __('Mes anterior') }}" aria-label="{{ __('Mes anterior') }}" class="{{ $navClass }}">
                <svg class="size-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
            </button>
            <button type="button" data-calendar-nav="next" title="{{ __('Mes siguiente') }}" aria-label="{{ __('Mes siguiente') }}" class="{{ $navClass }}">
                <svg class="size-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6" /></svg>
            </button>
        </div>
    </div>

    <div class="custom-calendar relative">
        <div id="calendar" data-events='@json($events)' data-documents-url="{{ route('calendar.documents') }}"
            data-available-from="{{ $availableFrom }}" data-visible-from="{{ $visibleFrom }}"
            data-i18n='@json(['labels' => __('Etiquetas'), 'di' => __('Instrucción de entrega'), 'more' => __('más')])'></div>
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
                    <button type="button" x-ref="closeButton" @click="close()" aria-label="{{ __('Cerrar') }}"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none dark:hover:bg-white/5 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                    </button>
                </div>

                <p x-show="day.past" x-cloak
                    class="mx-5 mt-4 flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-600 sm:mx-6 dark:bg-white/5 dark:text-gray-400">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                    {{ __('Esta fecha ya pasó: puedes ver lo que tuvo, pero ya no se puede descargar.') }}
                </p>

                <ul class="mt-4 divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-800 dark:border-gray-800">
                    {{-- Identification cards --}}
                    <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                            :class="day.labels > 0 ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400' : 'bg-gray-100 text-gray-400 dark:bg-white/5'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2" /><path d="M7 8h3v3H7zM14 8h3v3h-3zM7 14h3v3H7zM14 14h3v3h-3z" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ __('Etiquetas de identificación') }}</p>
                            <p x-show="day.labels === 0" class="text-sm text-gray-500 dark:text-gray-400"
                                x-text="day.past ? @js(__('No hubo entregas con etiquetas este día')) : @js(__('No hay entregas con etiquetas este día'))"></p>
                        </div>
                        <button type="button" x-show="day.labels > 0 && !day.past" @click="download('labels')" :disabled="isPending('labels')"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-success-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-success-700 focus-visible:ring-2 focus-visible:ring-success-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-gray-900">
                            <span x-show="!isPending('labels')"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" /></svg></span>
                            <span x-show="isPending('labels')" x-cloak><svg class="size-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg></span>
                            <span x-text="isPending('labels') ? TEXTS.preparing : TEXTS.download"></span>
                        </button>
                    </li>

                    {{-- Delivery instruction --}}
                    <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                            :class="day.di > 0 ? 'bg-blue-light-50 text-blue-light-600 dark:bg-blue-light-500/15 dark:text-blue-light-400' : 'bg-gray-100 text-gray-400 dark:bg-white/5'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" /><path d="M14 3v6h6M8 13h8M8 17h5" /></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ __('Instrucción de entrega') }}</p>
                            <p x-show="day.di === 0" class="text-sm text-gray-500 dark:text-gray-400"
                                x-text="day.past ? @js(__('No hubo entregas programadas este día')) : @js(__('No hay entregas programadas este día'))"></p>
                        </div>
                        <button type="button" x-show="day.di > 0 && !day.past" @click="download('di')" :disabled="isPending('di')"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-light-600 px-3.5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-light-700 focus-visible:ring-2 focus-visible:ring-blue-light-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-gray-900">
                            <span x-show="!isPending('di')"><svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14" /></svg></span>
                            <span x-show="isPending('di')" x-cloak><svg class="size-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg></span>
                            <span x-text="isPending('di') ? TEXTS.preparing : TEXTS.download"></span>
                        </button>
                    </li>
                </ul>

            </div>
        </div>
    </template>

    {{-- Floating notifications of the downloads (they run in the background) --}}
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
                        <span x-show="toast.kind === 'loading'"><svg class="size-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg></span>
                        <svg x-show="toast.kind === 'success'" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                        <svg x-show="toast.kind === 'error'" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="toast.title"></p>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400" x-text="toast.message"></p>
                    </div>
                    <button type="button" @click="dismiss(toast.id)" aria-label="{{ __('Cerrar notificación') }}"
                        class="flex size-7 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-white">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>

@once
    <script>
        // Texts in the page language; :name, :date and :file are filled in by fill()
        const TEXTS = {
            preparing: @js(__('Preparando…')),
            download: @js(__('Descargar')),
            preparingTitle: @js(__('Preparando :name')),
            preparingMessage: @js(__('Del :date. Se descargará en un momento; puedes seguir usando el calendario.')),
            readyTitle: @js(__(':name: lista')),
            readyMessage: @js(__('Se descargó :file.')),
            failedTitle: @js(__('No se pudo descargar: :name')),
            genericError: @js(__('Revisa tu conexión e inténtalo de nuevo en unos minutos.')),
            noData: @js(__('Ya no hay datos para este documento; la información se actualizó mientras tanto.')),
            sessionEnded: @js(__('Tu sesión terminó. Recarga la página e inicia sesión de nuevo.')),
            names: { labels: @js(__('Etiquetas de identificación')), di: @js(__('Instrucción de entrega')) },
        };
        const fill = (text, values) => text.replace(/:(\w+)/g, (match, key) => values[key] ?? match);
        const DATE_LOCALE = { es: 'es-MX', en: 'en-US', ja: 'ja-JP' }[@js(app()->getLocale())] || 'es-MX';

        function calendarDocuments({ labelsUrl, diUrl }) {
            const GENERIC_ERROR = TEXTS.genericError;
            const NAMES = TEXTS.names;
            let toastSeq = 0;

            return {
                open: false,
                documentsDown: false,
                day: { date: '', labels: 0, di: 0, past: false },
                // Downloads being prepared, by "kind-date"; a second click on the same one is ignored
                pending: {},
                toasts: [],

                get dateObj() {
                    return this.day.date ? new Date(`${this.day.date}T12:00:00`) : new Date();
                },
                get weekday() {
                    return this.dateObj.toLocaleDateString(DATE_LOCALE, { weekday: 'long' });
                },
                get longDate() {
                    return this.dateObj.toLocaleDateString(DATE_LOCALE, { day: 'numeric', month: 'long', year: 'numeric' });
                },

                openDay(detail) {
                    this.day = { labels: 0, di: 0, past: false, ...detail };
                    this.open = true;
                    this.$nextTick(() => this.$refs.closeButton?.focus());
                },
                close() {
                    this.open = false;
                },

                isPending(kind) {
                    return Boolean(this.pending[`${kind}-${this.day.date}`]);
                },

                // Notifications: "loading" stays until the download ends; the others close by themselves
                notify(toast) {
                    const id = ++toastSeq;
                    this.toasts.push({ id, ...toast });
                    this.autoDismiss(id, toast.kind);
                    return id;
                },
                update(id, changes) {
                    this.toasts = this.toasts.map((toast) => (toast.id === id ? { ...toast, ...changes } : toast));
                    this.autoDismiss(id, changes.kind);
                },
                autoDismiss(id, kind) {
                    if (kind === 'loading') return;
                    setTimeout(() => this.dismiss(id), kind === 'error' ? 10000 : 6000);
                },
                dismiss(id) {
                    this.toasts = this.toasts.filter((toast) => toast.id !== id);
                },

                // The modal closes at once and the PDF is prepared in the background:
                // a notification says it is on its way and another one when it is ready
                async download(kind) {
                    const date = this.day.date;
                    const key = `${kind}-${date}`;
                    if (this.pending[key]) return;

                    const name = NAMES[kind];
                    const when = this.longDate;
                    this.pending[key] = true;
                    this.close();

                    const id = this.notify({
                        kind: 'loading',
                        title: fill(TEXTS.preparingTitle, { name }),
                        message: fill(TEXTS.preparingMessage, { date: when }),
                    });

                    try {
                        const url = (kind === 'labels' ? labelsUrl : diUrl).replace('__DATE__', date);
                        const response = await fetch(url, { headers: { Accept: 'application/pdf' } });
                        if (!response.ok) {
                            this.update(id, {
                                kind: 'error',
                                title: fill(TEXTS.failedTitle, { name }),
                                message: response.status === 404
                                    ? TEXTS.noData
                                    : GENERIC_ERROR,
                            });
                            return;
                        }

                        // An expired session redirects to the sign in page instead of sending the PDF
                        if (!(response.headers.get('Content-Type') || '').includes('pdf')) {
                            this.update(id, {
                                kind: 'error',
                                title: fill(TEXTS.failedTitle, { name }),
                                message: TEXTS.sessionEnded,
                            });
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

                        this.update(id, { kind: 'success', title: fill(TEXTS.readyTitle, { name }), message: fill(TEXTS.readyMessage, { file: link.download }) });
                    } catch (e) {
                        this.update(id, { kind: 'error', title: fill(TEXTS.failedTitle, { name }), message: GENERIC_ERROR });
                    } finally {
                        delete this.pending[key];
                    }
                },
            };
        }
    </script>
@endonce
