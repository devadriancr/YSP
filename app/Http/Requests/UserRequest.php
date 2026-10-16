<?php

namespace App\Http\Requests;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Create or edit a user from the admin screen. On edit the password is
 * optional: left empty, the current one is kept.
 */
class UserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(User::ROLES)],
            'vendors' => ['nullable', 'array'],
            'vendors.*' => ['integer', 'distinct', Rule::exists('vendors', 'id')],
            'password' => $user
                ? ['nullable', 'string', Password::default(), 'confirmed']
                : $this->passwordRules(),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // An admin cannot take away their own admin role (they would lose access to this screen)
            if ($this->route('user')?->is($this->user()) && $this->input('role') !== User::ADMIN) {
                $validator->errors()->add('role', __('No puedes quitarte tu propio rol de administrador.'));
            }
        });
    }
}
