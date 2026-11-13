<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'key');
    }

    /** The administrator always has everything and is not edited. */
    public function isAdmin(): bool
    {
        return $this->key === User::ADMIN;
    }

    /** The roles of the portal from the start cannot be deleted (their permissions can change). */
    public function isBuiltIn(): bool
    {
        return in_array($this->key, User::BUILT_IN_ROLES, true);
    }

    public function allows(string $permission): bool
    {
        return $this->isAdmin() || in_array($permission, $this->permissions ?? [], true);
    }
}
