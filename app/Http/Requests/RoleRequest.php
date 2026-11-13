<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or edit a role from the admin screen.
 */
class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->route('role'))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(array_keys(config('permissions')))],
        ];
    }

    /** @return array{name: string, permissions: list<string>} No checkbox ticked sends no field: an empty list. */
    public function roleData(): array
    {
        return ['name' => $this->validated('name'), 'permissions' => $this->validated('permissions', [])];
    }
}
