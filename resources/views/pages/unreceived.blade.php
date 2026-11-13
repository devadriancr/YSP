@extends('layouts.app')

@php
    $th = 'px-4 py-3 text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400';
    $td = 'px-4 py-3 text-sm whitespace-nowrap text-gray-700 dark:text-gray-300';
    $search = 'shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent ps-9 pe-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
@endphp

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('Partes no recibidas')" />

    {{-- Same card as x-common.component-card, with the vendor picker in its header --}}
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center gap-3 px-6 py-5">
            <h3 class="me-auto text-base font-medium text-gray-800 dark:text-white/90">{{ __('Partes no recibidas') }}</h3>
            <x-common.vendor-picker :vendors="$vendors" :vendor="$vendor" back="unreceived" />
        </div>

        <div class="border-t border-gray-100 p-4 sm:p-6 dark:border-gray-800">
            {{-- Lines that Infor no longer reports in the last days: received in full or deleted --}}
            @if ($removed !== [])
                <div x-data="{ open: false }" class="mb-4 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                        class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-start text-sm font-medium text-gray-700 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-none dark:text-gray-300">
                        <span>{{ trans_choice(':count línea ya no aparece|:count líneas ya no aparecen', count($removed)) }}
                            <span class="font-normal text-gray-500 dark:text-gray-400">· {{ __('Recibidas completas o eliminadas en Infor') }}</span></span>
                        <x-svg.chevron-down class="size-4 shrink-0 transition-transform" ::class="open && 'rotate-180'" />
                    </button>
                    <ul x-show="open" x-cloak class="divide-y divide-gray-100 border-t border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        @foreach ($removed as $line)
                            <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-2.5 text-sm">
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $line['part'] }}
                                    <span class="font-normal text-gray-500 tabular-nums dark:text-gray-400">· {{ $line['order'] }}</span></span>
                                <span class="text-xs text-gray-500 tabular-nums dark:text-gray-400">
                                    {{ __('Pendiente') }}: {{ number_format($line['before'] ?? 0, 3) }}
                                    · {{ \Carbon\Carbon::parse($line['at'])->timezone(config('app.timezone'))->format('d/m H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div x-data="{
                    tab: @js($lines['parts'] === [] && $lines['materials'] !== [] ? 'materials' : 'parts'),
                    query: '',
                    rows: @js($lines),
                    {{-- Same locales as the calendar (calendar-documents.js) --}}
                    numbers: new Intl.NumberFormat(@js(['es' => 'es-MX', 'en' => 'en-US', 'ja' => 'ja-JP'][app()->getLocale()] ?? 'es-MX'), { maximumFractionDigits: 3 }),
                    urls: @js(collect(['parts', 'materials'])->mapWithKeys(fn ($type) => [$type => [
                        'pdf' => route('unreceived.download', [$type, 'pdf']),
                        'excel' => route('unreceived.download', [$type, 'excel']),
                    ]])),
                    sort: 'oldest',
                    page: 1,
                    perPage: 10,
                    init() {
                        // A new tab, search or order starts on the first page
                        ['tab', 'query', 'sort'].forEach(key => this.$watch(key, () => this.page = 1));
                    },
                    get filtered() {
                        const q = this.query.trim().toLowerCase();
                        const direction = this.sort === 'oldest' ? 1 : -1;
                        return this.rows[this.tab]
                            .filter(row => !q
                                || [row.part, row.description, row.order, row.material, row.symbol].some(v => String(v ?? '').toLowerCase().includes(q)))
                            .sort((a, b) => direction * a.due_date.localeCompare(b.due_date) || a.part.localeCompare(b.part) || a.order.localeCompare(b.order));
                    },
                    get lastPage() { return Math.max(1, Math.ceil(this.filtered.length / this.perPage)); },
                    get visible() { return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage); },
                    // First, last and two around the current one, like x-common.pagination; gap marks an ellipsis
                    get pages() {
                        const around = [];
                        for (let n = Math.max(1, this.page - 2); n <= Math.min(this.lastPage, this.page + 2); n++) around.push(n);
                        const numbers = [...new Set([1, this.lastPage, ...around])].sort((a, b) => a - b);
                        return numbers.map((n, i) => ({ n, gap: i > 0 && n - numbers[i - 1] > 1 }));
                    },
                    get range() {
                        const first = (this.page - 1) * this.perPage + 1;
                        return @js(__(':first–:last de :total'))
                            .replace(':first', this.num(first))
                            .replace(':last', this.num(first + this.visible.length - 1))
                            .replace(':total', this.num(this.filtered.length));
                    },
                    num(value) { return this.numbers.format(value); },
                    date(value) { return value.split('-').reverse().join('/'); },
                    changeDate(at) {
                        return new Date(at).toLocaleString(@js(['es' => 'es-MX', 'en' => 'en-US', 'ja' => 'ja-JP'][app()->getLocale()] ?? 'es-MX'), { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
                    },
                    size(row) {
                        const sizes = [row.thickness, row.width, row.length].filter(v => v > 0).map(v => this.num(v));
                        return sizes.length ? sizes.join(' × ') : '—';
                    },
                    days(value) { return value === 1 ? @js(__('1 día')) : value + ' ' + @js(__('días')); },
                    // More than a week of work late is red, more than two days orange
                    severity(value) {
                        if (value > 5) return 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400';
                        if (value > 2) return 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400';
                        return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400';
                    },
                    select(tab) { this.tab = tab; this.$nextTick(() => this.$refs[tab].focus()); },
                }">

                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    {{-- Tabs: arrows move between them, like any tab list --}}
                    <div role="tablist" aria-label="{{ __('Tipo') }}"
                        class="inline-flex w-full gap-1 rounded-lg bg-gray-100 p-1 sm:w-auto dark:bg-gray-900"
                        @keydown.arrow-right.prevent="select(tab === 'parts' ? 'materials' : 'parts')"
                        @keydown.arrow-left.prevent="select(tab === 'parts' ? 'materials' : 'parts')">
                        @foreach (['parts' => __('Piezas'), 'materials' => __('Materia prima')] as $key => $label)
                            <button type="button" role="tab" id="tab-{{ $key }}" x-ref="{{ $key }}" aria-controls="panel"
                                :aria-selected="(tab === '{{ $key }}').toString()" :tabindex="tab === '{{ $key }}' ? 0 : -1"
                                @click="tab = '{{ $key }}'"
                                class="flex h-9 flex-1 items-center justify-center gap-2 rounded-md px-4 text-sm font-medium transition-colors focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-none sm:flex-none"
                                :class="tab === '{{ $key }}' ? 'shadow-theme-xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'">
                                {{ $label }}
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums"
                                    :class="tab === '{{ $key }}' ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-gray-200 text-gray-600 dark:bg-white/5 dark:text-gray-400'">{{ number_format(count($lines[$key])) }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        {{-- Report of the open tab, like the Infor ones (YPU705B / YPU715B) --}}
                        <div x-data="{ formats: false }" class="relative" @click.outside="formats = false" @keydown.escape.stop="formats = false">
                            <button type="button" @click="formats = !formats" :aria-expanded="formats.toString()" aria-haspopup="menu"
                                class="shadow-theme-xs inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus-visible:ring-3 focus-visible:ring-brand-500/20 focus-visible:outline-none sm:w-auto dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                                <x-svg.download /> {{ __('Descargar') }}
                            </button>
                            <div x-show="formats" x-cloak role="menu"
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="shadow-theme-lg absolute end-0 top-full z-10 mt-2 w-40 origin-top-right rounded-xl border border-gray-200 bg-white p-1.5 rtl:origin-top-left dark:border-gray-800 dark:bg-gray-900">
                                <a role="menuitem" :href="urls[tab].pdf" @click="formats = false"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 focus-visible:bg-gray-100 focus-visible:outline-none dark:text-gray-300 dark:hover:bg-white/5 dark:focus-visible:bg-white/5">
                                    <x-svg.file-pdf class="size-4 text-error-500" /> PDF
                                </a>
                                <a role="menuitem" :href="urls[tab].excel" @click="formats = false"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 focus-visible:bg-gray-100 focus-visible:outline-none dark:text-gray-300 dark:hover:bg-white/5 dark:focus-visible:bg-white/5">
                                    <x-svg.file-excel class="size-4 text-success-600 dark:text-success-500" /> Excel
                                </a>
                            </div>
                        </div>

                        <label for="unreceived-sort" class="sr-only">{{ __('Ordenar') }}</label>
                        <div class="relative">
                            <select id="unreceived-sort" x-model="sort"
                                class="shadow-theme-xs h-10 w-full cursor-pointer appearance-none rounded-lg border border-gray-300 bg-white ps-4 pe-10 text-sm text-gray-700 transition hover:bg-gray-50 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden sm:w-auto dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/5">
                                <option value="oldest">{{ __('Más antiguo primero') }}</option>
                                <option value="newest">{{ __('Más reciente primero') }}</option>
                            </select>
                            <span class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.79 7.4 10 12.6l5.21-5.2" /></svg>
                            </span>
                        </div>
                        <div class="relative sm:w-80">
                            <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"><x-svg.search /></span>
                            <label for="unreceived-search" class="sr-only">{{ __('Buscar') }}</label>
                            <input id="unreceived-search" type="search" x-model="query" autocomplete="off"
                                placeholder="{{ __('Buscar por parte, orden o material') }}" class="{{ str_replace('pe-3', 'pe-9', $search) }} [&::-webkit-search-cancel-button]:hidden">
                            <button type="button" x-show="query" x-cloak @click="query = ''" aria-label="{{ __('Limpiar búsqueda') }}"
                                class="absolute end-2 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="panel" role="tabpanel" :aria-labelledby="'tab-' + tab">
                    {{-- Nothing pending at all in this tab --}}
                    <div x-show="rows[tab].length === 0" class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-gray-800">
                        <span class="flex size-12 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                        </span>
                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ __('Todo al corriente') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No hay entregas vencidas pendientes en esta sección.') }}</p>
                    </div>

                    <div x-show="rows[tab].length > 0" class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                        <table class="w-full" :class="tab === 'materials' ? 'min-w-[960px]' : 'min-w-[720px]'">
                            <thead class="bg-gray-50 dark:bg-white/[0.03]">
                                <tr>
                                    <th class="{{ $th }} text-start">{{ __('Atraso') }}</th>
                                    <th class="{{ $th }} text-start">{{ __('Número de parte') }}</th>
                                    <th x-show="tab === 'materials'" class="{{ $th }} text-start">{{ __('Material') }}</th>
                                    <th x-show="tab === 'materials'" class="{{ $th }} text-start">{{ __('Medidas') }}</th>
                                    <th class="{{ $th }} text-start">{{ __('Orden de compra') }}</th>
                                    <th class="{{ $th }} text-start">{{ __('Fecha de entrega') }}</th>
                                    <th class="{{ $th }} text-end">{{ __('Ordenado') }}</th>
                                    <th class="{{ $th }} text-end">{{ __('Pendiente') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <template x-for="row in visible" :key="row.order">
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="{{ $td }}">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums" :class="severity(row.days_late)" x-text="days(row.days_late)"></span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="flex items-center gap-2 text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">
                                                <span x-text="row.part"></span>
                                                {{-- What Infor changed in the last days (new line, or another pending quantity) --}}
                                                <span x-show="row.change" class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
                                                    :title="row.change ? changeDate(row.change.at) : ''"
                                                    x-text="row.change?.kind === 'new' ? @js(__('Nueva')) : @js(__('Actualizada'))"></span>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="row.description"></p>
                                        </td>
                                        <td x-show="tab === 'materials'" class="px-4 py-3">
                                            <p class="text-sm whitespace-nowrap text-gray-700 dark:text-gray-300" x-text="row.material || '—'"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="row.symbol"></p>
                                        </td>
                                        <td x-show="tab === 'materials'" class="{{ $td }} tabular-nums" x-text="size(row)"></td>
                                        <td class="{{ $td }} tabular-nums" x-text="row.order"></td>
                                        <td class="{{ $td }} tabular-nums" x-text="date(row.due_date)"></td>
                                        <td class="{{ $td }} text-end tabular-nums" x-text="num(row.quantity)"></td>
                                        <td class="{{ $td }} text-end font-semibold text-gray-800 tabular-nums dark:text-white/90">
                                            <span x-text="num(row.pending)"></span>
                                            <span x-show="row.change?.kind === 'changed'" class="block text-xs font-normal text-gray-500 dark:text-gray-400"
                                                x-text="@js(__('Antes')) + ' ' + num(row.change?.before ?? 0)"></span>
                                            <span x-show="tab === 'materials'" class="text-xs font-normal text-gray-500 dark:text-gray-400" x-text="row.unit"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filtered.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('Nada coincide con la búsqueda.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Same look as x-common.pagination, on the rows already loaded --}}
                    @php $link = 'flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium'; @endphp
                    <div x-show="filtered.length > 0" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-theme-sm text-gray-500 tabular-nums dark:text-gray-400" x-text="range"></p>
                        <nav x-show="lastPage > 1" class="flex flex-wrap items-center gap-1.5" aria-label="{{ __('Páginas') }}">
                            <button type="button" @click="page--" :disabled="page === 1"
                                class="{{ $link }} border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:border-gray-200 disabled:text-gray-300 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:disabled:border-gray-800 dark:disabled:text-gray-600">{{ __('Anterior') }}</button>
                            <template x-for="item in pages" :key="item.n">
                                <span class="flex items-center gap-1.5">
                                    <span x-show="item.gap" class="px-1 text-gray-400" aria-hidden="true">…</span>
                                    <button type="button" @click="page = item.n" x-text="num(item.n)"
                                        :aria-current="item.n === page ? 'page' : null"
                                        class="{{ $link }}"
                                        :class="item.n === page ? 'bg-brand-500 text-white' : 'text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:text-gray-400 dark:hover:bg-brand-500/15 dark:hover:text-brand-400'"></button>
                                </span>
                            </template>
                            <button type="button" @click="page++" :disabled="page === lastPage"
                                class="{{ $link }} border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:border-gray-200 disabled:text-gray-300 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:disabled:border-gray-800 dark:disabled:text-gray-600">{{ __('Siguiente') }}</button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
