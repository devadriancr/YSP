<x-profile.section :title="__('Actualizar contraseña')"
    bag="updatePassword"
    :statuses="['password-updated' => __('Tu contraseña se actualizó correctamente.')]">
    <form method="POST" action="{{ route('user-password.update') }}">
        @csrf
        @method('PUT')
        <div class="space-y-5">
            <x-auth.input :label="__('Contraseña actual')" name="current_password" type="password" autocomplete="current-password" />
            <x-auth.input :label="__('Nueva contraseña')" name="password" id="new_password" type="password" autocomplete="new-password" />
            <x-auth.input :label="__('Confirmar contraseña')" name="password_confirmation" id="new_password_confirmation" type="password" autocomplete="new-password" />
            <div class="flex justify-end">
                <x-auth.button class="!w-auto px-6">{{ __('Guardar') }}</x-auth.button>
            </div>
        </div>
    </form>
</x-profile.section>
