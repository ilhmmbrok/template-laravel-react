<?php

use App\Models\User;

test('user can log in and receives a token', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Pixel 9',
    ])
        ->assertOk()
        ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at', 'user' => ['id', 'email']]]);
});

test('login fails with a wrong password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'Pixel 9',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('user can register', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password-123456',
        'password_confirmation' => 'password-123456',
        'device_name' => 'Pixel 9',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'test@example.com');
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Pixel 9')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

test('protected routes require a token', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});
