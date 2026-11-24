<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\User\UserProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): UserProfileResource
    {
        return new UserProfileResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserProfileResource
    {
        $user = $request->user();
        $oldEmail = $user->email;

        $user->fill($request->validated());

        // Require email verification again if the email changes.
        if ($user->email !== $oldEmail) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserProfileResource($user->refresh());
    }
}
