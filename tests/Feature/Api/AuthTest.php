<?php

use App\Models\User;

it('returns a token on successful login', function () {
    User::factory()->create(['password' => bcrypt('secret123')]);

    $this->postJson('/api/login', ['password' => 'secret123'])
        ->assertOk()
        ->assertJsonStructure(['success', 'data' => ['token']]);
});

it('returns 401 on wrong password', function () {
    User::factory()->create(['password' => bcrypt('secret123')]);

    $this->postJson('/api/login', ['password' => 'wrong'])
        ->assertUnauthorized()
        ->assertJson(['success' => false]);
});

it('returns 422 when password is missing', function () {
    $this->postJson('/api/login', [])
        ->assertUnprocessable();
});

it('revokes token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertNoContent();

    // Token record must be deleted from the database
    expect($user->tokens()->count())->toBe(0);
});

it('returns 401 on logout without token', function () {
    $this->postJson('/api/logout')
        ->assertUnauthorized();
});
