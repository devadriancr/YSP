@extends('layouts.app')

{{-- The calendar takes the whole width of the screen, without the usual max width --}}
@section('content-class', 'p-4 md:p-6')

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('Calendario')" />

    <x-calender-area :events="$events ?? []" :vendors="$vendors ?? collect()" :vendor="$vendor ?? null"
        :availableFrom="$availableFrom ?? null" :visibleFrom="$visibleFrom ?? null" />
@endsection
