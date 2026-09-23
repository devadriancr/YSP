@extends('layouts.fullscreen-layout')

@section('content')
    <x-auth.layout :title="__('Confirmar contraseña')"
        :description="__('Esta es un área segura. Confirma tu contraseña para continuar.')">
        <form method="POST" action="{{ route('password.confirm.store') }}">
            @csrf
            <div class="space-y-5">
                <x-auth.input :label="__('Contraseña')" name="password" type="password" autocomplete="current-password" autofocus />
                <x-auth.button>{{ __('Confirmar') }}</x-auth.button>
            </div>
        </form>
    </x-auth.layout>
@endsection
