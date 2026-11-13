<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Each permission of config/permissions.php is a gate: @can('labels.download'), 'can:labels.download'
        foreach (array_keys(config('permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // Suspicious activity in the log: wrong passwords and accounts locked by the login limit
        Event::listen(Failed::class, fn (Failed $event) => Log::warning('Failed login', [
            'email' => $event->credentials['email'] ?? null,
            'ip' => request()->ip(),
        ]));
        Event::listen(Lockout::class, fn (Lockout $event) => Log::warning('Login locked: too many attempts', [
            'email' => $event->request->input('email'),
            'ip' => $event->request->ip(),
        ]));
    }
}
