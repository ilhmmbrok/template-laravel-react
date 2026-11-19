<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('me returns the authenticated user', function () {
    Sanctum::actingAs($user = User::factory()->create());

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

test('user can update the profile', function () {
    Sanctum::actingAs($user = User::factory()->create());

    $this->patchJson('/api/v1/me', [
        'name' => 'New Name',
        'email' => $user->email,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

test('user can upload and remove an avatar', function () {
    Storage::fake('public');
    Sanctum::actingAs($user = User::factory()->create());

    $this->postJson('/api/v1/me/avatar', [
        'avatar' => UploadedFile::fake()->image('a.jpg', 600, 600),
    ])->assertOk();

    Storage::disk('public')->assertExists($user->refresh()->avatar_path);

    $this->deleteJson('/api/v1/me/avatar')
        ->assertOk()
        ->assertJsonPath('data.avatar', null);

    expect($user->refresh()->avatar_path)->toBeNull();
});

test('changing the password revokes other tokens', function () {
    $user = User::factory()->create();
    $user->createToken('old-device');
    $current = $user->createToken('current-device')->plainTextToken;

    $this->withToken($current)->putJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertNoContent();

    expect($user->tokens()->pluck('name')->all())->toBe(['current-device']);
});

test('user can delete the account', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Pixel 9')->plainTextToken;

    $this->withToken($token)
        ->deleteJson('/api/v1/me', ['current_password' => 'password'])
        ->assertNoContent();

    $this->assertModelMissing($user);
});
