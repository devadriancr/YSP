@extends('layouts.fullscreen-layout')

@section('content')
    <x-auth.layout :title="__('Verifica tu correo')"
        :description="__('Antes de continuar, verifica tu correo electrónico con el enlace que te enviamos. Si no lo recibiste, podemos enviarte otro.')">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-auth.button>{{ __('Reenviar correo de verificación') }}</x-auth.button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
            @csrf
            <button type="submit" class="text-brand-500 hover:text-brand-600 dark:text-brand-400 text-sm">{{ __('Cerrar sesión') }}</button>
        </form>
    </x-auth.layout>
@endsection
