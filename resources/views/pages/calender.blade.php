@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('Calendario')" />

    <x-calender-area :events="$events ?? []" :vendors="$vendors ?? collect()" :vendor="$vendor ?? null"
        :availableFrom="$availableFrom ?? null" :visibleFrom="$visibleFrom ?? null" />
@endsection
