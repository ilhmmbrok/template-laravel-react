<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Settings\UpdateUserAvatar;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateAvatarRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
    public function store(UpdateAvatarRequest $request, UpdateUserAvatar $avatar): UserResource
    {
        $avatar->store($request->user(), $request);

        return new UserResource($request->user());
    }

    public function destroy(Request $request, UpdateUserAvatar $avatar): UserResource
    {
        $avatar->delete($request->user());

        return new UserResource($request->user());
    }
}
