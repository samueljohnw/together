<?php

use App\Models\User;

test('guests are redirected to the sign-in page', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with(['workspace' => '/', 'account' => '/account', 'items' => '/items']);

test('json requests from guests are rejected as unauthenticated', function () {
    $this->getJson(route('items.index'))->assertUnauthorized();
});

test('a member signs in with their email and password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('a wrong password is rejected', function () {
    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'That email and password did not match.']);

    $this->assertGuest();
});

test('sign-in attempts are rate limited', function () {
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertTooManyRequests();
});

test('a member signs out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
