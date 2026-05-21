<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Call;
use App\Models\User;

final class CreateNewCall
{
    public function handle(User $user, array $input): Call
    {
        dd($input);
        return $user->calls()->create($input);
    }
}
