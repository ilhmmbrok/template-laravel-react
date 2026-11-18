<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function updateProfile(User $user, array $extra = [])
{
    return test()->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        ...$extra,
    ]);
}

test('user can upload an avatar', function () {
    $user = User::factory()->create();

    updateProfile($user, ['avatar' => UploadedFile::fake()->image('a.jpg', 600, 600)])
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertExists($user->refresh()->avatar_path);
});

test('replacing the avatar deletes the old file', function () {
    $user = User::factory()->create();

    updateProfile($user, ['avatar' => UploadedFile::fake()->image('a.jpg', 600, 600)]);
    $old = $user->refresh()->avatar_path;

    updateProfile($user, ['avatar' => UploadedFile::fake()->image('b.jpg', 600, 600)]);

    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($user->refresh()->avatar_path);
});

test('user can remove the avatar', function () {
    $user = User::factory()->create();

    updateProfile($user, ['avatar' => UploadedFile::fake()->image('a.jpg', 600, 600)]);
    $path = $user->refresh()->avatar_path;

    updateProfile($user, ['remove_avatar' => 1]);

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('avatar must be an image', function () {
    $user = User::factory()->create();

    updateProfile($user, ['avatar' => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf')])
        ->assertSessionHasErrors('avatar');
});
