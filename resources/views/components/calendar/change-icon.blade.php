@props([
    // Alpine expression for the change kind: 'new' | 'modified' | 'removed'
    'of',
])

{{-- One icon per kind of change: sparkles new, pencil modified, trash removed --}}
<span {{ $attributes->class('inline-flex shrink-0') }}>
    <template x-if="{{ $of }} === 'new'"><x-svg.sparkles class="size-3.5" /></template>
    <template x-if="{{ $of }} === 'modified'"><x-svg.pencil class="size-3.5" /></template>
    <template x-if="{{ $of }} === 'removed'"><x-svg.trash class="size-3.5" /></template>
</span>
