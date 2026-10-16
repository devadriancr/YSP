@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'placeholder' => '',
    'hint' => null,
    'required' => false,
    'autocomplete' => null,
])

{{-- Text input with the template styles: default, and the error state when validation fails --}}
@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $isPassword = $type === 'password';
    $baseClass = 'dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $stateClass = $error
        ? 'border-error-300 focus:border-error-300 focus:ring-error-500/10 dark:border-error-700 dark:focus:border-error-800 pe-10'
        : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:focus:border-brand-800';
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        {{ $label }}
        @if ($required)<span class="text-error-500">*</span>@endif
    </label>

    <div class="relative" @if ($isPassword) x-data="{ showPassword: false }" @endif>
        <input id="{{ $id }}" name="{{ $name }}"
            @if ($isPassword) :type="showPassword ? 'text' : 'password'" type="password" @else type="{{ $type }}" @endif
            value="{{ $isPassword ? '' : old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($required) required @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->except('id')->merge(['class' => "$baseClass $stateClass" . ($isPassword ? ' pe-11' : '')]) }} />

        @if ($isPassword)
            <button type="button" @click="showPassword = !showPassword"
                :aria-label="showPassword ? @js(__('Ocultar contraseña')) : @js(__('Mostrar contraseña'))"
                class="absolute end-4 top-1/2 z-30 -translate-y-1/2 cursor-pointer text-gray-500 dark:text-gray-400">
                <svg x-show="!showPassword" class="size-5 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z" />
                </svg>
                <svg x-show="showPassword" x-cloak class="size-5 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z" />
                </svg>
            </button>
        @elseif ($error)
            <span class="pointer-events-none absolute end-3.5 top-1/2 -translate-y-1/2 text-error-500">
                <svg class="size-4 fill-current" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.58325 7.99967C2.58325 5.00813 5.00838 2.58301 7.99992 2.58301C10.9915 2.58301 13.4166 5.00813 13.4166 7.99967C13.4166 10.9912 10.9915 13.4163 7.99992 13.4163C5.00838 13.4163 2.58325 10.9912 2.58325 7.99967ZM7.99992 1.08301C4.17995 1.08301 1.08325 4.17971 1.08325 7.99967C1.08325 11.8196 4.17995 14.9163 7.99992 14.9163C11.8199 14.9163 14.9166 11.8196 14.9166 7.99967C14.9166 4.17971 11.8199 1.08301 7.99992 1.08301ZM7.09932 5.01639C7.09932 5.51345 7.50227 5.91639 7.99932 5.91639H7.99999C8.49705 5.91639 8.89999 5.51345 8.89999 5.01639C8.89999 4.51933 8.49705 4.11639 7.99999 4.11639H7.99932C7.50227 4.11639 7.09932 4.51933 7.09932 5.01639ZM7.99998 11.8306C7.58576 11.8306 7.24998 11.4948 7.24998 11.0806V7.29627C7.24998 6.88206 7.58576 6.54627 7.99998 6.54627C8.41419 6.54627 8.74998 6.88206 8.74998 7.29627V11.0806C8.74998 11.4948 8.41419 11.8306 7.99998 11.8306Z" />
                </svg>
            </span>
        @endif
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="text-theme-xs text-error-500 mt-1.5">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="text-theme-xs mt-1.5 text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
