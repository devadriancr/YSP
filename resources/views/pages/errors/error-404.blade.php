@extends('layouts.fullscreen-layout')

@section('content')
  <div class="relative flex flex-col items-center justify-center min-h-screen p-6 overflow-hidden z-1">
      {{-- common grid shape --}}
      <x-common.common-grid-shape />
      <!-- Centered Content -->
      <div class="mx-auto w-full max-w-[242px] text-center sm:max-w-[472px]">
          <h1 class="mb-8 font-bold text-gray-800 text-title-sm dark:text-white/90 xl:text-title-md">
              {{ __('Página no encontrada') }}
          </h1>

          <img src="/images/error/404.svg" alt="404" class="dark:hidden" />
          <img src="/images/error/404-dark.svg" alt="404" class="hidden dark:block" />

          <p class="mt-10 mb-6 text-base text-gray-700 dark:text-gray-400 sm:text-lg">
              {{ __('No encontramos la página que buscas. Puede que la dirección esté mal escrita o que ya no exista.') }}
          </p>

          <a href="{{ route('calendar') }}"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
              <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18" /></svg>
              {{ __('Ir al calendario') }}
          </a>
      </div>
      <!-- Footer -->
      <p class="absolute inset-x-0 bottom-6 text-sm text-center text-gray-500 dark:text-gray-400">
          &copy; {{ date('Y') }} - Y-Tec Keylex México
      </p>
  </div>
@endsection
