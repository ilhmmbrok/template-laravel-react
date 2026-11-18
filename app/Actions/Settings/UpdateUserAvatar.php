<?php

namespace App\Actions\Settings;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateUserAvatar
{
    public function store(User $user, Request $request): void
    {
        $oldPath = $user->avatar_path;

        $path = $request->image('avatar')
            ->orient()
            ->cover(512, 512)
            ->toWebp()
            ->quality(85)
            ->storePublicly('avatars', 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'avatar' => 'The image could not be processed.',
            ]);
        }

        $user->forceFill(['avatar_path' => $path])->save();

        $this->deleteFile($oldPath);
    }

    public function delete(User $user): void
    {
        $path = $user->avatar_path;

        if ($path === null) {
            return;
        }

        $user->forceFill(['avatar_path' => null])->save();

        $this->deleteFile($path);
    }

    private function deleteFile(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
