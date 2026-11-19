<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): Response
    {
        $user = $request->user();

        $user->forceFill(['password' => $request->input('password')])->save();

        $user->tokens()
            ->where('id', '!=', $user->currentAccessToken()->id)
            ->delete();

        return response()->noContent();
    }
}
