@props([
    // [['start' => 'Y-m-d', 'end' => 'Y-m-d' (optional, exclusive), 'title' => '', 'type' => 'danger|primary|warning|success|weekend', 'display' => 'block|background']]
    'events' => [],
    // Vendors of the user and the one being shown
    'vendors' => collect(),
    'vendor' => null,
    // Y-m-d: days before this one are past: shown greyed out, without downloads (one week back)
    'availableFrom' => null,
    // Y-m-d: first day that can be viewed (January of this year)
    'visibleFrom' => null,
])

@php
    // Everything the Alpine component (resources/js/components/calendar-documents.js) needs
    $config = [
        'locale' => app()->getLocale(),
        'urls' => [
            'labels' => route('calendar.labels', ['date' => '__DATE__']),
            'di' => route('calendar.delivery-instruction', ['date' => '__DATE__']),
            'diExcel' => route('calendar.delivery-instruction.excel', ['date' => '__DATE__']),
            'details' => route('calendar.details', ['date' => '__DATE__']),
        ],
        'texts' => [
            'preparingTitle' => __('Preparando :name'),
            'preparingMessage' => __('Del :date. Se descargará en un momento; puedes seguir usando el calendario.'),
            'readyTitle' => __(':name: lista'),
            'readyMessage' => __('Se descargó :file.'),
            'failedTitle' => __('No se pudo descargar: :name'),
            'genericError' => __('Revisa tu conexión e inténtalo de nuevo en unos minutos.'),
            'noData' => __('Ya no hay datos para este documento; la información se actualizó mientras tanto.'),
            'sessionEnded' => __('Tu sesión terminó. Recarga la página e inicia sesión de nuevo.'),
            'names' => [
                'labels' => __('Etiquetas de identificación'),
                'di' => __('Instrucción de entrega'),
                'diExcel' => __('Instrucción de entrega (Excel)'),
            ],
            'downloadedBy' => __('Descargada el :date por :name'),
            'changedAfter' => __('Infor cambió estos datos después de la descarga del :date (:name). Vuelve a descargarla.'),
            'pieces' => __(':count pzs'),
            'boxes' => __(':count cajas'),
            'origins' => ['PO' => __('Orden de compra'), 'FP' => __('Firme'), 'FR' => __('Pronóstico')],
            'workdays' => ['holiday' => __('Día no laborable'), 'half_shift' => __('Medio turno'), 'rest' => __('Día de descanso'), 'other' => __('Día especial')],
        ],
    ];
@endphp

<div x-data="calendarDocuments(@js($config))"
    @calendar-day.window="openDay($event.detail)"
    @documents-status="documentsDown = !$event.detail.ok"
    data-calendar-card
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>

    <div x-show="documentsDown" x-cloak role="alert"
        class="border-b border-warning-200 bg-warning-50 px-4 py-2.5 text-sm text-warning-700 sm:px-6 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-400">
        {{ __('No se pudieron cargar los documentos. Recarga la página; si sigue igual, contacta a Compras.') }}
    </div>

    <x-calendar.toolbar :vendors="$vendors" :vendor="$vendor" />

    <div class="custom-calendar relative">
        <div id="calendar" data-events='@json($events)' data-documents-url="{{ route('calendar.documents') }}"
            data-available-from="{{ $availableFrom }}" data-visible-from="{{ $visibleFrom }}"
            data-i18n="{{ json_encode(['labels' => __('Etiquetas'), 'di' => __('Instrucción de entrega'), 'more' => __('más'), 'downloaded' => __('Descargada'), 'changed' => __('Cambió después de la descarga')]) }}"></div>
    </div>

    <x-calendar.day-modal />
    <x-calendar.download-toasts />
</div>
