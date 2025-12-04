<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Account', 'Manage user account')]
#[Authenticated]
class AccountController extends Controller
{
    #[Endpoint('Get user details', 'Retrieve details of the currently authenticated user.', true)]
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function index(#[CurrentUser] User $user): JsonResource
    {
        return UserResource::make($user);
    }

    #[Endpoint('Update profile', "Update the authenticated user's profile information.", true)]
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function update(#[CurrentUser] User $user, UpdateProfileRequest $request): JsonResource
    {
        $user->update($request->validated());

        if ($user->wasChanged('email')) {
            $user->update(['email_verified_at' => null]);
        }

        return UserResource::make($user);
    }

    #[Endpoint('Change password', "Change the authenticated user's password.", true)]
    #[ResponseFromApiResource(UserResource::class, User::class)]
    public function changePassword(#[CurrentUser] User $user, ChangePasswordRequest $request): JsonResource
    {
        $user->update([
            'password' => $request->validated('password'),
        ]);

        return UserResource::make($user);
    }

    #[Endpoint('Get balance', "Retrieve the authenticated user's current balance.", true)]
    public function balance(#[CurrentUser] User $user): JsonResponse
    {
        return response()->json([
            'data' => [
                'balance' => $user->balance,
            ],
        ]);
    }
}
