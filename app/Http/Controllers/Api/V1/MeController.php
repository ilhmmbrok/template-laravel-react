<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Settings\UpdateUserAvatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfirmPasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user);
    }

    public function destroy(ConfirmPasswordRequest $request, UpdateUserAvatar $avatar): Response
    {
        $user = $request->user();

        $avatar->delete($user);
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
