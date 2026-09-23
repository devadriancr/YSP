@php
    $user = auth()->user();
    $confirmed = $user->hasEnabledTwoFactorAuthentication();
    $pending = ! $confirmed && ! is_null($user->two_factor_secret);
    $autoLoadCodes = in_array(session('status'), ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true);
    $primaryBtn = 'bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white transition';
    $outlineBtn = 'shadow-theme-xs inline-flex items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition ring-inset hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]';
    $dangerBtn = 'bg-error-500 shadow-theme-xs hover:bg-error-600 inline-flex items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white transition';
@endphp

<x-profile.section :title="__('Autenticación de dos factores')"
    bag="confirmTwoFactorAuthentication"
    :statuses="[
        'two-factor-authentication-enabled' => __('Escanea el código QR y confirma el código.'),
        'two-factor-authentication-confirmed' => __('Habilitada. Guarda tus códigos de recuperación.'),
        'two-factor-authentication-disabled' => __('Autenticación de dos factores deshabilitada.'),
        'recovery-codes-generated' => __('Códigos de recuperación regenerados.'),
    ]">
    <div x-data="{
        codes: [],
        showCodes: false,
        async loadCodes() {
            const response = await fetch(@js(route('two-factor.recovery-codes')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (response.status === 423) { window.location = @js(route('password.confirm')); return; }
            if (response.ok) { this.codes = await response.json(); this.showCodes = true; }
        },
    }"
        @if ($autoLoadCodes) x-init="loadCodes()" @endif>

    @if ($confirmed)
        <div class="mb-5 flex items-center gap-2">
            <span class="bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 text-theme-xs rounded-full px-2.5 py-0.5 font-medium">{{ __('Habilitada') }}</span>
        </div>
        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Al iniciar sesión se te pedirá un código de tu aplicación de autenticación.') }}
        </p>

        <div x-show="showCodes" x-cloak class="mb-5">
            <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Guarda estos códigos en un lugar seguro. Cada uno sirve una sola vez si pierdes tu dispositivo.') }}
            </p>
            <div class="grid gap-1 rounded-lg bg-gray-100 p-4 font-mono text-sm text-gray-800 dark:bg-gray-900 dark:text-white/90">
                <template x-for="code in codes" :key="code"><div x-text="code"></div></template>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="button" x-show="! showCodes" @click="loadCodes()" class="{{ $outlineBtn }}">{{ __('Ver códigos de recuperación') }}</button>
            <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                @csrf
                <button type="submit" class="{{ $outlineBtn }}">{{ __('Regenerar códigos') }}</button>
            </form>
            <form method="POST" action="{{ route('two-factor.disable') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="{{ $dangerBtn }}">{{ __('Deshabilitar') }}</button>
            </form>
        </div>
    @elseif ($pending)
        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Escanea el código QR con tu aplicación de autenticación (por ejemplo, Microsoft Authenticator) y escribe el código que te muestre.') }}
        </p>

        <div class="mb-5 inline-block rounded-lg bg-white p-3 ring-1 ring-gray-200 dark:ring-gray-700">
            {!! $user->twoFactorQrCodeSvg() !!}
        </div>

        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Clave manual:') }}
            <span class="font-mono font-medium text-gray-800 dark:text-white/90">{{ decrypt($user->two_factor_secret) }}</span>
        </p>

        <form id="two-factor-confirm-form" method="POST" action="{{ route('two-factor.confirm') }}">
            @csrf
            <div class="max-w-xs">
                <x-auth.input :label="__('Código')" name="code" id="two_factor_code" autocomplete="one-time-code" inputmode="numeric" />
            </div>
        </form>

        <form id="two-factor-cancel-form" method="POST" action="{{ route('two-factor.disable') }}">
            @csrf
            @method('DELETE')
        </form>

        <div class="mt-5 flex flex-wrap gap-3">
            <button type="submit" form="two-factor-confirm-form" class="{{ $primaryBtn }}">{{ __('Confirmar') }}</button>
            <button type="submit" form="two-factor-cancel-form" class="{{ $outlineBtn }}">{{ __('Cancelar') }}</button>
        </div>
    @else
        <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
            {{ __('No está habilitada. Al habilitarla, se te pedirá un código al iniciar sesión.') }}
        </p>
        <form method="POST" action="{{ route('two-factor.enable') }}">
            @csrf
            <button type="submit" class="{{ $primaryBtn }}">{{ __('Habilitar') }}</button>
        </form>
    @endif
    </div>
</x-profile.section>
