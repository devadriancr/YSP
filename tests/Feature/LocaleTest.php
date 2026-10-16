<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the portal is in spanish by default and switches to english and japanese', function () {
    $admin = User::factory()->create(['role' => User::ADMIN]);

    $this->actingAs($admin)->get('/users')->assertOk()->assertSee('Nuevo usuario');

    $this->get('/locale/en')->assertRedirect();
    $this->get('/users')->assertOk()->assertSee('New user')->assertDontSee('Nuevo usuario');

    $this->get('/locale/ja');
    $this->get('/users')->assertOk()->assertSee('新規ユーザー');
});

test('the sign in page offers the three languages', function () {
    $this->get('/signin')->assertOk()->assertSee('Iniciar Sesión');

    $this->get('/locale/ja');
    $this->get('/signin')->assertOk()->assertSee('ログイン')
        ->assertSee(route('locale.switch', 'es'))
        ->assertSee(route('locale.switch', 'en'));
});

test('validation messages follow the language', function () {
    $admin = User::factory()->create(['role' => User::ADMIN]);

    $this->actingAs($admin)->post('/users', [])->assertSessionHasErrors(['name' => 'El campo nombre es obligatorio.']);

    $this->get('/locale/ja');
    $this->post('/users', [])->assertSessionHasErrors(['name' => '名前は必須です。']);

    $this->get('/locale/en');
    $this->post('/users', [])->assertSessionHasErrors(['name' => 'The name field is required.']);
});

test('the profile and the password and two factor screens follow the language', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/locale/ja');
    $this->get('/profile')->assertOk()
        ->assertSee('二要素認証')->assertSee('パスワードの変更')->assertSee('<title>プロフィール | サプライヤーポータル</title>', false);

    $this->get('/locale/en');
    $this->get('/user/confirm-password')->assertOk()->assertSee('This is a secure area. Confirm your password to continue.');

    // A wrong current password comes back in the chosen language
    $this->put('/user/password', ['current_password' => 'wrong', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])
        ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'The current password is incorrect.']);
});

test('the forgot password screen has the language switcher and the mexican flag', function () {
    $this->get('/locale/en');
    $this->get('/forgot-password')->assertOk()
        ->assertSee('Reset password')->assertSee('Back to sign in')
        ->assertSee('/images/icons/flag-mx.svg');
});
