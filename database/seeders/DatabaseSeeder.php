<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the first administrator. It does not use the factory: Faker is a dev dependency
     * and the server installs without them. Running it again leaves an existing user as it is.
     */
    public function run(): void
    {
        $user = User::firstOrNew(['email' => 'jesus.camacho@ykm.com.mx']);

        if (! $user->exists) {
            // role is not fillable on purpose, so it is set with forceFill
            $user->forceFill([
                'name' => 'Adrian Camacho',
                'password' => Hash::make('password'),
                'role' => User::ADMIN,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
