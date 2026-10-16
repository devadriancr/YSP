<?php

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function admin(): User
{
    $user = User::factory()->create(['name' => 'Ana Admin']);
    $user->role = User::ADMIN;
    $user->save();

    return $user;
}

test('only admins see and use the users screen', function () {
    $supplier = User::factory()->create();

    $this->actingAs($supplier)->get('/users')->assertForbidden();
    $this->actingAs($supplier)->get('/calendar')->assertDontSee('href="/users"', false);

    $this->actingAs(admin())->get('/calendar')->assertSee('Usuarios');
});

test('the list shows users with their vendors and can be searched', function () {
    $hiruta = Vendor::create(['number' => 400010, 'name' => 'HIRUTA MEXICO SA DE CV']);
    User::factory()->create(['name' => 'Juan Proveedor', 'email' => 'juan@hiruta.test'])->vendors()->attach($hiruta);
    User::factory()->create(['name' => 'Sin Vendor']);
    $admin = admin();

    $this->actingAs($admin)->get('/users')
        ->assertOk()
        ->assertSee('Juan Proveedor')
        ->assertSee('400010')
        ->assertSee('Sin proveedor asignado');

    $this->actingAs($admin)->get('/users?buscar=hiruta')
        ->assertSee('Juan Proveedor')
        ->assertDontSee('Sin Vendor');

    $this->actingAs($admin)->get('/users?buscar=400010')->assertSee('Juan Proveedor');
});

test('an admin creates a user with several vendors', function () {
    $a = Vendor::create(['number' => 400010, 'name' => 'HIRUTA']);
    $b = Vendor::create(['number' => 400110, 'name' => 'SK TEC']);

    $this->actingAs(admin())->post('/users', [
        'name' => 'María López',
        'email' => 'maria@proveedor.test',
        'role' => 'supplier',
        'vendors' => [$a->id, $b->id],
        'password' => 'Secreta-2026',
        'password_confirmation' => 'Secreta-2026',
    ])->assertRedirect('/users');

    $user = User::firstWhere('email', 'maria@proveedor.test');
    expect($user->role)->toBe('supplier')
        ->and($user->vendors->pluck('number')->all())->toBe([400010, 400110])
        ->and(Hash::check('Secreta-2026', $user->password))->toBeTrue();
});

test('wrong data comes back with the fields in red and their messages', function () {
    User::factory()->create(['email' => 'repetido@proveedor.test']);

    $response = $this->actingAs(admin())->from('/users/create')->post('/users', [
        'name' => '',
        'email' => 'repetido@proveedor.test',
        'role' => 'otro',
        'vendors' => [999],
        'password' => 'corta',
        'password_confirmation' => 'distinta',
    ]);

    $response->assertRedirect('/users/create')->assertSessionHasErrors(['name', 'email', 'role', 'vendors.0', 'password']);

    $this->followRedirects($response)
        ->assertSee('border-error-300', false)
        ->assertSee('aria-invalid="true"', false);
});

test('editing keeps the password when it is left empty and replaces the vendors', function () {
    $old = Vendor::create(['number' => 400010, 'name' => 'HIRUTA']);
    $new = Vendor::create(['number' => 400110, 'name' => 'SK TEC']);
    $user = User::factory()->create();
    $user->vendors()->attach($old);
    $hash = $user->password;

    $this->actingAs(admin())->put("/users/{$user->id}", [
        'name' => 'Nombre Nuevo',
        'email' => $user->email,
        'role' => 'supplier',
        'vendors' => [$new->id],
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect('/users');

    $user->refresh();
    expect($user->name)->toBe('Nombre Nuevo')
        ->and($user->password)->toBe($hash)
        ->and($user->vendors->pluck('number')->all())->toBe([400110]);
});

test('an admin cannot remove their own admin role or delete themselves', function () {
    $admin = admin();

    $this->actingAs($admin)->put("/users/{$admin->id}", [
        'name' => $admin->name, 'email' => $admin->email, 'role' => 'supplier',
    ])->assertSessionHasErrors('role');

    $this->actingAs($admin)->delete("/users/{$admin->id}");

    expect($admin->fresh())->not->toBeNull()
        ->and($admin->fresh()->isAdmin())->toBeTrue();
});

test('an admin deletes another user', function () {
    $user = User::factory()->create(['name' => 'Para Borrar']);

    $this->actingAs(admin())->delete("/users/{$user->id}")
        ->assertRedirect('/users')
        ->assertSessionHas('status', 'Usuario Para Borrar eliminado.');

    expect(User::find($user->id))->toBeNull();
});
