@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb :pageTitle="__('Perfil')" />

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 lg:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
                <x-common.user-avatar size="size-20 text-2xl" class="border border-gray-200 dark:border-gray-800" />
                <div class="text-start">
                    <h4 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                        {{ auth()->user()->name }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ auth()->user()->email }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-profile.info-form />
            <x-profile.password-form />
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-profile.two-factor />
            <x-profile.browser-sessions :sessions="$sessions" :supported="$sessionsSupported" />
        </div>
    </div>
@endsection
