<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function userWithRoleKey(string $role): User
{
    $user = User::factory()->create();
    $user->role = $role;
    $user->save();

    return $user;
}

test('supply chain exists from the start and works like a planner', function () {
    $user = userWithRoleKey('supply_chain');

    expect($user->seesAllVendors())->toBeTrue()
        ->and($user->hasPermission('labels.download'))->toBeTrue()
        ->and(userWithRoleKey(User::SUPPLIER)->seesAllVendors())->toBeFalse();
    $this->actingAs($user)->get('/roles')->assertForbidden();
});

test('only admins manage roles, and a new role can be created with its permissions', function () {
    $this->actingAs(userWithRoleKey(User::PLANNER))->get('/roles')->assertForbidden();

    $this->actingAs(userWithRoleKey(User::ADMIN))
        ->post('/roles', ['name' => 'Compras', 'permissions' => ['labels.download', 'vendors.all']])
        ->assertRedirect('/roles');

    $role = Role::where('name', 'Compras')->firstOrFail();
    expect($role->permissions)->toBe(['labels.download', 'vendors.all']);

    $this->actingAs(userWithRoleKey(User::ADMIN))
        ->post('/roles', ['name' => 'Otro', 'permissions' => ['inventada']])
        ->assertSessionHasErrors('permissions.0');
});

test('a role without a permission cannot use that button or download', function () {
    $role = Role::where('key', 'supply_chain')->firstOrFail();
    $role->update(['permissions' => ['vendors.all', 'documents.details']]);
    $user = userWithRoleKey('supply_chain');

    $this->actingAs($user)->get('/calendar/2099-01-01/identification-cards')->assertForbidden();
    $this->actingAs($user)->get('/calendar/2099-01-01/delivery-instruction/excel')->assertForbidden();
    $this->actingAs($user)->get('/calendar/2099-01-01/versions')->assertForbidden();
    $this->actingAs($user)->get('/unreceived')->assertForbidden();
    $this->actingAs($user)->get('/calendar')->assertDontSee('Partes no recibidas');

    // with the permission the page opens (it needs a vendor to show)
    Vendor::create(['number' => 400010, 'name' => 'HIRUTA MEXICO SA DE CV']);
    $role->update(['permissions' => ['vendors.all', 'unreceived.view']]);
    $this->actingAs(userWithRoleKey('supply_chain'))->get('/unreceived')->assertOk();
});

test('the administrator role is not edited and built-in or used roles are not deleted', function () {
    $admin = userWithRoleKey(User::ADMIN);

    $this->actingAs($admin)->get('/roles/'.Role::where('key', 'admin')->value('id').'/edit')->assertForbidden();
    $this->actingAs($admin)->delete('/roles/'.Role::where('key', 'planner')->value('id'))->assertSessionHas('error');
    expect(Role::where('key', 'planner')->exists())->toBeTrue();

    $custom = (new Role(['name' => 'Temporal', 'permissions' => []]))->forceFill(['key' => 'temporal']);
    $custom->save();
    userWithRoleKey('temporal');
    $this->actingAs($admin)->delete("/roles/{$custom->id}")->assertSessionHas('error');

    User::where('role', 'temporal')->delete();
    $this->actingAs($admin)->delete("/roles/{$custom->id}")->assertRedirect('/roles');
    expect(Role::where('key', 'temporal')->exists())->toBeFalse();
});

test('a user can be given a role created in the screen', function () {
    $this->actingAs(userWithRoleKey(User::ADMIN))->post('/roles', ['name' => 'Compras', 'permissions' => []]);
    $key = Role::where('name', 'Compras')->value('key');

    $this->actingAs(userWithRoleKey(User::ADMIN))->post('/users', [
        'name' => 'Luis', 'email' => 'luis@ykm.test', 'role' => $key,
        'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
    ])->assertRedirect('/users');

    expect(User::where('email', 'luis@ykm.test')->value('role'))->toBe($key);
});
