@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Calendario" />
    <x-calender-area :events="$events ?? []" />
@endsection
