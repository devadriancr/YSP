<x-profile.section :title="__('Información del perfil')"
    bag="updateProfileInformation"
    :statuses="['profile-information-updated' => __('Tu información se guardó correctamente.')]">
    <form method="POST" action="{{ route('user-profile-information.update') }}">
        @csrf
        @method('PUT')
        <div class="space-y-5">
            <x-auth.input :label="__('Nombre')" name="name" id="profile_name" :value="old('name', auth()->user()->name)" autocomplete="name" />
            <x-auth.input :label="__('Correo Electrónico')" name="email" id="profile_email" type="email" :value="old('email', auth()->user()->email)" autocomplete="username" />
            <div class="flex justify-end">
                <x-auth.button class="!w-auto px-6">{{ __('Guardar') }}</x-auth.button>
            </div>
        </div>
    </form>
</x-profile.section>
