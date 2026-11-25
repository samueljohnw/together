<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it adds a member who can sign in', function () {
    $this->artisan('together:add-member', ['email' => 'sam@example.com', 'name' => 'Sam'])
        ->expectsQuestion('Password (at least 8 characters)', 'correct-horse')
        ->expectsQuestion('Confirm password', 'correct-horse')
        ->expectsOutput('Sam can now sign in as sam@example.com.')
        ->assertSuccessful();

    $user = User::sole();
    expect($user->name)->toBe('Sam')
        ->and(Hash::check('correct-horse', $user->password))->toBeTrue();
});

test('it refuses mismatched passwords', function () {
    $this->artisan('together:add-member', ['email' => 'sam@example.com', 'name' => 'Sam'])
        ->expectsQuestion('Password (at least 8 characters)', 'correct-horse')
        ->expectsQuestion('Confirm password', 'different-horse')
        ->expectsOutput('The passwords did not match.')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('it refuses short passwords', function () {
    $this->artisan('together:add-member', ['email' => 'sam@example.com', 'name' => 'Sam'])
        ->expectsQuestion('Password (at least 8 characters)', 'short')
        ->expectsOutput('The password must be at least 8 characters.')
        ->assertFailed();
});

test('it refuses an email that is already a member', function () {
    User::factory()->create(['email' => 'sam@example.com']);

    $this->artisan('together:add-member', ['email' => 'sam@example.com', 'name' => 'Sam'])
        ->expectsOutput('The email has already been taken.')
        ->assertFailed();
});
