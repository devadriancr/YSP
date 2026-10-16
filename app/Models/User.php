<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public const ADMIN = 'admin';

    public const SUPPLIER = 'supplier';

    // Plans the purchases: sees the calendar of every vendor, without managing users
    public const PLANNER = 'planner';

    public const ROLES = [self::ADMIN, self::PLANNER, self::SUPPLIER];

    // role is left out of $fillable on purpose: only an admin assigns it, like the vendors

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class)->withTimestamps()->orderBy('number');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    /**
     * Planners and admins work with every vendor; suppliers only with the ones assigned to them.
     */
    public function seesAllVendors(): bool
    {
        return in_array($this->role, [self::ADMIN, self::PLANNER], true);
    }

    /**
     * Vendors whose calendar this user can open.
     *
     * @return Collection<int, Vendor>
     */
    public function visibleVendors(): Collection
    {
        // once(): asked several times while building the calendar page, queried a single time
        return once(fn () => $this->seesAllVendors()
            ? Vendor::query()->orderBy('number')->get()
            : $this->vendors);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
